<?php

namespace app\model;

/**
 * IYUU文档管理类
 */
class IyuuDocuments
{
    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return config("documents.{$key}", $default);
    }
}
