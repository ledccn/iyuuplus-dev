<?php

namespace app\enums;

use app\traits\HasEventRegister;

/**
 * 自动转移做种客户端，事件枚举
 */
enum EventTransferEnums: string
{
    use HasEventRegister;

    /**
     * 自动转移做种客户端：转移前
     */
    case transfer_action_before = 'transfer.action.before';
    /**
     * 自动转移做种客户端：转移后
     */
    case transfer_action_after = 'transfer.action.after';
}