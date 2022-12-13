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