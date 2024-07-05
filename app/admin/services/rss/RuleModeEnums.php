<?php

namespace app\admin\services\rss;

/**
 * 规则模式
 */
enum RuleModeEnums
{
    /**
     * 简易模式
     */
    case Simple;
    /**
     * 正则表达式模式
     */
    case Regex;
}
