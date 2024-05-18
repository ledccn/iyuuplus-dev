<?php

namespace app\admin\support\iyuu;

/**
 * 爱语飞飞
 * @method self text($title)
 * @method self desp($desp)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $defined = [
        'text',
        'desp',
    ];

    public function toHttpUri(): string
    {
        return '{token}.send';
    }
}
