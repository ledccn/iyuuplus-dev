<?php

namespace support\annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class Middleware extends \Webman\Annotation\Middleware
{

}