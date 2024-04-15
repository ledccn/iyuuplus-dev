<?php

namespace Iyuu\SiteManager\Spider;

use Closure;
use Exception;
use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\Cookie;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Page;
use Iyuu\SiteManager\Contracts\ChromeBrowser;
use RuntimeException;

/**
 * 谷歌浏览器助手
 * @link https://github.com/chrome-php/chrome
 * @link https://packagist.org/packages/chrome-php/chrome
 */
class ChromeHelper
{
    /**
     * 创建谷歌浏览器实例，执行回调函数
     * @param Closure|ChromeBrowser $callable 可调用的回调函数
     * @param array $options 传给createBrowser的参数
     * @return mixed 回调函数的返回值
     * @throws CommunicationException
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
     */
    public static function createChromeBrowser(ChromeBrowser|Closure $callable, array $options = ['headless' => false]): mixed
    {
        $browserFactory = new BrowserFactory();
        $browser = $browserFactory->createBrowser($options);
        $page = $browser->createPage();

        try {
            return match (true) {
                $callable instanceof ChromeBrowser => $callable->chromeBrowser($page, $browser),
                $callable instanceof Closure => $callable($page, $browser),
                default => call_user_func($callable, $page, $browser),
            };
        } finally {
            try {
                $browser->close();
            } catch (Exception $e) {
            }
        }
    }

    /**
     * 请求URL，返回页面HTML
     * @param string $url
     * @param array|array<Cookie> $cookies
     * @return string 页面HTML
     * @throws RuntimeException|Exception
     */
    public static function request(string $url, array $cookies = []): string
    {
        return self::createChromeBrowser(function (Page $page, ProcessAwareBrowser $browser) use ($url, $cookies) {
            $page = $browser->createPage();
            if ($cookies) {
                $page->setCookies($cookies)->await();
            }

            $page->navigate($url)->waitForNavigation();

            // get page title
            //$pageTitle = $page->evaluate('document.title')->getReturnValue();

            // screenshot - Say "Cheese"!
            //$page->screenshot()->saveToFile(runtime_path('/bar.png'));
            return $page->getHtml();
        }, [
            'headless' => false, // disable headless mode
            'connectionDelay' => 5,

            //'debugLogger' => 'php://stdout',
            //'customFlags' => [],
        ]);
    }

    /**
     * 转换EditThisCookie的格式
     * @param string $json
     * @return array<Cookie>
     */
    public static function convertEditThisCookie(string $json): array
    {
        $_cookies = json_decode($json, true);
        $cookies = [];
        foreach ($_cookies as $cookie) {
            $cookies[] = Cookie::create($cookie['name'], $cookie['value'], [
                'domain' => $cookie['domain'],
                'expires' => $cookie['expirationDate'],
                'path' => $cookie['path'],
                'httpOnly' => $cookie['httpOnly'],
                'hostOnly' => $cookie['hostOnly'],
            ]);
        }
        return $cookies;
    }
}
