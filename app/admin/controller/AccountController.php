<?php

namespace app\admin\controller;

use app\admin\services\account\WechatAccountRocket;
use app\common\HasJsonResponse;
use app\common\Limit;
use Ledc\Crypt\RsaCrypt;
use plugin\admin\app\model\Admin;
use support\Request;
use support\Response;
use Throwable;

/**
 * 账户服务
 */
class AccountController
{
    use HasJsonResponse;

    /**
     * 无需登录及鉴权的方法
     * @var array
     */
    protected array $noNeedLogin = ['index'];

    /**
     * 爱语飞飞扫码登录
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        [$payload, $signature, $key] = $request->postMore(['payload/s', 'signature/s', 'key/s'], true);
        if (empty($payload)) {
            return $this->fail('payload is empty');
        }
        if (empty($signature)) {
            return $this->fail('signature is empty');
        }
        if (empty($key)) {
            return $this->fail('key is empty');
        }

        try {
            Limit::perMinute($request->getRealIp(), 6);
            $rsaCrypt = new RsaCrypt(
                config_path('rsa_private.key'),
                config_path('rsa_public.key'),
                $key
            );
            $wechatUser = $rsaCrypt->decrypt($payload, $signature);
            $rocket = new WechatAccountRocket($wechatUser);
            if (!password_verify(iyuu_token(), $rocket->token_password_hash)) {
                return $this->fail('登录失败：环境变量内token与登录方不一致');
            }

            $admin = Admin::first();
            if (0 !== (int)$admin->status) {
                return $this->json(1, '当前账户暂时无法登录');
            }
            $admin->login_at = date('Y-m-d H:i:s');
            $admin->save();
            $admin = $admin->toArray();
            $session = $request->session();
            $admin['password'] = md5($admin['password']);
            $session->set('admin', $admin);

            return $this->success('登录成功', [
                'nickname' => $admin['nickname'],
                'token' => $request->sessionId(),
            ]);
        } catch (Throwable $throwable) {
            return $this->fail($throwable->getMessage());
        }
    }
}
