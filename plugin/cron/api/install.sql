START TRANSACTION;

CREATE TABLE `cn_crontab`
(
    `crontab_id`        int(11) UNSIGNED     NOT NULL COMMENT '主键',
    `title`             varchar(100)         NOT NULL COMMENT '任务标题',
    `task_type`         tinyint(1) UNSIGNED  NOT NULL DEFAULT '0' COMMENT '任务类型',
    `crontab`           varchar(300)                  DEFAULT '' COMMENT '执行周期',
    `rule`              varchar(100)         NOT NULL COMMENT '执行表达式',
    `target`            varchar(300)         NOT NULL DEFAULT '' COMMENT '调用字符串',
    `parameter`         text COMMENT '调用参数',
    `running_count`     int(11) UNSIGNED     NOT NULL DEFAULT '0' COMMENT '已运行次数',
    `last_running_time` int(11) UNSIGNED     NOT NULL DEFAULT '0' COMMENT '上次运行时间',
    `sort`              smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序，越大越前',
    `record_log`        tinyint(1) UNSIGNED  NOT NULL DEFAULT '1' COMMENT '是否记录日志',
    `enabled`           tinyint(1) UNSIGNED  NOT NULL DEFAULT '0' COMMENT '启用',
    `created_at`        datetime             NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`        datetime                      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8 COMMENT ='计划任务'
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `cn_crontab_log`
(
    `id`           bigint(20) UNSIGNED NOT NULL COMMENT '主键',
    `crontab_id`   int(10) UNSIGNED    NOT NULL COMMENT '任务id',
    `target`       varchar(255)        NOT NULL COMMENT '调用字符串',
    `parameter`    text COMMENT '调用参数',
    `exception`    text COMMENT '异常信息',
    `return_code`  mediumint(1)        NOT NULL DEFAULT '0' COMMENT '执行状态：0成功',
    `running_time` int(10) UNSIGNED    NOT NULL DEFAULT '0' COMMENT '执行耗时毫秒',
    `create_time`  datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time`  datetime                     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8 COMMENT ='计划任务执行日志'
  ROW_FORMAT = DYNAMIC;

ALTER TABLE `cn_crontab`
    ADD PRIMARY KEY (`crontab_id`) USING BTREE,
    ADD KEY `task_type` (`task_type`),
    ADD KEY `created_at` (`created_at`),
    ADD KEY `enabled` (`enabled`),
    ADD KEY `sort` (`sort`);

ALTER TABLE `cn_crontab_log`
    ADD PRIMARY KEY (`id`) USING BTREE,
    ADD KEY `crontab_id` (`crontab_id`),
    ADD KEY `create_time` (`create_time`),
    ADD KEY `return_code` (`return_code`);

ALTER TABLE `cn_crontab`
    MODIFY `crontab_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

ALTER TABLE `cn_crontab_log`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

ALTER TABLE `cn_crontab_log`
    ADD CONSTRAINT `log_crontab_id` FOREIGN KEY (`crontab_id`) REFERENCES `cn_crontab` (`crontab_id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;