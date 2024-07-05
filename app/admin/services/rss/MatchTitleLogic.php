<?php

namespace app\admin\services\rss;

use app\model\enums\LogicEnums;

/**
 * 标题副标题匹配逻辑
 */
readonly class MatchTitleLogic
{
    /**
     * @param RuleModeEnums|null $ruleModeEnums 规则模式
     * @param array $text_selector 包含关键字
     * @param LogicEnums $text_selector_op 包含关键字的逻辑关系
     * @param array $text_filter 排除关键字
     * @param LogicEnums $text_filter_op 排除关键字的逻辑关系
     * @param string $regex_selector 选中规则：正则表达式
     * @param string $regex_filter 排除规则：正则表达式
     */
    public function __construct(
        public ?RuleModeEnums $ruleModeEnums,
        public array          $text_selector,
        public LogicEnums     $text_selector_op,
        public array          $text_filter,
        public LogicEnums     $text_filter_op,
        public string         $regex_selector,
        public string         $regex_filter
    )
    {
    }
}
