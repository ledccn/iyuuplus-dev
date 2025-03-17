<?php

namespace app\admin\services\site;

use app\model\Site;
use Ledc\Element\Concrete;
use Ledc\Element\Decorator;
use Ledc\Element\GenerateInterface;
use support\exception\BusinessException;

/**
 * 生成Layui站点配置模板
 */
class LayuiTemplate
{
    /**
     * 生成器
     * @param string $site 站点名称
     * @return GenerateInterface
     * @throws BusinessException
     */
    public static function generate(string $site): GenerateInterface
    {
        $default = new Concrete();
        $model = Site::uniqueSite($site);
        if (!$model) {
            throw new BusinessException('站点不存在：' . $site);
        }
        // 下载种子必须cookie
        if ($model->cookie_required) {
            $default = new CookieRequired($default);
        }

        $default = new OptionsProxy($default);
        return match ($site) {
            '1ptba',
            '52pt',
            'beitai',
            'btschool',
            'byr',
            'carpt',
            'cyanbug',
            'dajiao',
            'discfan',
            'dmhy',
            'dragonhd',
            'eastgame',
            'ecustpt',
            'haidan',
            'hares',
            'hd4fans',
            'hdarea',
            'hdatmos',
            'hdbd',
            'hdmayi',
            'hdpt',
            'hdroute',
            'hdtime',
            'hdvideo',
            'hdzone',
            'hhanclub',
            'hudbt',
            'joyhd',
            'kamept',
            'nanyangpt',
            'nicept',
            'opencd',
            'oshen',
            'pandapt',
            'pt0ffcc',
            'pt2xfree',
            'ptchina',
            'ptlsp',
            'ptsbao',
            'pttime',
            'rousi',
            'shadowflow',
            'sharkpt',
            'skyeysnow',
            'soulvoice',
            'tjupt',
            'torrentccf',
            'ubits',
            'upxin',
            'wintersakura',
            'qhstudio',
            'ptvicomo',
            'qingwapt',
            'xingtan',
            'hdkyl',
            'ilolicon',
            'gtkpw',
            'icc2022',
            'wukongwendao',
            'crabpt',
            'okpt',
            'tosky',
            'raingfh',
            'njtupt',
            'ptzone',
            'hdclone',
            'kufei',
            'tmpt',
            'sanpro',
            'htpt',
            'keepfrds' => Decorator::make([NexusPHP::class, OptionsUrlJoin::class], $default),
            'yemapt' => Decorator::make([OptionsRssUrl::class], $default),
            'ttg' => Decorator::make([NexusPHP::class, OptionsLimit::class, OptionsRssUrl::class], $default),
            'redleaves', 'pter', 'pt', 'hdsky', 'ssd', 'lemonhd' => Decorator::make([NexusPHP::class, OptionsLimit::class], $default),
            'hdpost', 'monikadesign' => Decorator::make([NexusPHP::class, OptionsRsskey::class, OptionsRssUrl::class], $default),
            'dicmusic',
            'greatposterwall' => Decorator::make([DicMusic::class, OptionsLimit::class, OptionsRssUrl::class], $default),
            'm-team' => Decorator::make([NexusPHP::class, OptionsXApiKey::class, OptionsLimit::class, OptionsUrlJoin::class, OptionsRssUrl::class], $default),
            'hdcity' => Decorator::make([NexusPHP::class, OptionsCuHashByHdcity::class], $default),
            'audiences' => Decorator::make([NexusPHP::class, OptionsUid::class, OptionsRsskey::class, OptionsLimit::class], $default),
            'hdhome', 'pthome', 'hddolby' => Decorator::make([NexusPHP::class, OptionsUid::class, OptionsLimit::class, OptionsRssUrl::class], $default),
            'zhuque' => Decorator::make([Zhuque::class, OptionsUid::class, OptionsLimit::class], $default),
            'ourbits', 'chdbits', 'piggo', 'zmpt', 'agsvpt', 'hdfans', 'ptcafe', 'ptlgs', 'ptlover', 'hitpt', 'hspt', 'xingyunge', 'cspt' => Decorator::make([NexusPHP::class, OptionsUid::class, OptionsLimit::class, OptionsUrlJoin::class], $default),
            default => $default,
        };
    }
}
