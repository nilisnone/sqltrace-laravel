<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace SQLTrace;

use Illuminate\Database\Events\QueryExecuted;

class TraceAppSchema
{
    protected string $app_uuid = '';
    protected string $app_name = '';
    protected string $run_host = '';
    protected string $run_mode = '';
    protected int $pid = 0;
    protected string $referer = '';
    protected string $request_uri = '';
    protected string $request_query = '';
    protected string $headers = '';
    protected string $request_post = '';
    protected bool $compress = false;
    private array $config = [];

    protected static ?TraceAppSchema $app = null;

    public int $last_push_sql_timestamp = 0;
    public int $last_push_trace_timestamp = 0;
    protected array $push_sql = [];
    protected array $push_trace = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        if (!empty($this->config['app_uuid_variable']) && !empty($_SERVER[$this->config['app_uuid_variable']])) {
            $this->app_uuid = $_SERVER[$this->config['app_uuid_variable']] . '-sql';
        } else {
            $this->app_uuid = Utils::static_uuid();
        }
        $this->app_name = config('app.name', 'default') ?? 'no-app-name';
        $this->run_host = gethostname();
        $this->run_mode = PHP_SAPI;
        $this->pid = getmypid();

        if ($this->run_mode === 'cli') {
            global $argv;
            $this->request_uri = implode(' ', $argv);
            $this->referer = '';
        } else {
            $_uri = $_SERVER['REQUEST_URI'] ?? '';
            $_uri = explode('?', $_uri)[0] ?? '';
            $this->request_uri = sprintf("%s %s", $_SERVER['REQUEST_METHOD'] ?? '', $_uri);
            $this->referer = $_SERVER['HTTP_REFERER'] ?? '';
        }
        $this->headers = static::getHeader();
        $this->request_query = static::getQuery();
        $this->request_post = static::getPost();
        $this->compress = extension_loaded('zlib');
    }

    public function __destruct()
    {
        $this->startPush();
        $this->clearPushSqlTrace();
    }

    public function addPushSql(string $content)
    {
        $this->push_sql[] = $this->compress ? gzcompress($content) : $content;
    }

    public function addPushTrace(string $content)
    {
        $this->push_trace[] = $this->compress ? gzcompress($content) : $content;
    }

    public function clearPushSqlTrace()
    {
        $this->push_sql = [];
        $this->push_trace = [];
    }

    public function getAppUUID(): string
    {
        return $this->app_uuid;
    }

    public function startPush()
    {
        if (count($this->push_sql) > 100 || time() - $this->last_push_sql_timestamp > 60) {
            $sql = json_encode($this->push_sql, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?? '';
            $sql and $this->pushRemote('sql', $sql, $this->compress);
            unset($sql);
            $this->push_sql = [];
            $this->last_push_sql_timestamp = time();
        }
        if (count($this->push_trace) > 100 || time() - $this->last_push_trace_timestamp > 60) {
            $trace = json_encode($this->push_trace, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?? '';
            $trace and $this->pushRemote('trace', $trace, $this->compress);
            unset($trace);
            $this->push_trace = [];
            $this->last_push_trace_timestamp = time();
        }
    }

    public function pushRemote(string $type, string $content, bool $compress)
    {
        if (!in_array($type, ['app', 'sql', 'trace'], true) || !$content) {
            return;
        }
        // todo send to remote
    }

    public static function getQuery()
    {
        if ($_GET) {
            return json_encode($_GET, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return '';
    }

    public static function getHeader()
    {
        return json_encode([
            'request_time' => $_SERVER['REQUEST_TIME'] ?? 0,
            'php_version' => $_SERVER['PHP_VERSION'] ?? '',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
        ]);
    }

    public static function getPost()
    {
        return file_get_contents('php://input') ?: ($_POST ? json_encode($_POST) : '');
    }

    public static function getInstance(array $config): ?TraceAppSchema
    {
        if (null === static::$app) {
            static::$app = new self($config);
            $data = static::$app->toArray();
            Log::getInstance(static::$app)->info('trace-app', $data);
            static::$app->pushRemote('app', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), false);
        }
        return static::$app;
    }

    /**
     * @param QueryExecuted $event
     * @return TraceSqlSchema
     */
    public function create(QueryExecuted $event): TraceSqlSchema
    {
        return TraceSqlSchema::create($this, $event);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function enableBacktrace()
    {
        return $this->config['enable_backtrace'] ?? false;
    }

    public function getLogFile()
    {
        return $this->config['log_file'] ?? '';
    }

    public function getMaxContentLine()
    {
        return $this->config['max_context_line'] ?? 0;
    }

    public function getSQLTimeThreshold()
    {
        if ($this->run_mode === 'cli') {
            return $this->config['sql_time_threshold_cli'] ?? -1;
        }
        return $this->config['sql_time_threshold'] ?? -1;
    }


    public function toArray(): array
    {
        return [
            'app_uuid' => $this->app_uuid,
            'app_name' => $this->app_name,
            'run_host' => $this->run_host,
            'run_mode' => $this->run_mode,
            'pid' => $this->pid,
            'referer' => $this->referer,
            'request_uri' => $this->request_uri,
            'header' => $this->headers,
            'request_query' => $this->request_query,
            'request_post' => $this->request_post,
        ];
    }
}
