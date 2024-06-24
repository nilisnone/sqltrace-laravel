<?php

return [
    /**
     * 是否开启功能
     */
    'enable' => env('SOI_ENABLE', false),
    /**
     * 是否开启 debug_trace
     */
    'enable_backtrace' => env('SOI_ENABLE_BACKTRACE', false),
    /**
     * 日志文件地址
     */
    'log_file' => env('SOI_LOG_FILE', '/tmp/sql.log'),
    /**
     * 最大记录源码行数
     */
    'max_context_line' => env('SOI_MAX_CONTEXT_LINE', 0),
    /**
     * app_uuid使用$_SERVER中的变量
     */
    'app_uuid_variable' => env('SOI_UID_VARIABLE', ''),
    'enable_statistic' => env('SOI_ENABLE_BACKTRACE', false),
];