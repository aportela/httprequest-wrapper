<?php

namespace aportela\HTTPRequestWrapper;

class HTTPRequest
{
    protected \Psr\Log\LoggerInterface $logger;
    protected array $commonCurlOptions;
    protected string $userAgent;
    protected bool $useCookies;
    protected string $cookiesFilePath;

    public const DEFAULT_USER_AGENT = "HTTPRequest-Wrapper - https://github.com/aportela/httprequest-wrapper (766f6964+github@gmail.com)";

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
            $this->commonCurlOptions[CURLOPT_USERAGENT] = self::DEFAULT_USER_AGENT;
        }
        $this->useCookies = true;
        if ($this->useCookies) {
            $this->cookiesFilePath = tempnam(sys_get_temp_dir(), "HTTP_REQUEST_WRAPPER");
            $this->commonCurlOptions[CURLOPT_COOKIEFILE] = $this->cookiesFilePath;
            $this->commonCurlOptions[CURLOPT_COOKIEJAR] = $this->cookiesFilePath;
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

    public function setUserAgent(string $userAgent = ""): HTTPRequest
    {
        $this->logger->debug("HTTPRequest::setUserAgent " . $userAgent);
        if (!empty($userAgent)) {
            $this->commonCurlOptions[CURLOPT_USERAGENT] = $userAgent;
        } elseif (array_key_exists(CURLOPT_USERAGENT, $this->commonCurlOptions)) {
            unset($this->commonCurlOptions[CURLOPT_USERAGENT]);
        }
        return ($this);
    }

    public function setReferer(string $referer = ""): HTTPRequest
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

    public function enableCookies(): HTTPRequest
    {
        $this->commonCurlOptions[CURLOPT_COOKIEFILE] = $this->cookiesFilePath;
        $this->commonCurlOptions[CURLOPT_COOKIEJAR] = $this->cookiesFilePath;
        return ($this);
    }

    public function disableCookies(): HTTPRequest
    {
        unset($this->commonCurlOptions[CURLOPT_COOKIEFILE]);
        unset($this->commonCurlOptions[CURLOPT_COOKIEJAR]);
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
        if (is_array($headers) && count($headers) > 0) {
            $curlHEADOptions[CURLOPT_HTTPHEADER] = $headers;
        }
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
        return ($this->curlExec($curlGETOptions));
    }
}
