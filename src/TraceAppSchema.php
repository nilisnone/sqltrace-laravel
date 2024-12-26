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
    private array $config = [];

    protected static ?TraceAppSchema $app = null;

    public int $last_push_timestamp = 0;
    public array $push_sql = [];
    public array $push_trace = [];

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
    }

    public function __destruct()
    {
        $this->startPush();
    }

    public function addPushSql(string $content)
    {
        $this->push_sql[] = $content;
    }

    public function addPushTrace(string $content)
    {
        $this->push_trace[] = $content;
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
        if (count($this->push_sql) < 60 || time() - $this->last_push_timestamp < 60) {
            return;
        }
        $sql = json_encode($this->push_sql, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?? '';
        $trace = json_encode($this->push_trace, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?? '';
        $compress = extension_loaded('zlib');
        if ($compress) {
            $sql = $sql ? gzcompress($sql) : '';
            $trace = $trace ? gzcompress($trace) : '';
        }

        $sql and $this->pushRemote('sql', $sql, $compress);
        $trace and $this->pushRemote('trace', $trace, $compress);
        $this->last_push_timestamp = time();
        $this->push_trace = [];
        $this->push_sql = [];
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

    /**
     * @param QueryExecuted $event
     * @param array $config
     */
    public static function create(QueryExecuted $event, array $config)
    {
        if (null === static::$app) {
            static::$app = new self($config);
            $data = static::$app->toArray();
            Log::getInstance(static::$app)->info('trace-app', $data);
            static::$app->pushRemote('app', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), false);
        }

        TraceSqlSchema::create(static::$app, $event);
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
