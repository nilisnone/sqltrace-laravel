<?php

return [
    /**
     * 是否开启分析统计模式
     */
    'enable_analytic' => env('SQL_TRACE_ANALYTIC', false),
    /**
     * 是否记录本地日志
     */
    'enable_log' => env('SQL_TRACE_ENABLE_LOG', true),
    /**
     * 是否开启 debug_trace
     */
    'enable_backtrace' => env('SQL_TRACE_ENABLE_BACKTRACE', false),
    /**
     * 如果开启本地日志，日志文件地址
     */
    'log_file' => env('SQL_TRACE_LOG_FILE', '/tmp/sql.log'),
    /**
     * DSN地址，如果开启，上传数据到远程
     */
    'dsn' => env('SQL_TRACE_DSN', ''),
    /**
     * 忽略文件夹
     */
    'ignore_folder' => env('SQL_TRACE_IGNORE_FOLDER', 'vendor'),
    /**
     * 开启分析模式，需要使用redis配置信息
     */
    'redis' => [
        'host' => env('SQL_TRACE_REDIS_HOST', '127.0.0.1'),
        'port' => env('SQL_TRACE_REDIS_PORT', 6379),
        'password' => env('SQL_TRACE_REDIS_PASSWORD', ''),
    ],
    /**
     * 最大记录源码行数
     */
    'max_context_line' => env('SQL_TRACE_MAX_CONTEXT_LINE', 5),
];