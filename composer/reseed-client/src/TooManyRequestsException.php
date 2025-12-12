<?php

namespace Iyuu\ReseedClient;

use Exception;

/**
 * 错误码429
 */
class TooManyRequestsException extends Exception
{
    /**
     * 重试时间
     * @var string
     */
    protected const string RETRY_AFTER = 'Retry-After';
    /**
     * 剩余次数
     * @var string
     */
    protected const string X_RATELIMIT_LIMIT = 'X-RateLimit-Limit';
    /**
     * 重置时间
     * @var string
     */
    protected const string X_RATELIMIT_RESET = 'X-RateLimit-Reset';
    /**
     * @var array
     */
    protected array $data = [];

    /**
     * 获取数据
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取重试时间
     * @return int
     */
    public function getRetryAfter(): int
    {
        return (int)($this->data[self::RETRY_AFTER] ?? 30);
    }

    /**
     * 获取剩余次数
     * @return int
     */
    public function getXRateLimitLimit(): int
    {
        return (int)($this->data[self::X_RATELIMIT_LIMIT] ?? 0);
    }

    /**
     * 获取重置时间
     * @return int
     */
    public function getXRateLimitReset(): int
    {
        return (int)($this->data[self::X_RATELIMIT_RESET] ?? (time() + 30));
    }
}
