<?php

namespace aportela\HTTPRequestWrapper;

class HTTPRequest
{

    protected $logger;
    protected $userAgent;

    const DEFAULT_USER_AGENT = "HTTPRequest-Wrapper - https://github.com/aportela/httprequest-wrapper (766f6964+github@gmail.com)";

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

    public function GET(string $url, array $params = [], array $headers = [])
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
        if (!empty($this->userAgent)) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        }
        $response = new \stdClass();
        $response->content = curl_exec($ch);
        $response->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response->contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        $this->logger->debug("Response code: " . $response->code);
        $this->logger->debug("Response contentType: " . $response->contentType);
        $this->logger->debug("Response content: " . $response->content);
        return ($response);
    }
}
