<?php

namespace SQLTrace;

use SplFileObject;
use Exception;

class TraceContextSchema
{
    protected array $context = [];

    public static function create(string $sql_uuid, array $traces): TraceContextSchema
    {
        $context = new self();
        foreach ($traces as $trace) {
            $source_code = $context->getSourceCode($trace['file'] ?? '', $trace['line'] ?? 0);
            Log::getInstance()->info('trace-context', [
                'sql_uuid' => $sql_uuid,
                'file' => $trace['file'] ?? '',
                'line' => $trace['line'] ?? '',
                'class' => $trace['class'] ?? '',
                'context_line' => $source_code['context_line'],
            ], true);
        }

        return $context;
    }

    protected function getSourceCode(string $path, int $line): array
    {
        if (@!is_readable($path) || !is_file($path)) {
            return [];
        }
        $config = app()['config']['SQLTrace'];
        $maxLinesToFetch = $config['max_context_line'];

        $frame = [
            'pre_context' => [],
            'context_line' => '',
            'post_context' => [],
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
                    $frame['context_line'] = $currLine;
                } elseif ($currLineNum < $line) {
                    $frame['pre_context'][] = $currLine;
                } elseif ($currLineNum > $line) {
                    $frame['post_context'][] = $currLine;
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
