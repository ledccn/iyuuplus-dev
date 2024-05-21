<?php

namespace app\admin\support;

use app\model\enums\NotifyChannelEnums;
use Guanguans\Notify\Bark\Authenticator as BarkAuthenticator;
use Guanguans\Notify\Bark\Client as BarkClient;
use Guanguans\Notify\Bark\Messages\Message as BarkMessage;
use Guanguans\Notify\Foundation\Client;
use Guanguans\Notify\Foundation\Message;
use Guanguans\Notify\Foundation\Response;
use Guanguans\Notify\Iyuu\Authenticator as IyuuAuthenticator;
use Guanguans\Notify\Iyuu\Client as IyuuClient;
use Guanguans\Notify\Iyuu\Message\Message as IyuuMessage;
use Guanguans\Notify\ServerChan\Authenticator as ServerChanAuthenticator;
use Guanguans\Notify\ServerChan\Client as ServerChanClient;
use Guanguans\Notify\ServerChan\Messages\Message as ServerChanMessage;
use Guanguans\Notify\WeWork\Authenticator as WeWorkAuthenticator;
use Guanguans\Notify\WeWork\Client as WeWorkClient;
use Guanguans\Notify\WeWork\Messages\MarkdownMessage as WeWorkMarkdownMessage;
use GuzzleHttp\Exception\GuzzleException;

/**
 * 通知助手
 */
class NotifyHelper
{
    /**
     * @param string $text
     * @param string $desp
     * @return Response
     * @throws GuzzleException
     */
    public static function iyuu(string $text, string $desp = ''): Response
    {
        $config = NotifyChannelEnums::getConfig(NotifyChannelEnums::notify_iyuu);
        $token = $config['token'] ?? '';
        $token = $token ?: iyuu_token();
        $client = new IyuuClient(new IyuuAuthenticator($token));
        $client->verify(false);
        $message = IyuuMessage::make([
            'text' => $text,
            'desp' => $desp
        ]);
        return $client->send($message);
    }

    /**
     * @param string $title
     * @param string $desp
     * @return Response
     * @throws GuzzleException
     */
    public static function serverChan(string $title, string $desp = ''): Response
    {
        $config = NotifyChannelEnums::getConfig(NotifyChannelEnums::notify_server_chan);
        $token = $config['key'] ?? '';
        $client = new ServerChanClient(new ServerChanAuthenticator($token));
        $client->verify(false);
        $message = ServerChanMessage::make([
            'title' => $title,
            'desp' => $desp
        ]);
        return $client->send($message);
    }

    /**
     * @param string $title
     * @param string $body
     * @param string $group
     * @return Response
     * @throws GuzzleException
     */
    public static function bark(string $title, string $body = '', string $group = ''): Response
    {
        $config = NotifyChannelEnums::getConfig(NotifyChannelEnums::notify_bark);
        $token = $config['device_key'] ?? '';
        $server = $config['server'] ?? '';
        $group = $group ?: ($config['group'] ?? '');
        $client = new BarkClient(new BarkAuthenticator($token));
        $client->verify(false);
        if ($server) {
            $client->baseUri($server);
        }
        $message = BarkMessage::make([
            'title' => $title,
            'body' => $body,
            'group' => $group,
        ]);
        return $client->send($message);
    }

    /**
     * @param string $content
     * @return Response
     * @throws GuzzleException
     */
    public static function weWork(string $content): Response
    {
        $config = NotifyChannelEnums::getConfig(NotifyChannelEnums::notify_qy_weixin);
        $url = $config['url'] ?? '';
        $parse = parse_url(trim($url), PHP_URL_QUERY);
        parse_str($parse, $result);
        $token = $result['key'] ?? '';
        $client = new WeWorkClient(new WeWorkAuthenticator($token));
        $client->verify(false);
        $message = WeWorkMarkdownMessage::make()->content($content);
        return $client->send($message);
    }

    /**
     * @param string $title
     * @param string $content
     * @return Response
     * @throws GuzzleException
     */
    public static function webhook(string $title, string $content): Response
    {
        $config = NotifyChannelEnums::getConfig(NotifyChannelEnums::notify_webhook);
        $url = $config['url'] ?? '';
        $client = new Client();

        // 请求头
        $headers =  [];
        if (!empty($config['header'])) {
            $_headers = explode("\n", $config['header']);
            foreach ($_headers as $_header) {
                if (!empty($_header)) {
                    [$key, $value] = explode(':', trim($_header), 2);
                    $headers[trim($key)] = trim($value);
                }
            }
        }

        // 文档类型
        $headers['Content-Type'] = $config['content_type'];

        // 请求body
        $body = strtr($config['body'], [
            '{{title}}' => $title,
            '{{content}}' => $content
        ]);

        $parse = parse_url(trim($url));
        $config['_parse'] = $parse;
        $port = ($parse['port'] ?? null) ? ':' . $parse['port'] : '';
        $client->baseUri($parse['scheme'] . '://' . $parse['host'] . $port . '/')
            ->verify(false)
            ->headers($headers)
            ->body($body);

        $message = new class ($config) extends Message
        {
            /**
             * 请求uri
             * @return string
             */
            public function toHttpUri(): string
            {
                $parse = $this->getOption('_parse');
                $query = $parse['query'] ?? '';
                return trim($parse['path'] ?? '') . ($query ? '?' . $query : '');
            }

            /**
             * 请求方法
             * @return string
             */
            public function toHttpMethod(): string
            {
                return $this->getOption('method');
            }
        };

        return $client->send($message);
    }
}
