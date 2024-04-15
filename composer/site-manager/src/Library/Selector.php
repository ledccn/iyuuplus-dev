<?php

namespace Iyuu\SiteManager\Library;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;

/**
 * 选择器
 */
class Selector
{
    /**
     * 选择器类型：XPath
     */
    public const XPATH = 'xpath';

    /**
     * 选择器类型：Regex
     */
    public const REGEX = 'regex';

    /**
     * 选择文本
     * @param string $html 原文本
     * @param string $selector 选择器规则
     * @param string $selector_type 选择器类型
     * @return array|string|null
     */
    public static function select(string $html, string $selector, string $selector_type = self::XPATH): array|string|null
    {
        if (empty($html) || empty($selector)) {
            return null;
        }

        return match ($selector_type) {
            self::XPATH => self::xpath_select($html, $selector),
            self::REGEX => self::regex_select($html, $selector),
            default => throw new InvalidArgumentException('未知的的选择器类型：' . $selector_type),
        };
    }

    /**
     * 移除文本
     * @param string $html 原文本
     * @param string $selector 选择器规则
     * @param string $selector_type 选择器类型
     * @return null|string|array
     */
    public static function remove(string $html, string $selector, string $selector_type = self::XPATH): array|string|null
    {
        if (empty($html) || empty($selector)) {
            return null;
        }

        $_html = match ($selector_type) {
            self::XPATH => self::xpath_select($html, $selector, true),
            self::REGEX => self::regex_select($html, $selector, true),
            default => throw new InvalidArgumentException('未知的的选择器类型：' . $selector_type),
        };

        if (null === $_html) {
            return $html;
        }
        return str_replace($_html, '', $html);
    }

    /**
     * xpath选择器
     * @param string $html 原文本
     * @param string $selector 选择器规则
     * @param bool $remove 是否为移除选中
     * @return string|array|null
     */
    private static function xpath_select(string $html, string $selector, bool $remove = false): array|string|null
    {
        $dom = new DOMDocument();
        // 禁用标准的 libxml 错误
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        // 清空 libxml 错误缓冲
        libxml_clear_errors();
        $xpath = new DOMXpath($dom);
        /** @var DOMNodeList|false $elements */
        $elements = $xpath->query($selector);
        if (false === $elements) {
            return null;
        }

        $result = [];
        if ($elements instanceof DOMNodeList) {
            /** @var DOMElement $element */
            foreach ($elements as $element) {
                // 如果是删除操作，取一整块代码
                if ($remove) {
                    $content = $dom->saveXml($element);
                } else {
                    $nodeName = $element->nodeName;
                    $nodeType = $element->nodeType;     // 1.Element 2.Attribute 3.Text 4.CDATA
                    // 如果是img标签，直接取src值
                    if (XML_ELEMENT_NODE === $nodeType && $nodeName === 'img') {
                        $content = $element->getAttribute('src');
                    } // 如果是标签属性，直接取节点值
                    elseif (XML_ATTRIBUTE_NODE === $nodeType || XML_TEXT_NODE === $nodeType || XML_CDATA_SECTION_NODE === $nodeType) {
                        $content = $element->nodeValue;
                    } else {
                        // 保留nodeValue里的html符号，给children二次提取
                        $content = $dom->saveXml($element);
                        $content = preg_replace(array("#^<{$nodeName}.*>#isU", "#</{$nodeName}>$#isU"), array('', ''), $content);
                    }
                }
                $result[] = $content;
            }
        }

        if (empty($result)) {
            return null;
        }
        // 如果只有一个元素就直接返回string，否则返回数组
        return 1 === count($result) ? $result[0] : $result;
    }

    /**
     * 正则选择器
     * @param string $html 原文本
     * @param string $selector 选择器规则
     * @param bool $remove 是否为移除选中
     * @return string|array|null
     */
    private static function regex_select(string $html, string $selector, bool $remove = false): array|string|null
    {
        if (false === preg_match_all($selector, $html, $matches)) {
            return null;
        }

        $count = count($matches);
        $result = [];
        // 一个都没有匹配到
        if ($count === 0) {
            return null;
        } // 只匹配一个，就是只有一个 ()
        elseif ($count == 2) {
            // 删除的话取匹配到的所有内容
            if ($remove) {
                $result = $matches[0];
            } else {
                $result = $matches[1];
            }
        } else {
            for ($i = 1; $i < $count; $i++) {
                // 如果只有一个元素，就直接返回好了
                $result[] = count($matches[$i]) > 1 ? $matches[$i] : $matches[$i][0];
            }
        }

        if (empty($result)) {
            return null;
        }
        // 如果只有一个元素就直接返回string，否则返回数组
        return 1 === count($result) ? $result[0] : $result;
    }

}
