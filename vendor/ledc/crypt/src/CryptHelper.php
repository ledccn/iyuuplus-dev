<?php

namespace Ledc\Crypt;

use InvalidArgumentException;

/**
 * 助手类
 */
class CryptHelper
{
    /**
     * 对称加密
     * @param array $data 数据报文
     * @param string $key 对称密钥
     * @param string $cipher 对称算法
     * @param string $hmac_algo 散列算法
     * @param int $expires_in 数据包有效时间（单位秒）
     * @return array
     */
    public static function aesEncrypt(array $data, string $key, string $cipher, string $hmac_algo, int $expires_in): array
    {
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $noncestr = bin2hex(openssl_random_pseudo_bytes(8));
        $timestamp = time();

        // 附加数据
        $addReq = ['_noncestr' => $noncestr];
        $realData = array_merge($addReq, $data);
        $plaintext = json_encode($realData);

        // 加密
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        if (false === $ciphertext_raw) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Encrypt AES CBC error.');
        }

        $payload = base64_encode(json_encode([
            'iv' => base64_encode($iv),
            'data' => base64_encode($ciphertext_raw),
            'timestamp' => $timestamp,
            'expires_in' => $expires_in
        ]));

        // 使用 HMAC 方法生成带有密钥的散列值
        $hmac = hash_hmac($hmac_algo, $payload, $key);

        return [$payload, $hmac];
    }

    /**
     * 对称解密
     * @param string $payload 有效载荷
     * @param string $key 对称密钥
     * @param string $cipher 对称算法
     * @return array
     */
    public static function aesDecrypt(string $payload, string $key, string $cipher): array
    {
        $_payload = json_decode(base64_decode($payload), true);
        $iv = base64_decode($_payload['iv']);
        $ciphertext_raw = base64_decode($_payload['data']);
        $timestamp = $_payload['timestamp'];
        $expires_in = $_payload['expires_in'];

        // 验证时间戳
        if ($expires_in < abs(time() - $timestamp)) {
            throw new InvalidArgumentException('时间戳验证失败，误差超过' . $expires_in . '秒');
        }

        // 解密
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        if (false === $original_plaintext) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Decrypt AES CBC error.');
        }

        return json_decode($original_plaintext, true);
    }
}