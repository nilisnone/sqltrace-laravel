<?php

return [
    /**
     * 是否开启功能
     */
    'enable' => env('SOI_ENABLE', false),
    /**
     * 是否开启 backtrace 记录
     */
    'enable_backtrace' => env('SOI_ENABLE_BACKTRACE', false),
    /**
     * 日志文件地址
     */
    'log_file' => env('SOI_LOG_FILE', sys_get_temp_dir() . '/sqltrace.log'),
    /**
     * 最大记录源码行数
     */
    'max_context_line' => env('SOI_MAX_CONTEXT_LINE', 20),
    /**
     * app_uuid 使用 $_SERVER 中的变量
     */
    'app_uuid_variable' => env('SOI_UID_VARIABLE', ''),
    /**
     * sql_time_threshold, 当不等于 -1 时，sql 大于设置的阈值才会记录
     */
    'sql_time_threshold' => env('SOI_TIME_THRESHOLD', 0),
    'sql_time_threshold_cli' => env('SOI_TIME_THRESHOLD_CLI', 0),
];
