<?php

namespace SQLTrace;

use Exception;
use SplFileObject;

class TraceContextSchema
{
    protected ?TraceSqlSchema $sql = null;

    public function __construct(TraceSqlSchema $sql)
    {
        $this->sql = $sql;
    }

    public function __destruct()
    {
        unset($this->sql);
    }

    public static function create(TraceSqlSchema $sql, array $traces)
    {
        $context = new self($sql);
        $maxLinesToFetch = $sql->app()->getMaxContentLine();
        foreach ($traces as $trace) {
            $source_code = $context->getSourceCode($trace['file'] ?? '', $trace['line'] ?? 0, $maxLinesToFetch);
            $traceContext = [
                'sql_uuid' => $sql->getSqlUUID(),
                'file' => $trace['file'] ?? '',
                'line' => $trace['line'] ?? '',
                'class' => $trace['class'] ?? '',
                'context_before' => $source_code['context_before'],
                'context_current' => $source_code['context_current'],
                'context_after' => $source_code['context_after'],
            ];
            Log::getInstance($sql->app())->info('trace-context', $traceContext, true);
            $sql->app()->addPushTrace(json_encode($traceContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $sql->app()->startPush();
        }
    }

    protected function getSourceCode(string $path, int $line, int $maxContentLine): array
    {
        if (@!is_readable($path) || !is_file($path)) {
            return [];
        }

        $frame = [
            'context_before' => [],
            'context_current' => [],
            'context_after' => [],
        ];
        if (!$maxContentLine) {
            return $frame;
        }

        $target = max(0, ($line - ($maxContentLine + 1)));
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
