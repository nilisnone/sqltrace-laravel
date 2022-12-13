<?php

namespace SQLTrace;

use Illuminate\Container\Container;

class Log
{
    protected static $instance;

    public static function getInstance(): Log
    {
        if (!static::$instance) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    public $log;
    public $traceLog;

    public function __construct()
    {
        $logfile = Container::getInstance()['config']['SQLTrace']['log_file'];
        $enableTrace = Container::getInstance()['config']['SQLTrace']['enable_backtrace'];
        if ($logfile) {
            $path = pathinfo($logfile);
            $baseFile = ($path['dirname'] ?? '') . DIRECTORY_SEPARATOR . ($path['filename'] ?? '');
            $logfile = $baseFile . '.' . date('Ymd') . '.log';
            $traceFile = $baseFile . '-trace' . date('Ymd') . '.log';
            if (!$logfile) {
                file_put_contents($logfile, '', FILE_APPEND);
            }
            if ($logfile) {
                $this->log = @fopen($logfile, 'ab+');
            }
            if ($enableTrace) {
                if (!$traceFile) {
                    file_put_contents($traceFile, '', FILE_APPEND);
                }
                if ($traceFile) {
                    $this->traceLog = @fopen($traceFile, 'ab+');
                }
            }
        }
    }

    protected static $reqId;

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

    protected static function getDefaultContext(array &$context): void
    {
        $context['@req_id'] = static::getReqId();
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $context['@may_file'] = sprintf(
            '%s@%d',
            $trace[1]['file'] ?? '',
            $trace[1]['line'] ?? ''
        );
    }

    public function info(string $msg, array $context = [], bool $debug = false): void
    {
        static::getDefaultContext($context);
        $context['msg'] = $msg;
        $context['@timestamp'] = date('Y-m-d H:i:s') . strstr(microtime(true), '.');
        if ($debug) {
            if ($this->traceLog) {
                fwrite($this->log, json_encode($context) . PHP_EOL);
            }
        } else if ($this->log) {
            fwrite($this->log, json_encode($context) . PHP_EOL);
        }
    }

    public function __destruct()
    {
        fclose($this->log);
    }
}