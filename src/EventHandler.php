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
    }

    public function subscribe(): void
    {
        $this->events->listen(QueryExecuted::class, [$this, 'queryExecuted']);
    }

    public function queryExecuted(QueryExecuted $query): void
    {
        TraceAppSchema::create($query, $this->config);
    }
}
