START TRANSACTION;

CREATE TABLE `cn_client` (
  `id` int(10) UNSIGNED NOT NULL COMMENT '主键',
  `brand` varchar(50) NOT NULL COMMENT '下载器品牌',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `hostname` varchar(200) NOT NULL COMMENT '协议主机',
  `endpoint` varchar(100) NOT NULL COMMENT '接入点',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(80) NOT NULL COMMENT '密码',
  `watch_path` varchar(200) NOT NULL DEFAULT '' COMMENT '监控目录',
  `save_path` varchar(200) NOT NULL DEFAULT '' COMMENT '资源保存路径',
  `torrent_path` varchar(200) NOT NULL DEFAULT '' COMMENT '种子目录',
  `root_folder` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建多文件子目录',
  `is_debug` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '调试',
  `is_default` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '默认',
  `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户端';

CREATE TABLE `cn_folder` (
  `folder_id` int(10) UNSIGNED NOT NULL COMMENT '主键',
  `folder_alias` varchar(100) NOT NULL COMMENT '目录别名',
  `folder_value` varchar(300) NOT NULL COMMENT '数据目录',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据目录';

CREATE TABLE `cn_reseed` (
  `reseed_id` int(10) UNSIGNED NOT NULL COMMENT '主键',
  `client_id` int(10) UNSIGNED NOT NULL COMMENT '客户端ID',
  `site` varchar(30) NOT NULL COMMENT '站点名字',
  `sid` int(10) UNSIGNED NOT NULL COMMENT '站点ID',
  `torrent_id` int(10) UNSIGNED NOT NULL COMMENT '种子ID',
  `group_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '种子分组ID',
  `info_hash` varchar(80) NOT NULL DEFAULT '' COMMENT '种子infohash',
  `directory` varchar(900) NOT NULL DEFAULT '' COMMENT '目标文件夹',
  `dispatch_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '调度时间',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
  `message` varchar(300) NOT NULL DEFAULT '' COMMENT '异常信息',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自动辅种';

CREATE TABLE `cn_sites` (
  `id` mediumint(5) UNSIGNED NOT NULL COMMENT '主键',
  `sid` int(10) UNSIGNED NOT NULL COMMENT '站点ID',
  `site` varchar(30) NOT NULL COMMENT '站点名称',
  `nickname` varchar(60) NOT NULL COMMENT '昵称',
  `base_url` varchar(100) NOT NULL COMMENT '域名',
  `mirror` varchar(150) NOT NULL DEFAULT '' COMMENT '镜像域名',
  `cookie` varchar(2000) DEFAULT '' COMMENT 'cookie',
  `download_page` varchar(200) NOT NULL DEFAULT '' COMMENT '下载种子页',
  `details_page` varchar(200) NOT NULL DEFAULT '' COMMENT '详情页',
  `reseed_check` varchar(200) NOT NULL DEFAULT '' COMMENT '检查项',
  `is_https` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '可选：0http，1https，2http+https',
  `cookie_required` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'cookie必须',
  `options` longtext COMMENT '用户配置值',
  `disabled` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='站点配置';

CREATE TABLE `cn_transfer` (
  `transfer_id` int(10) UNSIGNED NOT NULL COMMENT '主键',
  `from_client_id` int(10) UNSIGNED NOT NULL COMMENT '来源',
  `to_client_id` int(10) UNSIGNED NOT NULL COMMENT '目标',
  `info_hash` varchar(80) NOT NULL DEFAULT '' COMMENT '种子infohash',
  `directory` varchar(900) NOT NULL DEFAULT '' COMMENT '转换前目录',
  `convert_directory` varchar(900) NOT NULL DEFAULT '' COMMENT '转换后目录',
  `torrent_file` varchar(900) NOT NULL DEFAULT '' COMMENT '种子文件路径',
  `message` varchar(300) NOT NULL DEFAULT '' COMMENT '结果消息',
  `state` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
  `last_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后操作',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自动转移';


ALTER TABLE `cn_client`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hostname` (`hostname`,`endpoint`),
  ADD KEY `brand` (`brand`);

ALTER TABLE `cn_folder`
  ADD PRIMARY KEY (`folder_id`),
  ADD UNIQUE KEY `folder_alias` (`folder_alias`);

ALTER TABLE `cn_reseed`
  ADD PRIMARY KEY (`reseed_id`),
  ADD KEY `reseed_client_id` (`client_id`),
  ADD KEY `reseed_sid` (`sid`),
  ADD KEY `dispatch_time` (`dispatch_time`),
  ADD KEY `status` (`status`),
  ADD KEY `info_hash` (`info_hash`),
  ADD KEY `site` (`site`);

ALTER TABLE `cn_sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `site` (`site`),
  ADD UNIQUE KEY `sid` (`sid`);

ALTER TABLE `cn_transfer`
  ADD PRIMARY KEY (`transfer_id`),
  ADD KEY `from_client_id` (`from_client_id`),
  ADD KEY `to_client_id` (`to_client_id`),
  ADD KEY `info_hash` (`info_hash`),
  ADD KEY `state` (`state`);


ALTER TABLE `cn_client`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

ALTER TABLE `cn_folder`
  MODIFY `folder_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

ALTER TABLE `cn_reseed`
  MODIFY `reseed_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

ALTER TABLE `cn_sites`
  MODIFY `id` mediumint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

ALTER TABLE `cn_transfer`
  MODIFY `transfer_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';


ALTER TABLE `cn_reseed`
  ADD CONSTRAINT `reseed_client_id` FOREIGN KEY (`client_id`) REFERENCES `cn_client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reseed_sid` FOREIGN KEY (`sid`) REFERENCES `cn_sites` (`sid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;