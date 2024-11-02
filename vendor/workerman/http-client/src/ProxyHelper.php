<?php

namespace Workerman\Http;

use Workerman\Connection\AsyncTcpConnection;

class ProxyHelper
{
    public static function setConnectionProxy(AsyncTcpConnection &$connection, array $context): void
    {
        $httpProxy = $context['http']['proxy'] ?? '';
        if (!empty($httpProxy)) {
            $proxyScheme = parse_url($httpProxy, PHP_URL_SCHEME);
            $proxyString = explode('//', $httpProxy)[1] ?? '';
            if ($proxyScheme === 'socks5') {
                $connection->proxySocks5 = $proxyString;
            } else if ($proxyScheme === 'http') {
                $connection->proxyHttp = $proxyString;
            }
        }
    }

    public static function addressKey(string $address, string $proxyString): string
    {
        if (strpos($proxyString, '://') === false) {
            return $address;
        } else {
            $proxyString = explode('//', $proxyString)[1] ?? '';
            return $proxyString;
        }
    }
}