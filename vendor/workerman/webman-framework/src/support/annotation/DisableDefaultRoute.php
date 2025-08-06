<?php

namespace support\annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class DisableDefaultRoute extends \Webman\Annotation\DisableDefaultRoute
{
}