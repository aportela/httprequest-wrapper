<?php

namespace aportela\HTTPRequestWrapper;

class HTTPRequest
{
    protected \Psr\Log\LoggerInterface $logger;
    /**
     * @var array<mixed, mixed>
     */
    protected array $commonCurlOptions;
    protected string $userAgent;
    protected bool $useCookies;
    protected string $cookiesFilePath;

    public function __construct(\Psr\Log\LoggerInterface $logger, ?string $userAgent = null)
    {
        $this->logger = $logger;
        if (extension_loaded("curl")) {
            if (! function_exists('curl_version')) {
                $this->logger->critical("aportela\HTTPRequestWrapper\HTTPRequest::__construct - Error: curl extension not found", get_loaded_extensions());
                throw new \aportela\HTTPRequestWrapper\Exception\CurlMissingException("loaded extensions: " . implode(", ", get_loaded_extensions()));
            }
        } else {
            $this->logger->critical("HTTPRequest::__construct ERROR: curl extension loaded, but curl functions not available");
            throw new \aportela\HTTPRequestWrapper\Exception\CurlMissingException("loaded extensions: " . implode(", ", get_loaded_extensions()));
        }
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
    }

    public function __destruct()
    {
        if (! empty($this->cookiesFilePath) && file_exists(($this->cookiesFilePath))) {
            @unlink($this->cookiesFilePath);
        }
    }

    public function setUserAgent(?string $userAgent = null): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        if (!empty($userAgent)) {
            $this->commonCurlOptions[CURLOPT_USERAGENT] = $userAgent;
        } elseif (array_key_exists(CURLOPT_USERAGENT, $this->commonCurlOptions)) {
            unset($this->commonCurlOptions[CURLOPT_USERAGENT]);
        }
        return ($this);
    }

    public function setReferer(?string $referer = null): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        if (!empty($referer)) {
            if (filter_var($referer, FILTER_VALIDATE_URL)) {
                $this->commonCurlOptions[CURLOPT_REFERER] = $referer;
            } else {
                throw new \InvalidArgumentException("referer");
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

    public function setCookiesFilePath(?string $path = null): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        if (!empty($path)) {
            $this->cookiesFilePath = $path;
        } else {
            $this->cookiesFilePath = tempnam(sys_get_temp_dir(), "HTTP_REQUEST_WRAPPER");
        }
        $this->enableCookies();
        return ($this);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function setHeaders(array $headers = []): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        if (count($headers) > 0) {
            $this->commonCurlOptions[CURLOPT_HTTPHEADER] = $headers;
        } else {
            unset($this->commonCurlOptions[CURLOPT_HTTPHEADER]);
        }
        return ($this);
    }

    /**
     * @param array <mixed, mixed> $curlOptions
     */
    private function curlExec(array $curlOptions = []): \aportela\HTTPRequestWrapper\HTTPResponse
    {
        $ch = curl_init();
        foreach ($this->commonCurlOptions as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        if (count($curlOptions) > 0) {
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
                if (is_string($header)) {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2) { // ignore invalid headers
                        return $len;
                    }
                    $responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);
                    return $len;
                } else {
                    return (0);
                }
            }
        );
        $body = curl_exec($ch);
        if ($body !== false) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->logger->debug("aportela\HTTPRequestWrapper\HTTPRequest::curlExec - Response code: " . $code);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $this->logger->debug("aportela\HTTPRequestWrapper\HTTPRequest::curlExec - Response contentType: " . $contentType);
            $cookies = curl_getinfo($ch, CURLINFO_COOKIELIST);
            $this->logger->debug("aportela\HTTPRequestWrapper\HTTPRequest::curlExec - Response cookies: " . print_r($cookies, true));
            curl_close($ch);
            $this->logger->debug("aportela\HTTPRequestWrapper\HTTPRequest::curlExec - Response headers: " . print_r($responseHeaders, true));
            $this->logger->debug("aportela\HTTPRequestWrapper\HTTPRequest::curlExec - Response body: " . $body);
            return (new HTTPResponse($code, $contentType, $responseHeaders, (string) $body));
        } else {
            $this->logger->error("aportela\HTTPRequestWrapper\HTTPRequest::curlExec - Error", [curl_errno($ch), curl_error($ch), curl_getinfo($ch)]);
            throw new \aportela\HTTPRequestWrapper\Exception\CurlExecException(curl_error($ch), curl_errno($ch));
        }
    }

    /**
     * @param array<mixed, mixed> $params
     * @param array<string, mixed> $headers
     */
    public function HEAD(string $url, array $params = [], array $headers = [], ?string $referer = null): \aportela\HTTPRequestWrapper\HTTPResponse
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("url");
        }
        $requestUrl = count($params) > 0 ? $url . '?' . http_build_query($params) : $url;
        $this->logger->debug("aportela\HTTPRequestWrapper\HTTPRequest::HEAD - URL: " . $requestUrl);
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

    /**
     * @param array<mixed, mixed> $params
     * @param array<string, mixed> $headers
     */
    public function GET(string $url, array $params = [], array $headers = [], ?string $referer = null): \aportela\HTTPRequestWrapper\HTTPResponse
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("url");
        }
        $requestUrl = count($params) > 0 ? $url . '?' . http_build_query($params) : $url;
        $this->logger->debug("aportela\HTTPRequestWrapper\HTTPRequest::GET - URL: " . $requestUrl);
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
