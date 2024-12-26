<?php

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

    protected static ?TraceAppSchema $instance = null;

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
     * @return TraceAppSchema|null
     */
    public static function create(QueryExecuted $event, array $config): ?TraceAppSchema
    {
        if (null === static::$instance) {
            static::$instance = new TraceAppSchema($config);
            Log::getInstance()->info('trace-app', static::$instance->toArray());
        }
        TraceSqlSchema::create(static::$instance->app_uuid, $event);
        return static::$instance;
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
