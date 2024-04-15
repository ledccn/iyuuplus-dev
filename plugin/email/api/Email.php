<?php

namespace plugin\email\api;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use plugin\admin\app\model\Option;
use support\exception\BusinessException;

class Email
{

    /**
     * Option表的name字段值
     */
    const SETTING_OPTION_NAME = 'email_setting';

    /**
     * Option表模版前缀
     */
    const TEMPLATE_OPTION_PREFIX = 'email_template_';

    /**
     * @param $from
     * @param $to
     * @param $subject
     * @param $content
     * @return void
     * @throws Exception|BusinessException
     */
    public static function send($from, $to, $subject, $content)
    {
        $mailer = static::getMailer();
        call_user_func_array([$mailer, 'setFrom'], (array)$from);
        call_user_func_array([$mailer, 'addAddress'], (array)$to);
        $mailer->Subject = "=?UTF-8?B?".base64_encode($subject)."?=";
        $mailer->isHTML(true);
        $mailer->Body = $content;
        $mailer->send();
    }

    /**
     * 按照模版发送
     * @param string|array $to
     * @param $templateName
     * @param array $templateData
     * @return void
     * @throws BusinessException
     * @throws Exception
     */
    public static function sendByTemplate($to, $templateName, array $templateData = [])
    {
        $emailTemplate = Option::where('name', "email_template_$templateName")->value('value');
        $emailTemplate = $emailTemplate ? json_decode($emailTemplate, true) : null;
        if (!$emailTemplate) {
            throw new BusinessException('模版不存在');
        }
        $subject = $emailTemplate['subject'];
        $content = $emailTemplate['content'];
        if ($templateData) {
            $search = [];
            foreach ($templateData as $key => $value) {
                $search[] = '{' . $key . '}';
            }
            $content = str_replace($search, array_values($templateData), $content);
        }
        $config = static::getConfig();
        static::send($config['From'] ?? '', $to, $subject, $content);
    }

    /**
     * Get Mailer
     * @return PHPMailer
     * @throws BusinessException
     */
    public static function getMailer(): PHPMailer
    {
        if (!class_exists(PHPMailer::class)) {
            throw new BusinessException('请执行 composer require phpmailer/phpmailer 并重启');
        }
        $config = static::getConfig();
        if (!$config) {
            throw new BusinessException('未设置邮件配置');
        }
        $mailer = new PHPMailer();
        $mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        $mailer->isSMTP();
        $mailer->Host = $config['Host'];
        $mailer->SMTPAuth = true;
        $mailer->CharSet = 'UTF-8';
        $mailer->Username = $config['Username'];
        $mailer->Password = $config['Password'];
        $map = [
            'ssl' => PHPMailer::ENCRYPTION_SMTPS,
            'tls' => PHPMailer::ENCRYPTION_STARTTLS,
        ];
        $mailer->SMTPSecure = $map[$config['SMTPSecure']] ?? '';
        $mailer->Port = $config['Port'];
        return $mailer;
    }

    /**
     * 获取配置
     * @return array|null
     */
    public static function getConfig()
    {
        $config = Option::where('name', static::SETTING_OPTION_NAME)->value('value');
        return $config ? json_decode($config, true) : null;
    }

}