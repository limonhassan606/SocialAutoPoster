<?php

namespace LimonHasan\SocialAutoPoster\Tests\Unit;

use LimonHasan\SocialAutoPoster\Tests\Unit\TestCase;
use LimonHasan\SocialAutoPoster\Services\TikTokService;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'autopost.tiktok_access_token' => 'test_tiktok_token',
            'autopost.tiktok_client_key' => 'test_client_key',
            'autopost.tiktok_client_secret' => 'test_client_secret',
        ]);
    }

    public function testTikTokServiceSingleton()
    {
        $service1 = TikTokService::getInstance();
        $service2 = TikTokService::getInstance();
        
        $this->assertSame($service1, $service2);
    }

    public function testTikTokServiceWithMissingCredentials()
    {
        config(['autopost.tiktok_access_token' => null]);
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('TikTok API credentials are not fully configured');
        
        TikTokService::getInstance();
    }

    public function testShareVideoSuccess()
    {
        Http::fake([
            'https://open-api.tiktok.com/share/video/upload/' => Http::response([
                'data' => ['video_id' => 'tiktok123']
            ], 200),
        ]);

        $service = TikTokService::getInstance();
        $result = $service->shareVideo('Test TikTok video', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('tiktok123', $result['data']['video_id']);
    }

    public function testGetUserInfoSuccess()
    {
        Http::fake([
            'https://open-api.tiktok.com/user/info/' => Http::response([
                'data' => [
                    'display_name' => 'Test User',
                    'follower_count' => 1000,
                    'following_count' => 500
                ]
            ], 200),
        ]);

        $service = TikTokService::getInstance();
        $result = $service->getUserInfo();

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Test User', $result['data']['display_name']);
    }

    public function testGetUserVideosSuccess()
    {
        Http::fake([
            'https://open-api.tiktok.com/video/list/' => Http::response([
                'data' => [
                    'videos' => [
                        ['video_id' => 'video1', 'title' => 'Video 1'],
                        ['video_id' => 'video2', 'title' => 'Video 2']
                    ]
                ]
            ], 200),
        ]);

        $service = TikTokService::getInstance();
        $result = $service->getUserVideos(20);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('videos', $result['data']);
        $this->assertCount(2, $result['data']['videos']);
    }

    public function testShareVideoWithEmptyCaption()
    {
        $service = TikTokService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Caption cannot be empty');
        
        $service->shareVideo('', 'https://example.com/video.mp4');
    }

    public function testShareVideoWithCaptionTooLong()
    {
        $service = TikTokService::getInstance();
        $longCaption = str_repeat('a', 2201); // Over 2200 character limit
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content exceeds maximum length of 2200 characters');
        
        $service->shareVideo($longCaption, 'https://example.com/video.mp4');
    }

    public function testShareVideoWithInvalidUrl()
    {
        $service = TikTokService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Invalid URL provided');
        
        $service->shareVideo('Test video', 'invalid-url');
    }

    public function testShareVideoWithApiError()
    {
        Http::fake([
            'https://open-api.tiktok.com/share/video/upload/' => Http::response([
                'error' => ['message' => 'Invalid access token']
            ], 401),
        ]);

        $service = TikTokService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share video to TikTok');
        
        $service->shareVideo('Test TikTok video', 'https://example.com/video.mp4');
    }

    public function testGetUserInfoWithApiError()
    {
        Http::fake([
            'https://open-api.tiktok.com/user/info/' => Http::response([
                'error' => ['message' => 'Invalid user request']
            ], 400),
        ]);

        $service = TikTokService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get TikTok user info');
        
        $service->getUserInfo();
    }

    public function testGetUserVideosWithApiError()
    {
        Http::fake([
            'https://open-api.tiktok.com/video/list/' => Http::response([
                'error' => ['message' => 'Invalid video request']
            ], 400),
        ]);

        $service = TikTokService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get TikTok user videos');
        
        $service->getUserVideos(20);
    }

    public function testLoggingOnSuccess()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('TikTok video post shared successfully', \Mockery::type('array'));

        Http::fake([
            'https://open-api.tiktok.com/share/video/upload/' => Http::response([
                'data' => ['video_id' => 'tiktok123']
            ], 200),
        ]);

        $service = TikTokService::getInstance();
        $service->shareVideo('Test TikTok video', 'https://example.com/video.mp4');
    }

    public function testLoggingOnError()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to share video to TikTok', \Mockery::type('array'));

        Http::fake([
            'https://open-api.tiktok.com/share/video/upload/' => Http::response([
                'error' => ['message' => 'API Error']
            ], 400),
        ]);

        $service = TikTokService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $service->shareVideo('Test TikTok video', 'https://example.com/video.mp4');
    }

    public function testRetryLogic()
    {
        Http::fake([
            'https://open-api.tiktok.com/share/video/upload/' => Http::sequence()
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['data' => ['video_id' => 'tiktok123']], 200),
        ]);

        $service = TikTokService::getInstance();
        $result = $service->shareVideo('Test TikTok video', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('tiktok123', $result['data']['video_id']);
    }

    public function testTimeoutConfiguration()
    {
        config(['autopost.timeout' => 60]);

        Http::fake([
            'https://open-api.tiktok.com/share/video/upload/' => Http::response([
                'data' => ['video_id' => 'tiktok123']
            ], 200),
        ]);

        $service = TikTokService::getInstance();
        $service->shareVideo('Test TikTok video', 'https://example.com/video.mp4');

        Http::assertSent(function ($request) {
            return $request->timeout() === 60;
        });
    }

    public function testVideoUploadWithLargeFile()
    {
        Http::fake([
            'https://open-api.tiktok.com/share/video/upload/' => Http::response([
                'data' => ['video_id' => 'tiktok123']
            ], 200),
        ]);

        $service = TikTokService::getInstance();
        $result = $service->shareVideo('Test large video', 'https://example.com/large-video.mp4');

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('tiktok123', $result['data']['video_id']);
    }

    public function testGetUserVideosWithPagination()
    {
        Http::fake([
            'https://open-api.tiktok.com/video/list/' => Http::response([
                'data' => [
                    'videos' => [
                        ['video_id' => 'video1', 'title' => 'Video 1'],
                        ['video_id' => 'video2', 'title' => 'Video 2']
                    ],
                    'cursor' => 'next_cursor'
                ]
            ], 200),
        ]);

        $service = TikTokService::getInstance();
        $result = $service->getUserVideos(20);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('videos', $result['data']);
        $this->assertArrayHasKey('cursor', $result['data']);
    }

    public function testGetUserVideosWithMaxCount()
    {
        Http::fake([
            'https://open-api.tiktok.com/video/list/' => Http::response([
                'data' => [
                    'videos' => [
                        ['video_id' => 'video1', 'title' => 'Video 1']
                    ]
                ]
            ], 200),
        ]);

        $service = TikTokService::getInstance();
        $result = $service->getUserVideos(1);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']['videos']);
    }

    public function testVideoUploadWithMetadata()
    {
        Http::fake([
            'https://open-api.tiktok.com/share/video/upload/' => Http::response([
                'data' => ['video_id' => 'tiktok123']
            ], 200),
        ]);

        $service = TikTokService::getInstance();
        $result = $service->shareVideo('Test video with hashtags #test #tiktok', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('tiktok123', $result['data']['video_id']);
    }
}
