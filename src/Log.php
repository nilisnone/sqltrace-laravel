<?php

namespace SQLTrace;

use Illuminate\Container\Container;

class Log
{
    protected static ?Log $instance = null;
    public $log;
    public $traceLog;
    protected static string $reqId = '';

    public static function getInstance(?TraceAppSchema $app = null): Log
    {
        if (!static::$instance) {
            static::$instance = new self($app);
        }
        return static::$instance;
    }

    public function __construct(TraceAppSchema $app)
    {
        $logfile = $app->getLogFile();
        if ($logfile) {
            $path = pathinfo($logfile);
            $baseFile = ($path['dirname'] ?? '') . DIRECTORY_SEPARATOR . ($path['filename'] ?? '');
            $logfile = $baseFile . '.' . date('Ymd') . '.log';
            $traceFile = $baseFile . '.trace.' . date('Ymd') . '.log';
            @touch($logfile);
            if ($logfile) {
                $this->log = @fopen($logfile, 'ab+');
            }
            if ($app->enableBacktrace()) {
                @touch($traceFile);
                if ($traceFile) {
                    $this->traceLog = @fopen($traceFile, 'ab+');
                }
            }
        }
    }

    public static function getReqId(): string
    {
        global $request_id_seq;
        if (is_null($request_id_seq)) {
            $request_id_seq = 0;
        } else {
            $request_id_seq++;
        }
        if (empty(static::$reqId) && !empty($_SERVER['HTTP_X_REQ_ID'])) {
            static::$reqId = $_SERVER['HTTP_X_REQ_ID'] . '-' . $request_id_seq;
        }
        if (empty(static::$reqId)) {
            $_SERVER['HTTP_X_REQ_ID'] = Utils::uuid();
            static::$reqId = $_SERVER['HTTP_X_REQ_ID'] . '-' . $request_id_seq;
        }
        return preg_replace('/(\d+)$/', $request_id_seq, static::$reqId);
    }

    public function info(string $msg, array $context = [], bool $debug = false): void
    {
        $context['msg'] = $msg;
        $context['req_id'] = static::getReqId();
        if ($debug) {
            if ($this->traceLog) {
                fwrite($this->traceLog, json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
            }
        } else if ($this->log) {
            fwrite($this->log, json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
        }
    }

    public function __destruct()
    {
        $this->log && fclose($this->log);
        $this->traceLog && fclose($this->log);
        static::$instance = null;
    }
}