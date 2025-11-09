<?php

declare(strict_types=1);

namespace aportela\MediaWikiWrapper\Test;

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('curl')]
final class HTTPRequestTest extends \PHPUnit\Framework\TestCase
{
    private const string EXISTENT_URL = "https://raw.githubusercontent.com/aportela/httprequest-wrapper/main/src/HTTPRequest.php";
    
    private const string NON_EXISTENT_URL = "https://raw.githubusercontent.com/aportela/httprequest-wrapper/main/src/404_FILE_NOT_FOUND";

    private static \Psr\Log\LoggerInterface $logger;

    /**
     * Called once just like normal constructor
     */
    public static function setUpBeforeClass(): void
    {
        self::$logger = new \Psr\Log\NullLogger();
    }

    /**
     * Initialize the test case
     * Called for every defined test
     */
    protected function setUp(): void
    {
    }

    /**
     * Clean up the test case, called for every defined test
     */
    protected function tearDown(): void
    {
    }

    /**
     * Clean up the whole test class
     */
    public static function tearDownAfterClass(): void
    {
    }

    public function testHeadPackagistUrl(): void
    {
        $httpRequest = new \aportela\HTTPRequestWrapper\HTTPRequest(self::$logger);
        $httpResponse = $httpRequest->HEAD(self::EXISTENT_URL);
        $this->assertEquals($httpResponse->code, 200);
        $this->assertEquals($httpResponse->getContentType(), "text/plain; charset=utf-8");
        $this->assertTrue($httpResponse->is(\aportela\HTTPRequestWrapper\ContentType::TEXT_PLAIN));
        $this->assertEmpty($httpResponse->body);
    }

    public function testHeadNotFoundUrl(): void
    {
        $httpRequest = new \aportela\HTTPRequestWrapper\HTTPRequest(self::$logger);
        $httpResponse = $httpRequest->HEAD(self::NON_EXISTENT_URL);
        $this->assertEquals($httpResponse->code, 404);
    }

    public function testGetPackagistUrl(): void
    {
        $httpRequest = new \aportela\HTTPRequestWrapper\HTTPRequest(self::$logger);
        $httpResponse = $httpRequest->GET(self::EXISTENT_URL);
        $this->assertEquals($httpResponse->code, 200);
        $this->assertTrue($httpResponse->is(\aportela\HTTPRequestWrapper\ContentType::TEXT_PLAIN));
        $this->assertNotEmpty($httpResponse->body);
        $this->assertStringStartsWith("<?php", $httpResponse->body);
    }

    public function testGetNotFoundUrl(): void
    {
        $httpRequest = new \aportela\HTTPRequestWrapper\HTTPRequest(self::$logger);
        $httpResponse = $httpRequest->GET(self::NON_EXISTENT_URL);
        $this->assertEquals($httpResponse->code, 404);
    }

    public function testGetJsonContentTypeApi(): void
    {
        $httpRequest = new \aportela\HTTPRequestWrapper\HTTPRequest(self::$logger);
        $httpResponse = $httpRequest->GET("https://myfakeapi.com/api/users/1");
        $this->assertEquals($httpResponse->code, 200);
        $this->assertTrue($httpResponse->is(\aportela\HTTPRequestWrapper\ContentType::JSON));
    }
}
