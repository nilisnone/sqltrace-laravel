<?php

return [
    /**
     * 是否开启功能
     */
    'enable' => env('SOI_ENABLE', false),
    /**
     * 是否开启分析统计模式
     */
    'enable_analytic' => env('SOI_ANALYTIC', false),
    /**
     * 是否记录本地日志
     */
    'enable_log' => env('SOI_ENABLE_LOG', true),
    /**
     * 是否开启 debug_trace
     */
    'enable_backtrace' => env('SOI_ENABLE_BACKTRACE', false),
    /**
     * 如果开启本地日志，日志文件地址
     */
    'log_file' => env('SOI_LOG_FILE', '/tmp/sql.log'),
    /**
     * DSN地址，如果开启，上传数据到远程
     */
    'dsn' => env('SOI_DSN', ''),
    /**
     * 开启分析模式，需要使用redis配置信息
     */
    'redis' => [
        'host' => env('SOI_REDIS_HOST', '127.0.0.1'),
        'port' => env('SOI_REDIS_PORT', 6379),
        'password' => env('SOI_REDIS_PASSWORD', ''),
    ],
    /**
     * 最大记录源码行数
     */
    'max_context_line' => env('SOI_MAX_CONTEXT_LINE', 20),
    /**
     * app_uuid使用$_SERVER中的变量
     */
    'app_uuid_variable' => env('SOI_UID_VARIABLE', '')
];