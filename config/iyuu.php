<?php

/**
 * IYUU配置
 */
defined('IYUU_HOST') or define('IYUU_HOST', 'api.iyuu.cn');

return [
    'base_url' => 'http://' . IYUU_HOST,
    'endpoint' => [
        'getRecommendSites' => '/App.Api.GetRecommendSites',
        'bind' => '/App.Api.Bind',
    ],
];
