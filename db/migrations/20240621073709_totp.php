<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 动态口令
 */
final class Totp extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        if (!$this->hasTable('cn_totp')) {
            $sql = "CREATE TABLE IF NOT EXISTS `cn_totp` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(200) NOT NULL COMMENT '名称',
  `secret` varchar(128) NOT NULL COMMENT '密钥',
  `issuer` varchar(200) NOT NULL DEFAULT '' COMMENT '发行方',
  `t0` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '开始纪元',
  `t1` smallint(5) UNSIGNED NOT NULL DEFAULT '30' COMMENT '时间周期',
  `algo` varchar(50) NOT NULL DEFAULT 'sha1' COMMENT '散列算法',
  `digits` tinyint(3) UNSIGNED NOT NULL DEFAULT '6' COMMENT '令牌位数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='动态令牌'";
            $this->execute($sql);
        }
    }
}
