<?php

namespace SQLTrace;

use Illuminate\Container\Container;
use Illuminate\Database\Events\QueryExecuted;

class TraceSqlSchema
{
    protected $app_uuid;
    protected $sql_uuid;
    protected $trace_sql;
    protected $db_host;
    protected $run_ms;
    protected $biz_created_at;

    /**
     * @param string $app_uuid
     * @param QueryExecuted $event
     *
     * @return TraceSqlSchema
     */
    public static function create(string $app_uuid, QueryExecuted $event): TraceSqlSchema
    {
        $trace_sql = new TraceSqlSchema($app_uuid, $event);
        $context = $trace_sql->toArray();
        static::getDefaultContext($context);
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
        $this->db_host = $event->connection->getConfig('host');
        $sql = $event->sql;
        foreach ($event->bindings as $binding) {
            if (is_string($binding)) {
                $value = "'" . $binding . "'";
            } else {
                $value = $binding;
            }
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        $this->trace_sql = $sql;
        $this->run_ms = $event->time;
        $this->biz_created_at = Utils::get_datetime_ms();

        if (Container::getInstance()['config']['SQLTrace']['enable_backtrace']) {
            $logback = $this->format_traces(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
            TraceContextSchema::create($this->sql_uuid, $logback);
        }
    }
    protected static function getDefaultContext(array &$context): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);
        foreach ($trace as $v) {
            $file = $v['file'] ?? '';
            if (!$file) {
                continue;
            }
            if (strpos($file, 'vendor') !== false) {
                continue;
            }
            $context['call_sql_position'] = sprintf(
                '%s@%d',
                $v['file'] ?? '',
                $v['line'] ?? ''
            );
            return;
        }
    }

    protected function format_traces(array $traces): array
    {
        $format_traces = [];
        while (!empty($traces)) {
            $trace = array_pop($traces);
            $skip_folder = 'vendor';
            if (isset($trace['file']) && strpos($trace['file'] ?? '', $skip_folder) === false) {
                $format_trace = [
                    'file' => $trace['file'] ?: '',
                    'line' => $trace['line'] ?? 0,
                    'class' => $trace['class'] . $trace['type'] . $trace['function'] . '(..)'
                ];
                $format_traces[] = $format_trace;
            }
        }
        return $format_traces;
    }

    public function toArray(): array
    {
        return [
            'app_uuid' => $this->app_uuid,
            'sql_uuid' => $this->sql_uuid,
            'trace_sql' => $this->trace_sql,
            'db_host' => $this->db_host,
            'run_ms' => $this->run_ms,
            'biz_created_at' => $this->biz_created_at,
        ];
    }
}