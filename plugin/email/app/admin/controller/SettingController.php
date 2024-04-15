<?php

namespace plugin\email\app\admin\controller;

use PHPMailer\PHPMailer\Exception;
use plugin\admin\app\model\Option;
use plugin\email\api\Email;
use plugin\email\api\Install;
use plugin\email\app\admin\model\Template;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use function view;

/**
 * 邮件设置
 */
class SettingController
{

    /**
     * 邮件设置页
     * @return Response
     */
    public function index()
    {
        return view('setting/index');
    }

    /**
     * 获取设置
     * @return Response
     */
    public function get(): Response
    {
        $name = Email::SETTING_OPTION_NAME;
        $setting = Option::where('name', $name)->value('value');
        $setting = $setting ? json_decode($setting, true) : [
            'Host' => 'smtp.qq.com',
            'Username' => '',
            'Password' => '',
            'SMTPSecure' => 'ssl',
            'Port' => 465,
            'From' => '',
        ];
        return json(['code' => 0, 'msg' => 'ok', 'data' => $setting]);
    }

    /**
     * 更改设置
     * @param Request $request
     * @return Response
     */
    public function save(Request $request): Response
    {
        $data = [
            'Host' => $request->post('Host'),
            'Username' => $request->post('Username'),
            'Password' => $request->post('Password'),
            'SMTPSecure' => $request->post('SMTPSecure'),
            'Port' => $request->post('Port'),
            'From' => $request->post('From'),
        ];
        $value = json_encode($data);
        $name = Email::SETTING_OPTION_NAME;
        $option = Option::where('name', $name)->first();
        if ($option) {
            Option::where('name', $name)->update(['value' => $value]);
        } else {
            $option = new Option();
            $option->name = $name;
            $option->value = $value;
            $option->save();

            // 默认生成一个验证码邮件模版
            $templateName = 'captcha';
            if (!Template::get($templateName)) {
                Template::save($templateName, $data['From'], '验证码', '验证码为 {code} 。如您未发送过该邮件，请忽略。');
            }
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 邮件测试
     * @param Request $request
     * @return Response
     * @throws Exception|BusinessException
     */
    public function test(Request $request): Response
    {
        $from = $request->post('From');
        $to = $request->post('To');
        $subject = $request->post('Subject');
        $content = $request->post('Content');
        Email::send($from, $to, $subject, $content);
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 邮件模版测试
     * @param Request $request
     * @return Response
     * @throws Exception|BusinessException
     */
    public function testTemplate(Request $request): Response
    {
        if ($request->method() === 'GET') {
            return view('template/test');
        }
        $name = $request->post('name');
        $to = $request->post('to');
        $data = $request->post('data');
        $data = $data ? json_decode($data, true) : [];
        Email::sendByTemplate($to, $name, $data);
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 获取模版
     * @param Request $request
     * @return Response
     */
    public function selectTemplate(Request $request): Response
    {
        $name = $request->get('name', '');
        $prefix = Email::TEMPLATE_OPTION_PREFIX;
        if ($name && is_string($name)) {
            $items = Option::where('name', 'like', "{$prefix}$name%")->get()->toArray();
        } else {
            $items = Option::where('name', 'like', "$prefix%")->get()->toArray();
        }
        foreach ($items as &$item) {
            $item['name'] = Template::optionNameToTemplateName($item['name']);
            [$item['from'], $item['subject'], $item['content']] = array_values(json_decode($item['value'], true));
        }
        return json(['code' => 0, 'msg' => 'ok', 'data' => $items]);
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     */
    public function insertTemplate(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $name = $request->post('name');
            if (Template::get($name)) {
                return json(['code' => 1, 'msg' => '模版已经存在']);
            }
            $from = $request->post('from');
            $subject = $request->post('subject');
            $content = $request->post('content');
            Template::save($name, $from, $subject, $content);
        }
        return view('template/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     */
    public function updateTemplate(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $name = $request->post('name');
            $newName = $request->post('new_name');
            if (!Template::get($name)) {
                return json(['code' => 1, 'msg' => '模版不存在']);
            }
            if ($name != $newName) {
                Template::delete([$name]);
            }
            $from = $request->post('from');
            $subject = $request->post('subject');
            $content = $request->post('content');
            Template::save($newName, $from, $subject, $content);
            return json(['code' => 0, 'msg' => 'ok']);
        }
        return view('template/update');
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function deleteTemplate(Request $request): Response
    {
        $names = (array)$request->post('name');
        Template::delete($names);
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
