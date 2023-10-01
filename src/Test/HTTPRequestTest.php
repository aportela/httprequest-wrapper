<?php

namespace aportela\MediaWikiWrapper\Test;

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

class HTTPRequestTest extends \PHPUnit\Framework\TestCase
{
    private const EXISTENT_URL = "https://raw.githubusercontent.com/aportela/httprequest-wrapper/main/src/HTTPRequest.php";
    private const NON_EXISTENT_URL = "https://raw.githubusercontent.com/aportela/httprequest-wrapper/main/src/404_FILE_NOT_FOUND";

    protected static $logger;

    /**
     * Called once just like normal constructor
     */
    public static function setUpBeforeClass(): void
    {
        self::$logger = new \Psr\Log\NullLogger("");
    }

    /**
     * Initialize the test case
     * Called for every defined test
     */
    public function setUp(): void
    {
    }

    /**
     * Clean up the test case, called for every defined test
     */
    public function tearDown(): void
    {
    }

    /**
     * Clean up the whole test class
     */
    public static function tearDownAfterClass(): void
    {
    }

    public function testHEADPackagistURL(): void
    {
        $http = new \aportela\HTTPRequestWrapper\HTTPRequest(self::$logger, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1");
        $response = $http->HEAD(self::EXISTENT_URL);
        $this->assertEquals($response->code, 200);
        $this->assertEquals($response->getContentType(), "text/plain; charset=utf-8");
        $this->assertTrue($response->hasHeader("content-type"));
        $headerValues = $response->getHeaderValues("content-type");
        $this->assertIsArray($headerValues);
        $this->assertContains("text/plain; charset=utf-8", $headerValues);
        $this->assertEmpty($response->body);
    }

    public function testHEADNotFoundURL(): void
    {
        $http = new \aportela\HTTPRequestWrapper\HTTPRequest(self::$logger, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1");
        $response = $http->HEAD(self::NON_EXISTENT_URL);
        $this->assertEquals($response->code, 404);
    }

    public function testGETPackagistURL(): void
    {
        $http = new \aportela\HTTPRequestWrapper\HTTPRequest(self::$logger, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1");
        $response = $http->GET(self::EXISTENT_URL);
        $this->assertEquals($response->code, 200);
        $this->assertEquals($response->getContentType(), "text/plain; charset=utf-8");
        $this->assertTrue($response->hasHeader("content-type"));
        $headerValues = $response->getHeaderValues("content-type");
        $this->assertIsArray($headerValues);
        $this->assertContains("text/plain; charset=utf-8", $headerValues);
        $this->assertStringStartsWith("<?php", $response->body);
    }

    public function testGETNotFoundURL(): void
    {
        $http = new \aportela\HTTPRequestWrapper\HTTPRequest(self::$logger, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1");
        $response = $http->GET(self::NON_EXISTENT_URL);
        $this->assertEquals($response->code, 404);
    }
}
