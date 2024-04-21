<?php

namespace app\admin\services;

use app\model\Site;
use Iyuu\ReseedClient\Client;
use Throwable;

/**
 * 站点服务层
 */
class SitesServices
{
    /**
     * 同步站点表
     * @return void
     */
    public static function sync(): void
    {
        try {
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
                $siteModel->reseed_check = $item['reseed_check'] ?? '';
                $siteModel->is_https = $item['is_https'] ?? 1;
                $siteModel->cookie_required = $item['cookie_required'] ?? 0;
                $siteModel->save();
            }
        } catch (Throwable $throwable) {
        }
    }
}
