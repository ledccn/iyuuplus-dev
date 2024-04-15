<?php

namespace app\common;

/**
 * 格式化枚举值
 */
trait HasFormatEnum
{
    /**
     * 格式化下拉列表
     * @param array $items
     * @return array
     */
    private function formatSelectEnum(array $items): array
    {
        $formatted_items = [];
        foreach ($items as $name => $value) {
            $formatted_items[] = [
                'name' => $name,
                'value' => $value
            ];
        }
        return $formatted_items;
    }
}
