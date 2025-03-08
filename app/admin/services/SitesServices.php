<?php

namespace app\admin\services;

use app\model\Site;
use Iyuu\ReseedClient\Client;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Option;
use plugin\cron\app\support\PushNotify;
use think\helper\Str;
use Throwable;

/**
 * 站点服务层
 */
class SitesServices
{
    /**
     * IYUU 浏览器助手配置键名
     */
    public const string SYSTEM_IYUU_HELPER = 'system_iyuu_helper';

    /**
     * 获取IYUU 浏览器助手密钥
     * @return Option
     */
    public static function getIyuuHelper(): string
    {
        $option = Option::where('name', '=', self::SYSTEM_IYUU_HELPER)->first();
        if (!$option) {
            $option = new Option();
            $option->name = self::SYSTEM_IYUU_HELPER;
            $option->value = Str::random(40, 0);
            $option->save();
        }
        return $option->value;
    }

    /**
     * 同步站点表
     * @return void
     */
    public static function sync(): void
    {
        try {
            if (!Util::schema()->hasTable(Site::TABLE_NAME)) {
                return;
            }

            $reseedClient = new Client(iyuu_token());
            $list = $reseedClient->sites();
            file_put_contents(runtime_path('sync.json'), json_encode($list, JSON_UNESCAPED_UNICODE));
            foreach ($list as $site => $item) {
                $siteModel = Site::uniqueSite($site);
                if (!$siteModel) {
                    $siteModel = new Site();
                    $siteModel->sid = $item['id'];
                    $siteModel->site = $site;
                }

                $siteModel->nickname = $item['nickname'];
                $siteModel->base_url = $item['base_url'];
                $siteModel->download_page = $item['download_page'] ?? '';
                $siteModel->details_page = $item['details_page'] ?? '';
                $siteModel->is_https = $item['is_https'] ?? 1;
                $siteModel->cookie_required = $item['cookie_required'] ?? 0;
                $siteModel->save();
            }
        } catch (Throwable $throwable) {
            $msg = '同步站点列表失败：' . $throwable->getMessage();
            PushNotify::error($msg);
            echo $msg . PHP_EOL;
        }
    }
}
