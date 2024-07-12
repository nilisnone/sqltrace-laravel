<?php

namespace SQLTrace;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;

class EventHandler
{
    /**
     * @var Dispatcher
     */
    private $events;

    private $config;

    private static $errlog;

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
        static::$errlog = sys_get_temp_dir() . 'sqltrace_error.log';
    }

    public function subscribe(): void
    {
        $this->events->listen(QueryExecuted::class, [$this, 'queryExecuted']);
    }

    public function queryExecuted(QueryExecuted $query): void
    {
        try {
            TraceAppSchema::create($query, $this->config);
        } catch (\Exception $e) {
             @file_put_contents(static::$errlog, 'Got error [' . $e->getMessage() . '] at ' . $e->getFile() . '@' . $e->getLine());
        }
    }
}
