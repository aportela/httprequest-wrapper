<?php

namespace aportela\HTTPRequestWrapper;

class HTTPResponse
{
    public int $code = 0;
    protected string $contentType = "";
    /**
     * @var array<string, string[]>
     */
    protected array $headers = array();
    public ?string $body = null;

    /**
     * @param array<string, string[]> $headers
     */
    public function __construct(int $code, string $contentType, array $headers = [], ?string $body = null)
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
        return (array_key_exists(strtolower(trim($header)), $this->headers));
    }

    /**
     * @return string[]
     */
    public function getHeaderValues(string $header): array
    {
        return ($this->headers[strtolower(trim($header))] ?? []);
    }

    public function is(\aportela\HTTPRequestWrapper\ContentType $contentType): bool
    {
        switch ($contentType) {
            case \aportela\HTTPRequestWrapper\ContentType::JSON:
                return (str_starts_with($this->contentType, "application/json"));
            case \aportela\HTTPRequestWrapper\ContentType::XML:
                return (str_starts_with($this->contentType, "application/xml") || str_starts_with($this->contentType, "text/xml"));
            case \aportela\HTTPRequestWrapper\ContentType::TEXT_PLAIN:
                return (str_starts_with($this->contentType, "text/plain"));
            default:
                return (false);
        }
    }
}
