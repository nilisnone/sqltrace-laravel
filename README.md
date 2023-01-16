# 介绍

大量依赖数据库的业务，可以通过记录生命周期内所有 SQL 及其 SQL 执行位置，来调试代码。

## 版本要求 

Laravel 版本 >= 6

PHP version >= 7.4


## 安装

```
composer require --dev nilisnone/sqltrace-laravel dev-master

php artisan package:discover
```

## 使用

1. 编辑 .env

```
SOI_ENABLE=true
SOI_LOG_FILE=/tmp/sql.log
```

2. 其他参数

### SOI_ENABLE_BACKTRACE

是否开启trace日志，默认 false， 如果开启，会额外新增一个 .trace.{Ymd}.log 文件

### SOI_MAX_CONTEXT_LINE

最多记录源码行数，默认 0，如果大于 0，会记录请求时源码位置

### SOI_UID_VARIABLE

app_uuid 使用 $_SERVER 中的 key 值，默认空，随机生成