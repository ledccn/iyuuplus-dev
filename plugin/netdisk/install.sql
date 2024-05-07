SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `io_share`;
CREATE TABLE `io_share` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '分享名称',
    `share_hash` varchar(50) NOT NULL DEFAULT '' COMMENT 'hash',
    `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分享用户id',
    `source_id` int(11) NOT NULL DEFAULT '0' COMMENT '文档数据id',
    `is_folder` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否是文件夹',
    `file_id` int(11) NOT NULL DEFAULT '0' COMMENT '附件id',
    `share_password` varchar(10) NOT NULL DEFAULT '' COMMENT '访问密码,为空则无密码',
    `expire_at` datetime DEFAULT NULL COMMENT '到期时间',
    `view_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '预览次数',
    `download_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下载次数',
    `created_at` datetime DEFAULT NULL COMMENT '创建时间',
    `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
    `weight` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序号',
    `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
    PRIMARY KEY (`id`),
    KEY `admin_id` (`admin_id`),
    KEY `source_id` (`source_id`),
    KEY `file_id` (`file_id`),
    KEY `shareHash` (`share_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分享数据表';


SET NAMES utf8mb4;

DROP TABLE IF EXISTS `io_source`;
CREATE TABLE `io_source` (
     `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
     `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '创建人ID',
     `title` varchar(255) DEFAULT '' COMMENT '文件名',
     `hash` varchar(50) NOT NULL DEFAULT '' COMMENT 'hash',
     `ext` varchar(50) DEFAULT '' COMMENT '扩展名',
     `is_folder` tinyint(4) DEFAULT '0' COMMENT '是否是文件夹',
     `pid` int(11) DEFAULT '0' COMMENT '父级ID',
     `pids` varchar(255) DEFAULT '' COMMENT '所有父级',
     `file_id` int(11) DEFAULT '0' COMMENT '附件ID',
     `file_size` int(11) DEFAULT '0' COMMENT '文件大小',
     `created_at` datetime DEFAULT NULL COMMENT '创建时间',
     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
     `weight` int(11) DEFAULT '0' COMMENT '排序号',
     `status` tinyint(4) DEFAULT '1' COMMENT '状态',
     PRIMARY KEY (`id`),
     KEY `pid` (`pid`),
     KEY `admin_id` (`admin_id`),
     KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文档数据表';
