<?php

namespace Ledc\Curl;

/**
 * 简单的POST请求
 */
final class HttpPost
{
    /**
     * 超时，单位：秒
     * @var int
     */
    public static int $timeout = 5;

    /**
     * 简单的POST请求
     * - 用curl实现
     * @param string $url 请求地址
     * @param object|array $data 数据包
     * @param bool $isJsonRequest 是否Json请求
     * @param int|null $responseCode 最后的响应代码
     * @param int $curlErrorCode 返回错误代码或在没有错误发生时返回 0 (零)
     * @param string $curlErrorMessage 返回错误信息，或者如果没有任何错误发生就返回 '' (空字符串)
     * @return bool|string
     */
    public static function request(string $url, object|array $data = [], bool $isJsonRequest = true, ?int &$responseCode = null, int &$curlErrorCode = 0, string &$curlErrorMessage = ''): bool|string
    {
        if ($isJsonRequest) {
            $header = ['Content-Type: application/json; charset=UTF-8'];
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $header = ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8'];
            $data = http_build_query($data);
        }
        return self::curl($url, $data, $header, $responseCode, $curlErrorCode, $curlErrorMessage);
    }

    /**
     * 通过Curl扩展发起POST请求
     * @param string $url 请求地址
     * @param string $data 打包后的数据
     * @param array $header 请求头
     * @param int|null $responseCode 最后的响应代码
     * @param int $curlErrorCode 返回错误代码或在没有错误发生时返回 0 (零)
     * @param string $curlErrorMessage 返回错误信息，或者如果没有任何错误发生就返回 '' (空字符串)
     * @return bool|string
     */
    public static function curl(string $url, string $data, array $header = [], ?int &$responseCode = null, int &$curlErrorCode = 0, string &$curlErrorMessage = ''): bool|string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        if (0 === stripos($url, 'https://')) {
            //false 禁止 cURL 验证对等证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            //0 时不检查名称（SSL 对等证书中的公用名称字段或主题备用名称（Subject Alternate Name，简称 SNA）字段是否与提供的主机名匹配）
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    // 自动跳转，跟随请求Location
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);         // 递归次数
        $response = curl_exec($ch);
        $responseCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlErrorCode = curl_errno($ch);
        $curlErrorMessage = curl_error($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * 简单的POST请求
     * - 用file_get_contents实现
     * @param string $url 请求地址
     * @param object|array $data 数据包
     * @param bool $isJsonRequest 是否Json请求
     * @return false|string
     */
    public static function stream(string $url, object|array $data, bool $isJsonRequest = true): bool|string
    {
        if ($isJsonRequest) {
            $type = 'Content-Type: application/json; charset=UTF-8';
            $data = json_encode($data);
        } else {
            $type = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            $data = http_build_query($data);
        }
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => $type . "\r\n" . "Content-Length: " . strlen($data) . "\r\n",
                'content' => $data,
                'timeout' => self::$timeout
            ],
            // 解决SSL证书验证失败的问题
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
        $context = stream_context_create($opts);
        return file_get_contents($url, false, $context);
    }
}