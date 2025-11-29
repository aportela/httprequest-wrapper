<?php

declare(strict_types=1);

namespace aportela\HTTPRequestWrapper;

class HTTPRequest
{
    /**
     * @var array<mixed, mixed>
     */
    protected array $commonCurlOptions;

    protected string $userAgent;

    protected bool $useCookies;

    protected string $cookiesFilePath;

    public function __construct(protected \Psr\Log\LoggerInterface $logger, ?string $userAgent = null)
    {
        if (extension_loaded("curl")) {
            if (! function_exists('curl_version')) {
                $this->logger->critical(\aportela\HTTPRequestWrapper\HTTPRequest::class . '::__construct - Error: curl extension not found', get_loaded_extensions());
                throw new \aportela\HTTPRequestWrapper\Exception\CurlMissingException("loaded extensions: " . implode(", ", get_loaded_extensions()));
            }
        } else {
            $this->logger->critical("HTTPRequest::__construct ERROR: curl extension loaded, but curl functions not available");
            throw new \aportela\HTTPRequestWrapper\Exception\CurlMissingException("loaded extensions: " . implode(", ", get_loaded_extensions()));
        }

        $this->commonCurlOptions = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 3,
        ];
        if (!in_array($userAgent, [null, '', '0'], true)) {
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
        if ($this->cookiesFilePath !== '' && $this->cookiesFilePath !== '0' && file_exists(($this->cookiesFilePath))) {
            @unlink($this->cookiesFilePath);
        }
    }

    public function setUserAgent(?string $userAgent = null): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        if (!in_array($userAgent, [null, '', '0'], true)) {
            $this->commonCurlOptions[CURLOPT_USERAGENT] = $userAgent;
        } elseif (array_key_exists(CURLOPT_USERAGENT, $this->commonCurlOptions)) {
            unset($this->commonCurlOptions[CURLOPT_USERAGENT]);
        }

        return ($this);
    }

    public function setReferer(?string $referer = null): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        if (!in_array($referer, [null, '', '0'], true)) {
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
        $this->cookiesFilePath = in_array($path, [null, '', '0'], true) ? tempnam(sys_get_temp_dir(), "HTTP_REQUEST_WRAPPER") : $path;

        $this->enableCookies();
        return ($this);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function setHeaders(array $headers = []): \aportela\HTTPRequestWrapper\HTTPRequest
    {
        if ($headers !== []) {
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

        foreach ($curlOptions as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        $responseHeaders = [];
        // https://stackoverflow.com/a/41135574
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$responseHeaders): int {
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
            $this->logger->debug(\aportela\HTTPRequestWrapper\HTTPRequest::class . '::curlExec - Response code: ' . $code);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $this->logger->debug(\aportela\HTTPRequestWrapper\HTTPRequest::class . '::curlExec - Response contentType: ' . $contentType);
            $cookies = curl_getinfo($ch, CURLINFO_COOKIELIST);
            $this->logger->debug(\aportela\HTTPRequestWrapper\HTTPRequest::class . '::curlExec - Response cookies: ' . print_r($cookies, true));
            curl_close($ch);
            $this->logger->debug(\aportela\HTTPRequestWrapper\HTTPRequest::class . '::curlExec - Response headers: ' . print_r($responseHeaders, true));
            $this->logger->debug(\aportela\HTTPRequestWrapper\HTTPRequest::class . '::curlExec - Response body: ' . $body);
            return (new HTTPResponse($code, $contentType, $responseHeaders, (string) $body));
        } else {
            $this->logger->error(\aportela\HTTPRequestWrapper\HTTPRequest::class . '::curlExec - Error', [curl_errno($ch), curl_error($ch), curl_getinfo($ch)]);
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

        $requestUrl = $params !== [] ? $url . '?' . http_build_query($params) : $url;
        $this->logger->debug(\aportela\HTTPRequestWrapper\HTTPRequest::class . '::HEAD - URL: ' . $requestUrl);
        $curlHEADOptions = [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $requestUrl,
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

        $requestUrl = $params !== [] ? $url . '?' . http_build_query($params) : $url;
        $this->logger->debug(\aportela\HTTPRequestWrapper\HTTPRequest::class . '::GET - URL: ' . $requestUrl);
        $curlGETOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $requestUrl,
        ];
        $this->setHeaders($headers);
        $this->setReferer($referer);
        return ($this->curlExec($curlGETOptions));
    }
}
