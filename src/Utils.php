<?php

namespace SQLTrace;

class Utils
{
    protected static string $g_uuid = '';

    /**
     * 全局UUID
     *
     * @return string
     */
    public static function static_uuid(): string
    {
        if (!static::$g_uuid) {
            static::$g_uuid = $_SERVER['HTTP_TRACE_ID'] ?? ($_GET['trace_id'] ?? static::uuid());
        }
        return static::$g_uuid;
    }

    /**
     * UUID
     *
     * @return string
     */
    public static function uuid(): string
    {
        if (function_exists('uuid_create')) {
            $uuid = uuid_create(UUID_TYPE_RANDOM);
            $uuid = str_replace('-', '', $uuid);
        } else {
            $uuid = md5(uniqid(sprintf('%f%d', microtime(true), getmypid()), true));
        }
        return $uuid;
    }

    /**
     * 毫秒时间戳
     *
     * @return string
     */
    public static function get_datetime_ms(): string
    {
        $ms = str_pad((int)(100000 * (microtime(true) - time())), 6, 0, STR_PAD_LEFT);
        return date('Y-m-d') . 'T' . date('H:i:s.') . $ms;
    }

}