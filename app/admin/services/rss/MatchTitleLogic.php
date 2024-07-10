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

    /**
     * 匹配
     * @param TorrentItem $item
     * @return bool
     */
    public function match(TorrentItem $item): bool
    {
        if (empty($this->ruleModeEnums)) {
            return true;
        }

        return match ($this->ruleModeEnums) {
            RuleModeEnums::Simple => $this->matchSimple($item),
            RuleModeEnums::Regex => $this->matchRegex($item),
        };
    }

    /**
     * 简易模式
     * @param TorrentItem $item
     * @return bool
     */
    private function matchSimple(TorrentItem $item): bool
    {
        $text = $item->getTitle();
        switch (true) {
            case empty($this->text_selector):
                return !$this->evalBoolean($this->strContains($text, $this->text_filter), $this->text_filter_op);
            case empty($this->text_filter):
                return $this->evalBoolean($this->strContains($text, $this->text_selector), $this->text_selector_op);
            default:
                $rs1 = $this->evalBoolean($this->strContains($text, $this->text_selector), $this->text_selector_op);
                $rs2 = !$this->evalBoolean($this->strContains($text, $this->text_filter), $this->text_filter_op);
                return $rs1 and $rs2;
        }
    }

    /**
     * 正则表达式
     * @param TorrentItem $item
     * @return bool
     */
    private function matchRegex(TorrentItem $item): bool
    {
        if (empty($this->regex_selector)) {
            return false === preg_match($this->regex_filter. 'i', $item->getTitle(), $matches);
        } else {
            return false !== preg_match($this->regex_selector . 'i', $item->getTitle(), $matches);
        }
    }

    /**
     * 文本匹配
     * @param string $text
     * @param array $matches
     * @return array
     */
    private function strContains(string $text, array $matches): array
    {
        $result = [];
        foreach ($matches as $match) {
            $result[] = str_contains($text, $match);
        }
        return $result;
    }

    /**
     * 求数组的布尔值
     * @param array $result
     * @param LogicEnums $logicEnums
     * @return bool
     */
    private function evalBoolean(array $result, LogicEnums $logicEnums): bool
    {
        if (LogicEnums::OR === $logicEnums) {
            return in_array(true, $result, true);
        } else {
            return false === in_array(false, $result, true);
        }
    }
}
