<?php

namespace Ledc\Crypt\Support;

use InvalidArgumentException;
use Ledc\Crypt\Contracts\Aes;
use function base64_decode;
use function base64_encode;
use function openssl_decrypt;
use function openssl_encrypt;
use function openssl_error_string;
use const OPENSSL_RAW_DATA;

/**
 * 对称加密解密算法：aes-256-gcm
 */
class AesGcm implements Aes
{
    public const BLOCK_SIZE = 16;

    /**
     * @throws InvalidArgumentException
     */
    public static function encrypt(string $plaintext, string $key, ?string $iv = null, string $aad = ''): string
    {
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            (string)$iv,
            $tag,
            $aad,
            self::BLOCK_SIZE
        );

        if ($ciphertext === false) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Encrypt failed');
        }

        return base64_encode($ciphertext . $tag);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function decrypt(string $ciphertext, string $key, ?string $iv = null, string $aad = ''): string
    {
        $ciphertext = base64_decode($ciphertext);

        $tag = substr($ciphertext, -self::BLOCK_SIZE);

        $ciphertext = substr($ciphertext, 0, -self::BLOCK_SIZE);

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, (string)$iv, $tag, $aad);

        if ($plaintext === false) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Decrypt failed');
        }

        return $plaintext;
    }
}
