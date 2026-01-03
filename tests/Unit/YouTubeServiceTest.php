<?php

namespace LimonHasan\SocialAutoPoster\Tests\Unit;

use LimonHasan\SocialAutoPoster\Tests\Unit\TestCase;
use LimonHasan\SocialAutoPoster\Services\YouTubeService;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'autopost.youtube_api_key' => 'test_youtube_key',
            'autopost.youtube_access_token' => 'test_youtube_token',
            'autopost.youtube_channel_id' => 'test_channel_id',
        ]);
    }

    public function testYouTubeServiceSingleton()
    {
        $service1 = YouTubeService::getInstance();
        $service2 = YouTubeService::getInstance();
        
        $this->assertSame($service1, $service2);
    }

    public function testYouTubeServiceWithMissingCredentials()
    {
        config(['autopost.youtube_api_key' => null]);
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('YouTube API credentials are not fully configured');
        
        YouTubeService::getInstance();
    }

    public function testShareVideoSuccess()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::response(['id' => 'youtube123'], 200),
        ]);

        $service = YouTubeService::getInstance();
        $result = $service->shareVideo('Test YouTube video', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('youtube123', $result['id']);
    }

    public function testCreateCommunityPostSuccess()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/activities' => Http::response(['id' => 'community123'], 200),
        ]);

        $service = YouTubeService::getInstance();
        $result = $service->createCommunityPost('Test community post', 'https://example.com');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('community123', $result['id']);
    }

    public function testGetChannelInfoSuccess()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels' => Http::response([
                'items' => [
                    [
                        'id' => 'test_channel_id',
                        'snippet' => [
                            'title' => 'Test Channel',
                            'description' => 'Test channel description'
                        ]
                    ]
                ]
            ], 200),
        ]);

        $service = YouTubeService::getInstance();
        $result = $service->getChannelInfo();

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(1, $result['items']);
        $this->assertEquals('Test Channel', $result['items'][0]['snippet']['title']);
    }

    public function testGetChannelVideosSuccess()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/search' => Http::response([
                'items' => [
                    ['id' => ['videoId' => 'video1'], 'snippet' => ['title' => 'Video 1']],
                    ['id' => ['videoId' => 'video2'], 'snippet' => ['title' => 'Video 2']]
                ]
            ], 200),
        ]);

        $service = YouTubeService::getInstance();
        $result = $service->getChannelVideos(25);

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);
    }

    public function testGetVideoAnalyticsSuccess()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::response([
                'items' => [
                    [
                        'id' => 'video123',
                        'statistics' => [
                            'viewCount' => '1000',
                            'likeCount' => '100',
                            'commentCount' => '50'
                        ]
                    ]
                ]
            ], 200),
        ]);

        $service = YouTubeService::getInstance();
        $result = $service->getVideoAnalytics('video123');

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(1, $result['items']);
        $this->assertEquals('1000', $result['items'][0]['statistics']['viewCount']);
    }

    public function testShareVideoWithEmptyTitle()
    {
        $service = YouTubeService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Title cannot be empty');
        
        $service->shareVideo('', 'https://example.com/video.mp4');
    }

    public function testShareVideoWithTitleTooLong()
    {
        $service = YouTubeService::getInstance();
        $longTitle = str_repeat('a', 101); // Over 100 character limit
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content exceeds maximum length of 100 characters');
        
        $service->shareVideo($longTitle, 'https://example.com/video.mp4');
    }

    public function testShareVideoWithInvalidUrl()
    {
        $service = YouTubeService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Invalid URL provided');
        
        $service->shareVideo('Test video', 'invalid-url');
    }

    public function testCreateCommunityPostWithEmptyContent()
    {
        $service = YouTubeService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Content cannot be empty');
        
        $service->createCommunityPost('', 'https://example.com');
    }

    public function testCreateCommunityPostWithContentTooLong()
    {
        $service = YouTubeService::getInstance();
        $longContent = str_repeat('a', 2001); // Over 2000 character limit
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content exceeds maximum length of 2000 characters');
        
        $service->createCommunityPost($longContent, 'https://example.com');
    }

    public function testShareVideoWithApiError()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::response([
                'error' => ['message' => 'Invalid access token']
            ], 401),
        ]);

        $service = YouTubeService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share video to YouTube');
        
        $service->shareVideo('Test YouTube video', 'https://example.com/video.mp4');
    }

    public function testCreateCommunityPostWithApiError()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/activities' => Http::response([
                'error' => ['message' => 'Invalid channel ID']
            ], 400),
        ]);

        $service = YouTubeService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to create YouTube community post');
        
        $service->createCommunityPost('Test community post', 'https://example.com');
    }

    public function testGetChannelInfoWithApiError()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels' => Http::response([
                'error' => ['message' => 'Invalid channel request']
            ], 400),
        ]);

        $service = YouTubeService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get YouTube channel info');
        
        $service->getChannelInfo();
    }

    public function testGetChannelVideosWithApiError()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/search' => Http::response([
                'error' => ['message' => 'Invalid search request']
            ], 400),
        ]);

        $service = YouTubeService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get YouTube channel videos');
        
        $service->getChannelVideos(25);
    }

    public function testGetVideoAnalyticsWithApiError()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::response([
                'error' => ['message' => 'Invalid video ID']
            ], 400),
        ]);

        $service = YouTubeService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get YouTube video analytics');
        
        $service->getVideoAnalytics('invalid_video_id');
    }

    public function testLoggingOnSuccess()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('YouTube video post shared successfully', \Mockery::type('array'));

        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::response(['id' => 'youtube123'], 200),
        ]);

        $service = YouTubeService::getInstance();
        $service->shareVideo('Test YouTube video', 'https://example.com/video.mp4');
    }

    public function testLoggingOnError()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to share video to YouTube', \Mockery::type('array'));

        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::response([
                'error' => ['message' => 'API Error']
            ], 400),
        ]);

        $service = YouTubeService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $service->shareVideo('Test YouTube video', 'https://example.com/video.mp4');
    }

    public function testRetryLogic()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::sequence()
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['id' => 'youtube123'], 200),
        ]);

        $service = YouTubeService::getInstance();
        $result = $service->shareVideo('Test YouTube video', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('youtube123', $result['id']);
    }

    public function testTimeoutConfiguration()
    {
        config(['autopost.timeout' => 60]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::response(['id' => 'youtube123'], 200),
        ]);

        $service = YouTubeService::getInstance();
        $service->shareVideo('Test YouTube video', 'https://example.com/video.mp4');

        Http::assertSent(function ($request) {
            return $request->timeout() === 60;
        });
    }

    public function testVideoUploadWithMetadata()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::response(['id' => 'youtube123'], 200),
        ]);

        $service = YouTubeService::getInstance();
        $result = $service->shareVideo('Test video with description', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('youtube123', $result['id']);
    }

    public function testGetChannelVideosWithMaxResults()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/search' => Http::response([
                'items' => [
                    ['id' => ['videoId' => 'video1'], 'snippet' => ['title' => 'Video 1']]
                ]
            ], 200),
        ]);

        $service = YouTubeService::getInstance();
        $result = $service->getChannelVideos(1);

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(1, $result['items']);
    }

    public function testGetVideoAnalyticsWithMultipleVideos()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos' => Http::response([
                'items' => [
                    [
                        'id' => 'video1',
                        'statistics' => ['viewCount' => '1000']
                    ],
                    [
                        'id' => 'video2',
                        'statistics' => ['viewCount' => '2000']
                    ]
                ]
            ], 200),
        ]);

        $service = YouTubeService::getInstance();
        $result = $service->getVideoAnalytics('video1,video2');

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);
    }
}
