# 介绍

sqltrace-laravel是一个基于Laravel框架的开源工具，旨在提供数据库SQL语句跟踪和分析的功能。该工具可以跟踪应用程序中执行的SQL查询，记录每个查询的详细信息（如查询语句、执行时间、绑定参数等），并可通过可视化界面分析和展示这些信息，以帮助开发人员进行性能优化和调试。

具体来说，sqltrace-laravel通过Laravel框架提供的事件系统，拦截应用程序中执行的数据库查询，并将这些查询的详细信息保存到本地或远程的数据库中。同时，该工具还提供了可视化的Web界面，用于查看和分析这些查询的信息，包括按时间、请求、查询类型等多种维度的查询统计和分析，以及查询详细信息的查看和调试功能。

总的来说，sqltrace-laravel是一个非常有用的工具，可以帮助开发人员快速定位和解决应用程序中的数据库性能问题。同时，它还可以提供对数据库查询行为的深入理解，以便进行更好的优化和调优。

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

# 版本

2024.2

- [x] 新增 SQL 指纹 (fingerprint) 标记，基于指纹可以统计 N+1 循环查询问题
- [x] 增加 SQL 链路最多支持 5 层，解决在 SQL 下沉到模型的设计模式中，相同方法调用层级太多，没办法快速定位原始调用位置问题
