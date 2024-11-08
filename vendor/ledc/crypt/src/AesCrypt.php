<?php

namespace Ledc\Crypt;

use ErrorException;
use InvalidArgumentException;
use Throwable;

/**
 * 对称加密解密与加签验签
 */
readonly class AesCrypt
{
    /**
     * 构造函数
     * @param string $key 对称密钥
     * @param string $cipher 对称算法
     * @param string $hmac_algo 散列算法
     * @param int $expires_in 数据包有效时间（单位秒）
     */
    public function __construct(
        protected string $key,
        protected string $cipher = 'aes-128-cbc',
        protected string $hmac_algo = 'sha256',
        protected int    $expires_in = 30
    )
    {
        if (!in_array($this->hmac_algo, hash_hmac_algos(), true)) {
            throw new InvalidArgumentException('无效的散列算法');
        }
    }

    /**
     * 对称加密
     * @param array $data 数据包
     * @return array
     * @throws ErrorException
     */
    public function encrypt(array $data): array
    {
        try {
            [$payload, $hmac] = CryptHelper::aesEncrypt($data, $this->getKey(), $this->getCipher(), $this->getHmacAlgo(), $this->getExpiresIn());

            $signature = $hmac;

            return compact('payload', 'signature');
        } catch (Throwable $throwable) {
            throw new ErrorException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * 对称解密
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
            if (!hash_equals($hmac, $signature)) {
                throw new InvalidArgumentException('签名验证失败');
            }

            return CryptHelper::aesDecrypt($payload, $key, $this->getCipher());
        } catch (Throwable $throwable) {
            throw new ErrorException($throwable->getMessage(), $throwable->getCode());
        }
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
