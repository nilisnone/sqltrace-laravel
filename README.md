

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
