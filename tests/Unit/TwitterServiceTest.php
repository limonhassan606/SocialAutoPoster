<?php

namespace LimonHasan\SocialAutoPoster\Tests\Unit;

use LimonHasan\SocialAutoPoster\Tests\Unit\TestCase;
use LimonHasan\SocialAutoPoster\Services\TwitterService;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwitterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'autopost.twitter_bearer_token' => 'test_bearer_token',
            'autopost.twitter_api_key' => 'test_api_key',
            'autopost.twitter_api_secret' => 'test_api_secret',
            'autopost.twitter_access_token' => 'test_access_token',
            'autopost.twitter_access_token_secret' => 'test_access_token_secret',
        ]);
    }

    public function testTwitterServiceSingleton()
    {
        $service1 = TwitterService::getInstance();
        $service2 = TwitterService::getInstance();
        
        $this->assertSame($service1, $service2);
    }

    public function testTwitterServiceWithMissingCredentials()
    {
        config(['autopost.twitter_bearer_token' => null]);
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Twitter API credentials are not fully configured');
        
        TwitterService::getInstance();
    }

    public function testShareSuccess()
    {
        Http::fake([
            'https://api.twitter.com/2/tweets' => Http::response(['data' => ['id' => '123']], 200),
        ]);

        $service = TwitterService::getInstance();
        $result = $service->share('Test tweet', 'https://example.com');

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('123', $result['data']['id']);
    }

    public function testShareImageSuccess()
    {
        Http::fake([
            'https://upload.twitter.com/1.1/media/upload.json' => Http::response(['media_id_string' => 'media123'], 200),
            'https://api.twitter.com/2/tweets' => Http::response(['data' => ['id' => '456']], 200),
        ]);

        $service = TwitterService::getInstance();
        $result = $service->shareImage('Test image tweet', 'https://example.com/image.jpg');

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('456', $result['data']['id']);
    }

    public function testShareVideoSuccess()
    {
        Http::fake([
            'https://upload.twitter.com/1.1/media/upload.json' => Http::response(['media_id_string' => 'media123'], 200),
            'https://api.twitter.com/2/tweets' => Http::response(['data' => ['id' => '789']], 200),
        ]);

        $service = TwitterService::getInstance();
        $result = $service->shareVideo('Test video tweet', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('789', $result['data']['id']);
    }

    public function testGetTimelineSuccess()
    {
        Http::fake([
            'https://api.twitter.com/2/users/me/tweets' => Http::response([
                'data' => [
                    ['id' => 'tweet1', 'text' => 'Tweet 1'],
                    ['id' => 'tweet2', 'text' => 'Tweet 2']
                ]
            ], 200),
        ]);

        $service = TwitterService::getInstance();
        $result = $service->getTimeline(5);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
    }

    public function testGetUserInfoSuccess()
    {
        Http::fake([
            'https://api.twitter.com/2/users/me' => Http::response([
                'data' => [
                    'id' => 'user123',
                    'username' => 'testuser',
                    'name' => 'Test User'
                ]
            ], 200),
        ]);

        $service = TwitterService::getInstance();
        $result = $service->getUserInfo();

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('testuser', $result['data']['username']);
    }

    public function testShareWithEmptyCaption()
    {
        $service = TwitterService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Caption cannot be empty');
        
        $service->share('', 'https://example.com');
    }

    public function testShareWithCaptionTooLong()
    {
        $service = TwitterService::getInstance();
        $longCaption = str_repeat('a', 281); // Over 280 character limit
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content exceeds maximum length of 280 characters');
        
        $service->share($longCaption, 'https://example.com');
    }

    public function testShareWithInvalidUrl()
    {
        $service = TwitterService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Invalid URL provided');
        
        $service->share('Test tweet', 'invalid-url');
    }

    public function testShareWithApiError()
    {
        Http::fake([
            'https://api.twitter.com/2/tweets' => Http::response([
                'errors' => [['message' => 'Invalid access token']]
            ], 401),
        ]);

        $service = TwitterService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share to Twitter');
        
        $service->share('Test tweet', 'https://example.com');
    }

    public function testShareImageWithApiError()
    {
        Http::fake([
            'https://upload.twitter.com/1.1/media/upload.json' => Http::response([
                'errors' => [['message' => 'Invalid image']]
            ], 400),
        ]);

        $service = TwitterService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share image to Twitter');
        
        $service->shareImage('Test image tweet', 'https://example.com/image.jpg');
    }

    public function testShareVideoWithApiError()
    {
        Http::fake([
            'https://upload.twitter.com/1.1/media/upload.json' => Http::response([
                'errors' => [['message' => 'Invalid video']]
            ], 400),
        ]);

        $service = TwitterService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share video to Twitter');
        
        $service->shareVideo('Test video tweet', 'https://example.com/video.mp4');
    }

    public function testGetTimelineWithApiError()
    {
        Http::fake([
            'https://api.twitter.com/2/users/me/tweets' => Http::response([
                'errors' => [['message' => 'Invalid request']]
            ], 400),
        ]);

        $service = TwitterService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get Twitter timeline');
        
        $service->getTimeline(5);
    }

    public function testGetUserInfoWithApiError()
    {
        Http::fake([
            'https://api.twitter.com/2/users/me' => Http::response([
                'errors' => [['message' => 'Invalid user']]
            ], 400),
        ]);

        $service = TwitterService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get Twitter user info');
        
        $service->getUserInfo();
    }

    public function testLoggingOnSuccess()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Twitter post shared successfully', \Mockery::type('array'));

        Http::fake([
            'https://api.twitter.com/2/tweets' => Http::response(['data' => ['id' => '123']], 200),
        ]);

        $service = TwitterService::getInstance();
        $service->share('Test tweet', 'https://example.com');
    }

    public function testLoggingOnError()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to share to Twitter', \Mockery::type('array'));

        Http::fake([
            'https://api.twitter.com/2/tweets' => Http::response([
                'errors' => [['message' => 'API Error']]
            ], 400),
        ]);

        $service = TwitterService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $service->share('Test tweet', 'https://example.com');
    }

    public function testRetryLogic()
    {
        Http::fake([
            'https://api.twitter.com/2/tweets' => Http::sequence()
                ->push(['errors' => [['message' => 'Rate limited']]], 429)
                ->push(['errors' => [['message' => 'Rate limited']]], 429)
                ->push(['data' => ['id' => '123']], 200),
        ]);

        $service = TwitterService::getInstance();
        $result = $service->share('Test tweet', 'https://example.com');

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('123', $result['data']['id']);
    }

    public function testMediaUploadWithChunkedVideo()
    {
        Http::fake([
            'https://upload.twitter.com/1.1/media/upload.json' => Http::response(['media_id_string' => 'media123'], 200),
            'https://api.twitter.com/2/tweets' => Http::response(['data' => ['id' => '456']], 200),
        ]);

        $service = TwitterService::getInstance();
        $result = $service->shareVideo('Test video tweet', 'https://example.com/large-video.mp4');

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('456', $result['data']['id']);
    }

    public function testTimeoutConfiguration()
    {
        config(['autopost.timeout' => 60]);

        Http::fake([
            'https://api.twitter.com/2/tweets' => Http::response(['data' => ['id' => '123']], 200),
        ]);

        $service = TwitterService::getInstance();
        $service->share('Test tweet', 'https://example.com');

        Http::assertSent(function ($request) {
            return $request->timeout() === 60;
        });
    }
}
