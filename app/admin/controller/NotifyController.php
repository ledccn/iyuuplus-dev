<?php

namespace app\admin\controller;

use app\model\enums\ConfigEnums;
use plugin\admin\app\controller\Base;
use support\Request;
use support\Response;
use Throwable;

/**
 * 通知渠道配置
 */
class NotifyController extends Base
{
    /**
     * 不需要验证权限的方法
     * @var string[]
     */
    protected $noNeedAuth = ['get'];

    /**
     * 配置
     * @param Request $request
     * @return Response
     * @throws Throwable
     */
    public function index(Request $request): Response
    {
        $vars = [
            'notify_iyuu' => ConfigEnums::notify_iyuu->value,
            'notify_server_chan' => ConfigEnums::notify_server_chan->value,
            'notify_bark' => ConfigEnums::notify_bark->value,
            'notify_email' => ConfigEnums::notify_email->value,
            'notify_qy_weixin' => ConfigEnums::notify_qy_weixin->value,
        ];
        return raw_view('notify/index', array_merge($vars, ['support_list' => json_encode(array_values($vars))]));
    }

    /**
     * 获取配置
     * @param Request $request
     * @return Response
     */
    public function get(Request $request): Response
    {
        $name = $request->get('name', '');
        if (!str_starts_with($name, 'notify_')) {
            return $this->fail('错误的通知前缀');
        }

        try {
            $notifyEnum = ConfigEnums::from($name);
            return $this->success('ok', ConfigEnums::getConfig($notifyEnum));
        } catch (Throwable $throwable) {
            return $this->fail('获取配置异常：' . $throwable->getMessage());
        }
    }

    /**
     * 保存配置
     * @param Request $request
     * @return Response
     */
    public function save(Request $request): Response
    {
        $name = $request->post('PRIMARY_KEY', '');
        if (!str_starts_with($name, 'notify_')) {
            return $this->fail('错误的通知前缀');
        }

        try {
            $data = $request->post();
            unset($data['PRIMARY_KEY']);
            $notifyEnum = ConfigEnums::from($name);
            ConfigEnums::saveConfig($notifyEnum, $data);
            return $this->success('ok');
        } catch (Throwable $throwable) {
            return $this->fail('保存配置异常：' . $throwable->getMessage());
        }
    }
}
