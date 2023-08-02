<?php

namespace aportela\HTTPRequestWrapper;

class HTTPResponse
{
    public int $code = 0;
    public string $contentType = "";
    public array $headers = array();
    public $body = null;

    public function __construct(int $code, string $contentType, array $headers, $body)
    {
        $this->code = $code;
        $this->contentType = $contentType;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function __destruct()
    {
    }
}
