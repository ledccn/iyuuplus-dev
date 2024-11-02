<?php

namespace Ledc\Crypt;

use ErrorException;
use InvalidArgumentException;
use Throwable;

/**
 * 私钥加签/公钥验签，对称加密解密
 */
readonly class RsaCrypt
{
    /**
     * 构造函数
     * @param string $privateKey 非对称密钥-私钥（文件路径）
     * @param string $publicKey 非对称密钥-公钥（文件路径）
     * @param string $key 对称密钥
     * @param string $cipher 对称算法
     * @param string $hmac_algo 散列算法
     * @param int $openssl_sign_algorithm 非对称私钥签名算法与非对称公钥验签算法（OPENSSL_ALGO_SHA256 或 OPENSSL_ALGO_SHA1）
     * @param int $expires_in 数据包有效时间（单位秒）
     * @throws ErrorException
     */
    public function __construct(
        protected string $privateKey,
        protected string $publicKey,
        protected string $key,
        protected string $cipher = 'aes-128-cbc',
        protected string $hmac_algo = 'sha256',
        protected int    $openssl_sign_algorithm = OPENSSL_ALGO_SHA1,
        protected int    $expires_in = 30
    )
    {
        if (!is_file($this->privateKey) || !is_readable($this->privateKey)) {
            throw new ErrorException("Private key file not found or not readable");
        }

        if (!is_file($this->publicKey) || !is_readable($this->publicKey)) {
            throw new ErrorException("Public key file not found or not readable");
        }

        if (!in_array($this->hmac_algo, hash_hmac_algos(), true)) {
            throw new InvalidArgumentException('无效的散列算法');
        }
    }

    /**
     * 对称加密 & RSA非对称私钥签名
     * @param array $data 数据包
     * @return array
     * @throws ErrorException
     */
    public function encrypt(array $data): array
    {
        try {
            [$payload, $hmac] = CryptHelper::aesEncrypt($data, $this->getKey(), $this->getCipher(), $this->getHmacAlgo(), $this->getExpiresIn());

            $signature = $this->opensslSignature($hmac);

            return compact('payload', 'signature');
        } catch (Throwable $throwable) {
            throw new ErrorException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * 对称解密 & RSA非对称公钥验签
     * @param string $payload 有效载荷
     * @param string $signature 签名
     * @return array 数据包
     * @throws ErrorException
     */
    public function decrypt(string $payload, string $signature): array
    {
        try {
            $key = $this->getKey();

            // 使用 HMAC 方法生成带有密钥的散列值
            $hmac = hash_hmac($this->getHmacAlgo(), $payload, $key);
            // 验签
            if (!$this->opensslVerify($hmac, $signature)) {
                throw new InvalidArgumentException('签名验证失败');
            }

            return CryptHelper::aesDecrypt($payload, $key, $this->getCipher());
        } catch (Throwable $throwable) {
            throw new ErrorException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * 非对称私钥签名
     * @param string $data
     * @return string
     */
    public function opensslSignature(string $data): string
    {
        if (!openssl_sign($data, $signature, openssl_pkey_get_private(file_get_contents($this->getPrivateKey())), $this->getOpensslSignAlgorithm())) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'openssl_sign error.');
        }
        return base64_encode($signature);
    }

    /**
     * 非对称公钥验签
     * @param string $data
     * @param string $signature
     * @return bool
     * @throws ErrorException
     */
    public function opensslVerify(string $data, string $signature): bool
    {
        if (1 === openssl_verify($data, base64_decode($signature), openssl_pkey_get_public(file_get_contents($this->getPublicKey())), $this->getOpensslSignAlgorithm())) {
            return true;
        }
        throw new ErrorException('非对称公钥验签失败');
    }

    /**
     * 获取对称密钥
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * 非对称私钥签名算法与非对称公钥验签算法
     * @return int
     */
    public function getOpensslSignAlgorithm(): int
    {
        return $this->openssl_sign_algorithm;
    }

    /**
     * 获取对称算法
     * @return string
     */
    public function getCipher(): string
    {
        return $this->cipher;
    }

    /**
     * 获取数据包有效时间
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expires_in;
    }

    /**
     * 获取散列算法
     * @return string
     */
    public function getHmacAlgo(): string
    {
        return $this->hmac_algo;
    }
}