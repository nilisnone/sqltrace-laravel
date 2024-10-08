<?php

namespace SQLTrace;

use SplFileObject;
use Exception;

class TraceContextSchema
{
    protected $context = [];

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

    protected function getSourceCode(string $path, int $lineNumber): array
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

        $target = max(0, ($lineNumber - ($maxLinesToFetch + 1)));
        $currentLineNumber = $target + 1;

        try {
            $file = new SplFileObject($path);
            $file->seek($target);

            while (!$file->eof()) {
                $line = $file->current();
                $line = str_replace(["\r\n", "\r", "\n"], "", $line);

                if ($currentLineNumber === $lineNumber) {
                    $frame['context_line'] = $line;
                } elseif ($currentLineNumber < $lineNumber) {
                    $frame['pre_context'][] = $line;
                } elseif ($currentLineNumber > $lineNumber) {
                    $frame['post_context'][] = $line;
                }

                ++$currentLineNumber;
                if ($currentLineNumber > $lineNumber + $maxLinesToFetch) {
                    break;
                }

                $file->next();
            }
        } catch (Exception $exception) {
        }

        return $frame;
    }
}
