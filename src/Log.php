<?php

namespace SQLTrace;

use Illuminate\Container\Container;

class Log
{
    protected static ?Log $instance = null;
    public $log;
    public $traceLog;
    protected static string $reqId = '';

    public static function getInstance(): Log
    {
        if (!static::$instance) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    public function __construct()
    {
        $logfile = Container::getInstance()['config']['SQLTrace']['log_file'];
        $enableTrace = Container::getInstance()['config']['SQLTrace']['enable_backtrace'];
        if ($logfile) {
            $path = pathinfo($logfile);
            $baseFile = ($path['dirname'] ?? '') . DIRECTORY_SEPARATOR . ($path['filename'] ?? '');
            $logfile = $baseFile . '.' . date('Ymd') . '.log';
            $traceFile = $baseFile . '.trace.' . date('Ymd') . '.log';
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
        $context['_req_id'] = static::getReqId();
        $context['_timestamp'] = date('Y-m-d H:i:s') . strstr(microtime(true), '.');
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
        fclose($this->log);
    }
}