<?php

namespace aportela\HTTPRequestWrapper;

class HTTPResponse
{
    public int $code = 0;
    protected string $contentType = "";
    protected array $headers = array();
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

    public function getContentType(): string
    {
        return ($this->contentType);
    }

    public function hasHeader(string $header): bool
    {
        return (array_key_exists($header, $this->headers));
    }

    public function getHeaderValues(string $header): array
    {
        return ($this->headers[$header]);
    }

    public function is(\aportela\HTTPRequestWrapper\ContentType $contentType): bool
    {
        switch ($contentType) {
            case \aportela\HTTPRequestWrapper\ContentType::JSON:
                return (str_starts_with($this->contentType, "application/json"));
                break;
            case \aportela\HTTPRequestWrapper\ContentType::XML:
                return (str_starts_with($this->contentType, "application/xml") || str_starts_with($this->contentType, "text/xml"));
                break;
            case \aportela\HTTPRequestWrapper\ContentType::TEXT_PLAIN:
                return (str_starts_with($this->contentType, "text/plain"));
                break;
        }
        return (false);
    }
}
