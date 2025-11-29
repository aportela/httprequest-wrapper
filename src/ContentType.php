<?php

declare(strict_types=1);

namespace aportela\HTTPRequestWrapper;

enum ContentType
{
    case JSON;

    case XML;

    case TEXT_PLAIN;
}
