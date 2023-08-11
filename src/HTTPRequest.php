<?php

namespace aportela\HTTPRequestWrapper;

class HTTPRequest
{
    protected \Psr\Log\LoggerInterface $logger;
    protected string $userAgent;

    public const DEFAULT_USER_AGENT = "HTTPRequest-Wrapper - https://github.com/aportela/httprequest-wrapper (766f6964+github@gmail.com)";

    public function __construct(\Psr\Log\LoggerInterface $logger, string $userAgent = "")
    {
        $this->logger = $logger;
        if (!empty($userAgent)) {
            $this->userAgent = $userAgent;
        } else {
            $this->userAgent = self::DEFAULT_USER_AGENT;
        }
        $loadedExtensions = get_loaded_extensions();
        if (in_array("curl", $loadedExtensions)) {
            $this->logger->debug("HTTPRequest::__construct");
        } else {
            $this->logger->critical("HTTPRequest::__construct ERROR: curl extension not found");
            throw new \aportela\HTTPRequestWrapper\Exception\CurlMissingException("loaded extensions: " . implode(", ", $loadedExtensions));
        }
    }

    public function __destruct()
    {
        $this->logger->debug("HTTPRequest::__destruct");
    }

    public function GET(string $url, array $params = [], array $headers = []): \aportela\HTTPRequestWrapper\HTTPResponse
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $requestUrl = count($params) > 0 ? $url . '?' . http_build_query($params) : $url;
        $this->logger->debug("HTTPRequest::GET");
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        $this->logger->debug("Request URL: " . $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $responseHeaders = array();
        // https://stackoverflow.com/a/41135574
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$responseHeaders) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) { // ignore invalid headers
                    return $len;
                }
                $responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);
                return $len;
            }
        );
        if (!empty($this->userAgent)) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        }
        if (is_array($headers) && count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->logger->debug("Response code: " . $code);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->logger->debug("Response contentType: " . $contentType);
        curl_close($ch);
        $this->logger->debug("Response headers: " . print_r($responseHeaders, true));
        $this->logger->debug("Response body: " . $body);
        return (new HTTPResponse($code, $contentType, $responseHeaders, $body));
    }
}
