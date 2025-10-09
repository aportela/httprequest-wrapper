<?php

namespace aportela\HTTPRequestWrapper;

class HTTPRequest
{
    protected \Psr\Log\LoggerInterface $logger;
    protected array $commonCurlOptions;
    protected string $userAgent;
    protected bool $useCookies;
    protected string $cookiesFilePath;

    public function __construct(\Psr\Log\LoggerInterface $logger, string $userAgent = "")
    {
        $this->logger = $logger;
        $this->commonCurlOptions = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 3
        ];
        if (!empty($userAgent)) {
            $this->commonCurlOptions[CURLOPT_USERAGENT] = $userAgent;
        } else {
            $this->commonCurlOptions[CURLOPT_USERAGENT] = \aportela\HTTPRequestWrapper\UserAgent::DEFAULT->value;
        }
        $this->useCookies = true;
        $this->cookiesFilePath = tempnam(sys_get_temp_dir(), "HTTP_REQUEST_WRAPPER");
        $this->commonCurlOptions[CURLOPT_COOKIEFILE] = $this->cookiesFilePath;
        $this->commonCurlOptions[CURLOPT_COOKIEJAR] = $this->cookiesFilePath;
        if (extension_loaded("curl")) {
            if (function_exists('curl_version')) {
                $this->logger->debug("HTTPRequest::__construct");
            } else {
                $this->logger->critical("HTTPRequest::__construct ERROR: curl extension not found");
                throw new \aportela\HTTPRequestWrapper\Exception\CurlMissingException("loaded extensions: " . implode(", ", get_loaded_extensions()));
            }
        } else {
            $this->logger->critical("HTTPRequest::__construct ERROR: curl extension loaded, but curl functions not available");
            throw new \aportela\HTTPRequestWrapper\Exception\CurlMissingException("loaded extensions: " . implode(", ", get_loaded_extensions()));
        }
    }

    public function __destruct()
    {
        $this->logger->debug("HTTPRequest::__destruct");
        if (! empty($this->cookiesFilePath) && file_exists(($this->cookiesFilePath))) {
            @unlink($this->cookiesFilePath);
        }
    }

    public function setUserAgent(string $userAgent = ""): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        $this->logger->debug("HTTPRequest::setUserAgent " . $userAgent);
        if (!empty($userAgent)) {
            $this->commonCurlOptions[CURLOPT_USERAGENT] = $userAgent;
        } elseif (array_key_exists(CURLOPT_USERAGENT, $this->commonCurlOptions)) {
            unset($this->commonCurlOptions[CURLOPT_USERAGENT]);
        }
        return ($this);
    }

    public function setReferer(string $referer = ""): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        $this->logger->debug("HTTPRequest::setReferer " . $referer);
        if (!empty($referer)) {
            if (filter_var($referer, FILTER_VALIDATE_URL)) {
                $this->commonCurlOptions[CURLOPT_REFERER] = $referer;
            } else {
                throw new \aportela\HTTPRequestWrapper\Exception\InvalidParamException("referer");
            }
        } else {
            unset($this->commonCurlOptions[CURLOPT_REFERER]);
        }
        return ($this);
    }

    public function enableCookies(): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        $this->commonCurlOptions[CURLOPT_COOKIEFILE] = $this->cookiesFilePath;
        $this->commonCurlOptions[CURLOPT_COOKIEJAR] = $this->cookiesFilePath;
        return ($this);
    }

    public function disableCookies(): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        unset($this->commonCurlOptions[CURLOPT_COOKIEFILE]);
        unset($this->commonCurlOptions[CURLOPT_COOKIEJAR]);
        return ($this);
    }

    public function setCookiesFilePath(string $path = ""): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        if (!empty($path)) {
            $this->cookiesFilePath = $path;
        } else {
            $this->cookiesFilePath = tempnam(sys_get_temp_dir(), "HTTP_REQUEST_WRAPPER");
        }
        $this->enableCookies();
        return ($this);
    }

    public function setHeaders(array $headers = []): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        if (is_array($headers) && count($headers) > 0) {
            $this->commonCurlOptions[CURLOPT_HTTPHEADER] = $headers;
        } else {
            unset($this->commonCurlOptions[CURLOPT_HTTPHEADER]);
        }
        return ($this);
    }

    private function curlExec(array $curlOptions = []): \aportela\HTTPRequestWrapper\HTTPResponse
    {
        $ch = curl_init();
        foreach ($this->commonCurlOptions as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        if (is_array($curlOptions) && count($curlOptions) > 0) {
            foreach ($curlOptions as $key => $value) {
                curl_setopt($ch, $key, $value);
            }
        }
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
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->logger->debug("Response code: " . $code);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->logger->debug("Response contentType: " . $contentType);
        $cookies = curl_getinfo($ch, CURLINFO_COOKIELIST);
        $this->logger->debug("Response cookies: " . print_r($cookies, true));
        curl_close($ch);
        $this->logger->debug("Response headers: " . print_r($responseHeaders, true));
        $this->logger->debug("Response body: " . $body);
        return (new HTTPResponse($code, $contentType, $responseHeaders, $body));
    }

    public function HEAD(string $url, array $params = [], array $headers = [], string $referer = ""): \aportela\HTTPRequestWrapper\HTTPResponse
    {
        $this->logger->debug("HTTPRequest::HEAD");
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \aportela\HTTPRequestWrapper\Exception\InvalidParamException("url");
        }
        $requestUrl = count($params) > 0 ? $url . '?' . http_build_query($params) : $url;
        $this->logger->debug("Request URL: " . $requestUrl);
        $curlHEADOptions = [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $requestUrl
        ];
        $this->setHeaders($headers);
        $this->setReferer($referer);
        return ($this->curlExec($curlHEADOptions));
    }

    public function GET(string $url, array $params = [], array $headers = [], string $referer = ""): \aportela\HTTPRequestWrapper\HTTPResponse
    {
        $this->logger->debug("HTTPRequest::GET");
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \aportela\HTTPRequestWrapper\Exception\InvalidParamException("url");
        }
        $requestUrl = count($params) > 0 ? $url . '?' . http_build_query($params) : $url;
        $this->logger->debug("Request URL: " . $requestUrl);
        $curlGETOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $requestUrl
        ];
        $this->setHeaders($headers);
        $this->setReferer($referer);
        return ($this->curlExec($curlGETOptions));
    }
}
