<?php

namespace SQLTrace;

use Exception;
use SplFileObject;

class TraceContextSchema
{
    protected ?TraceSqlSchema $sql = null;

    protected array $context = [];

    public function __construct(TraceSqlSchema $sql)
    {
        $this->sql = $sql;
    }

    public static function create(TraceSqlSchema $sql, array $traces): TraceContextSchema
    {
        $context = new self($sql);
        foreach ($traces as $trace) {
            $source_code = $context->getSourceCode($trace['file'] ?? '', $trace['line'] ?? 0);
            $traceContext = [
                'sql_uuid' => $sql->getSqlUUID(),
                'file' => $trace['file'] ?? '',
                'line' => $trace['line'] ?? '',
                'class' => $trace['class'] ?? '',
                'context_before' => $source_code['context_before'],
                'context_current' => $source_code['context_current'],
                'context_after' => $source_code['context_after'],
            ];
            Log::getInstance()->info('trace-context', $traceContext, true);
            $sql->app()->addPushTrace(json_encode($traceContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $sql->app()->startPush();
        }

        return $context;
    }

    protected function getSourceCode(string $path, int $line): array
    {
        if (@!is_readable($path) || !is_file($path)) {
            return [];
        }
        $maxLinesToFetch = $this->sql->app()->getMaxContentLine();

        $frame = [
            'context_before' => [],
            'context_current' => [],
            'context_after' => [],
        ];
        if (!$maxLinesToFetch) {
            return $frame;
        }

        $target = max(0, ($line - ($maxLinesToFetch + 1)));
        $currLineNum = $target + 1;

        try {
            $file = new SplFileObject($path);
            $file->seek($target);

            while (!$file->eof()) {
                $currLine = $file->current();
                $currLine = str_replace(["\r\n", "\r", "\n"], "", $currLine);

                if ($currLineNum == $line) {
                    $frame['context_current'][$currLineNum] = $currLine;
                } elseif ($currLineNum < $line) {
                    $frame['context_before'][$currLineNum] = $currLine;
                } elseif ($currLineNum > $line) {
                    $frame['context_after'][$currLineNum] = $currLine;
                }

                ++$currLineNum;
                if ($currLineNum > $line + $maxLinesToFetch) {
                    break;
                }

                $file->next();
            }
        } catch (Exception $exception) {
        }

        return $frame;
    }
}
