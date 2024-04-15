<?php

namespace app\admin\services\reseed;

/**
 * 辅种结束后通知
 */
class NotifyData
{
    /**
     * 做种的哈希总数
     * @var int
     */
    public int $hashCount = 0;
    /**
     * 接口返回的可辅种数
     * @var int
     */
    public int $reseedCount = 0;
    /**
     * 跳过辅种
     * - 客户端无配置、未勾选
     * @var int
     */
    public int $reseedSkip = 0;
    /**
     * 与客户端现有种子重复
     * @var int
     */
    public int $reseedRepeat = 0;
    /**
     * 当前种子上次辅种已成功添加
     * @var int
     */
    public int $reseedPass = 0;
    /**
     * 辅种成功数
     * @var int
     */
    public int $reseedSuccess = 0;
    /**
     * 辅种失败数
     * @var int
     */
    public int $reseedFail = 0;

    /**
     * 构造函数
     * @param int $supportSitesCount 支持的站点总数
     * @param int $userSitesCount 用户勾选的辅种站点数
     */
    public function __construct(public readonly int $supportSitesCount, public readonly int $userSitesCount)
    {

    }
}
