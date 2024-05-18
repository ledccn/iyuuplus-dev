<?php

declare(strict_types=1);

namespace app\admin\support\iyuu;

/**
 * 爱语飞飞
 */
class Client extends \Guanguans\Notify\Foundation\Client
{
    public function __construct(Authenticator $authenticator)
    {
        parent::__construct($authenticator);
        $this->baseUri('https://iyuu.cn/');
    }
}
