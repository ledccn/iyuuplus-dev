<?php

namespace Ledc\Crypt\Contracts;

/**
 * 使用对称密钥加密与解密接口
 */
interface Aes
{
    /**
     * 加密
     * @param string $plaintext
     * @param string $key
     * @param string|null $iv
     * @return string
     */
    public static function encrypt(string $plaintext, string $key, ?string $iv = null): string;

    /**
     * 解密
     * @param string $ciphertext
     * @param string $key
     * @param string|null $iv
     * @return string
     */
    public static function decrypt(string $ciphertext, string $key, ?string $iv = null): string;
}
