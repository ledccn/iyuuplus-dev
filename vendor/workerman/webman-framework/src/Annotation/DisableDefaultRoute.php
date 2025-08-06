<?php

namespace Webman\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class DisableDefaultRoute
{
}