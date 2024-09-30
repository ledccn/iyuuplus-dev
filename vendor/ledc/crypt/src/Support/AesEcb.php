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
 * 对称加密解密算法：aes-256-ecb
 */
class AesEcb implements Aes
{
    /**
     * @throws InvalidArgumentException
     */
    public static function encrypt(string $plaintext, string $key, ?string $iv = null): string
    {
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-ecb', $key, OPENSSL_RAW_DATA, (string)$iv);

        if ($ciphertext === false) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Encrypt AES ECB failed.');
        }

        return base64_encode($ciphertext);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function decrypt(string $ciphertext, string $key, ?string $iv = null): string
    {
        $plaintext = openssl_decrypt(
            base64_decode($ciphertext, true) ?: '',
            'aes-256-ecb',
            $key,
            OPENSSL_RAW_DATA,
            (string)$iv
        );

        if ($plaintext === false) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Decrypt AES ECB failed.');
        }

        return $plaintext;
    }
}
