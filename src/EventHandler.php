<?php

namespace SQLTrace;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;

class EventHandler
{
    private ?Dispatcher $events = null;

    private array $config = [];

    private static string $errLog = '';

    protected ?TraceAppSchema $app = null;

    /**
     * EventHandler constructor.
     *
     * @param Dispatcher $events
     * @param array $config
     */
    public function __construct(Dispatcher $events, array $config)
    {
        $this->events = $events;
        $this->config = $config;
        $this->app = TraceAppSchema::getInstance($this->config);
        static::$errLog = sys_get_temp_dir() . '/sqltrace-error.log';
    }

    public function subscribe(): void
    {
        $this->events->listen(QueryExecuted::class, [$this, 'queryExecuted']);
    }

    public function queryExecuted(QueryExecuted $query): void
    {
        try {
            if ($this->app->getSQLTimeThreshold() <= $query->time) {
                $this->app->create($query);
            }
        } catch (Exception $e) {
            @file_put_contents(
                static::$errLog,
                date('Y-m-d H:i:s') . ' [' . $e->getMessage() . '] at ' . $e->getFile() . '@' . $e->getLine() . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    public function __destruct()
    {
        unset($this->config, $this->app);
    }
}
