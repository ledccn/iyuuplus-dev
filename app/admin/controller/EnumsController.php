<?php

namespace app\admin\controller;

use app\common\HasFormatEnum;
use app\common\HasJsonResponse;
use app\model\enums\ConfigEnums;
use app\model\enums\ReseedStatusEnums;
use Iyuu\BittorrentClient\ClientEnums;
use support\Request;
use support\Response;

/**
 * 枚举控制器
 */
class EnumsController
{
    use HasFormatEnum, HasJsonResponse;

    /**
     * 客户端类型
     * @param Request $request
     * @return Response
     */
    public function client(Request $request): Response
    {
        return $this->data($this->formatSelectEnum(ClientEnums::toArray()));
    }

    /**
     * 自定义配置枚举
     * @param Request $request
     * @return Response
     */
    public function config(Request $request): Response
    {
        return $this->data($this->formatSelectEnum(ConfigEnums::toArray()));
    }

    /**
     * 辅种状态枚举
     * @param Request $request
     * @return Response
     */
    public function reseedStatus(Request $request): Response
    {
        return $this->data($this->formatSelectEnum(ReseedStatusEnums::select()));
    }
}
