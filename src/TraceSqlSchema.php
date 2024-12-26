<?php

namespace SQLTrace;

use Illuminate\Container\Container;
use Illuminate\Database\Events\QueryExecuted;

class TraceSqlSchema
{
    protected string $app_uuid = '';
    protected string $sql_uuid = '';
    protected string $trace_sql = '';
    protected string $trace_sql_fingerprint = '';
    protected string $trace_sql_origins = '';
    protected string $db_host = '';
    protected ?float $run_ms = 0.0;

    /**
     * @param string $app_uuid
     * @param QueryExecuted $event
     *
     * @return TraceSqlSchema
     */
    public static function create(string $app_uuid, QueryExecuted $event): TraceSqlSchema
    {
        $trace_sql = new self($app_uuid, $event);
        $context = $trace_sql->toArray();
        Log::getInstance()->info('trace-sql', $context);
        return $trace_sql;
    }

    /**
     * @param string $app_uuid
     * @param QueryExecuted $event
     */
    public function __construct(string $app_uuid, QueryExecuted $event)
    {
        $this->app_uuid = $app_uuid;
        $this->sql_uuid = Utils::uuid();
        $conf = $event->connection->getConfig();
        $this->db_host = sprintf("mysql://%s@%s:%s/%s", $conf['username'], $conf['host'], $conf['port'], $conf['database']);
        $sql = $event->sql;
        $this->trace_sql_fingerprint = (new SqlDigester())->doDigest($sql);
        foreach ($event->bindings as $binding) {
            $value = $binding;
            if (is_object($binding)) {
                // hotfix: 查询直接使用 DateTime 当参数时的问题
                if ($binding instanceof \DateTimeInterface) {
                    $binding = $binding->format('Y-m-d H:i:s');
                } else {
                    $binding = (string)$binding;
                }
                if (is_numeric($binding)) {
                    $value = $binding;
                } elseif (is_string($binding)) {
                    $value = "'" . $binding . "'";
                }
            } elseif (is_string($binding)) {
                $value = "'" . $binding . "'";
            }
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        $this->trace_sql = $sql;
        $this->run_ms = $event->time;

        if (Container::getInstance()['config']['SQLTrace']['enable_backtrace']) {
            $logback = $this->format_traces(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20));
            $appName = ($_SERVER['APP_NAME'] ?? '') . '/';
            TraceContextSchema::create($this->sql_uuid, $logback);
            foreach ($logback as $item) {
                $file = $item['file'] ?? '';
                if (strpos($file, $appName) === false) {
                    continue;
                }
                $file = explode($appName, $file);
                if (!empty($file[1])) {
                    $this->trace_sql_origins .=$file[1] . '@' . ($item['line'] ?? 0) . ';';
                }
            }
        }
    }


    protected function format_traces(array $traces): array
    {
        $format_traces = [];
        $max = 5;
        while (!empty($traces)) {
            $trace = array_shift($traces);
            if (isset($trace['file']) && strpos($trace['file'], 'vendor') === false && strpos($trace['file'], 'sqltrace-laravel') === false) {
                $format_trace = [
                    'file' => $trace['file'] ?: '',
                    'line' => $trace['line'] ?? 0,
                    'class' => ($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? '') . '(..)'
                ];
                $format_traces[] = $format_trace;
            }
            if (count($format_traces) >= $max) {
                break;
            }
        }
        return $format_traces;
    }

    public function toArray(): array
    {
        return [
            'app_uuid' => $this->app_uuid,
            'sql_uuid' => $this->sql_uuid,
            'run_mode' => PHP_SAPI,
            'trace_sql' => $this->trace_sql,
            'trace_sql_fingerprint' => $this->trace_sql_fingerprint,
            'trace_sql_origins' => $this->trace_sql_origins,
            'db_host' => $this->db_host,
            'db_alias' => md5($this->db_host),
            'run_ms' => $this->run_ms,
        ];
    }
}
