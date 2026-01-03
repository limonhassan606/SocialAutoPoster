<?php

namespace LimonHasan\SocialAutoPoster\Tests\Unit;

use LimonHasan\SocialAutoPoster\Tests\Unit\TestCase;
use LimonHasan\SocialAutoPoster\Services\SocialMediaService;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the abstract SocialMediaService for testing
        $this->service = new class extends SocialMediaService {
            public function testSendRequest($url, $method = 'post', $params = [], $headers = [])
            {
                return $this->sendRequest($url, $method, $params, $headers);
            }
            
            public function testValidateUrl($url)
            {
                return $this->validateUrl($url);
            }
            
            public function testValidateText($text, $maxLength = 1000)
            {
                return $this->validateText($text, $maxLength);
            }
            
            public function testDownloadFile($url)
            {
                return $this->downloadFile($url);
            }
        };
    }

    public function testSendRequestSuccess()
    {
        Http::fake([
            'https://api.example.com/test' => Http::response(['success' => true, 'id' => '123'], 200),
        ]);

        $result = $this->service->testSendRequest('https://api.example.com/test', 'post', ['test' => 'data']);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('123', $result['id']);
    }

    public function testSendRequestWithRetry()
    {
        Http::fake([
            'https://api.example.com/test' => Http::sequence()
                ->push(['error' => 'Rate limited'], 429)
                ->push(['error' => 'Rate limited'], 429)
                ->push(['success' => true, 'id' => '123'], 200),
        ]);

        $result = $this->service->testSendRequest('https://api.example.com/test', 'post', ['test' => 'data']);

        $this->assertArrayHasKey('success', $result);
        $this->assertEquals('123', $result['id']);
    }

    public function testSendRequestMaxRetriesExceeded()
    {
        Http::fake([
            'https://api.example.com/test' => Http::response(['error' => 'Server error'], 500),
        ]);

        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('API request failed');

        $this->service->testSendRequest('https://api.example.com/test', 'post', ['test' => 'data']);
    }

    public function testValidateUrlValid()
    {
        $this->expectNotToPerformAssertions();
        
        $this->service->testValidateUrl('https://example.com');
        $this->service->testValidateUrl('http://example.com');
        $this->service->testValidateUrl('https://subdomain.example.com/path?query=value');
    }

    public function testValidateUrlInvalid()
    {
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Invalid URL provided');

        $this->service->testValidateUrl('invalid-url');
    }

    public function testValidateUrlEmpty()
    {
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Invalid URL provided');

        $this->service->testValidateUrl('');
    }

    public function testValidateTextValid()
    {
        $this->expectNotToPerformAssertions();
        
        $this->service->testValidateText('Valid text');
        $this->service->testValidateText('Valid text with special characters: @#$%^&*()');
        $this->service->testValidateText(str_repeat('a', 1000), 1000);
    }

    public function testValidateTextEmpty()
    {
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content cannot be empty');

        $this->service->testValidateText('');
    }

    public function testValidateTextWhitespaceOnly()
    {
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content cannot be empty');

        $this->service->testValidateText('   ');
    }

    public function testValidateTextTooLong()
    {
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content exceeds maximum length of 100 characters');

        $this->service->testValidateText(str_repeat('a', 101), 100);
    }

    public function testDownloadFileSuccess()
    {
        Http::fake([
            'https://example.com/file.txt' => Http::response('File content', 200),
        ]);

        $content = $this->service->testDownloadFile('https://example.com/file.txt');

        $this->assertEquals('File content', $content);
    }

    public function testDownloadFileInvalidUrl()
    {
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Invalid URL provided');

        $this->service->testDownloadFile('invalid-url');
    }

    public function testDownloadFileNotFound()
    {
        Http::fake([
            'https://example.com/notfound.txt' => Http::response('Not Found', 404),
        ]);

        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to download file from URL');

        $this->service->testDownloadFile('https://example.com/notfound.txt');
    }

    public function testLoggingEnabled()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Social media API request successful', \Mockery::type('array'));

        Http::fake([
            'https://api.example.com/test' => Http::response(['success' => true], 200),
        ]);

        $this->service->testSendRequest('https://api.example.com/test', 'post', ['test' => 'data']);
    }

    public function testLoggingOnError()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Social media API request failed after all retries', \Mockery::type('array'));

        Http::fake([
            'https://api.example.com/test' => Http::response(['error' => 'Server error'], 500),
        ]);

        $this->expectException(SocialMediaException::class);

        $this->service->testSendRequest('https://api.example.com/test', 'post', ['test' => 'data']);
    }

    public function testTimeoutConfiguration()
    {
        config(['autopost.timeout' => 60]);

        Http::fake([
            'https://api.example.com/test' => Http::response(['success' => true], 200),
        ]);

        $this->service->testSendRequest('https://api.example.com/test', 'post', ['test' => 'data']);

        Http::assertSent(function ($request) {
            return $request->timeout() === 60;
        });
    }

    public function testRetryAttemptsConfiguration()
    {
        config(['autopost.retry_attempts' => 5]);

        Http::fake([
            'https://api.example.com/test' => Http::response(['error' => 'Server error'], 500),
        ]);

        $this->expectException(SocialMediaException::class);

        $this->service->testSendRequest('https://api.example.com/test', 'post', ['test' => 'data']);

        // Should make 5 attempts
        Http::assertSentCount(5);
    }
}
