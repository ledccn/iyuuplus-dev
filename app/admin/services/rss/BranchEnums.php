<?php

namespace app\admin\services\rss;

use DOMDocument;
use DOMElement;

/**
 * 逻辑分支
 */
enum BranchEnums
{
    /**
     * 默认
     */
    case Default;
    /**
     * Unit3D
     */
    case Unit3D;

    /**
     * @param string $url
     * @param DOMDocument $dom
     * @param DOMElement $element
     * @return self
     */
    public static function create(string $url, DOMDocument $dom, DOMElement $element): self
    {
        if ($element->getElementsByTagName('enclosure')->item(0)->getAttribute('url')) {
            return self::Default;
        }

        if ($generator = $dom->getElementsByTagName('generator')->item(0)?->nodeValue) {
            if (str_contains($generator, 'NexusPHP')) {
                return self::Default;
            }
        }

        return self::Unit3D;
    }
}
