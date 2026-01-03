<?php

namespace LimonHasan\SocialAutoPoster\Tests\Unit;

use LimonHasan\SocialAutoPoster\Tests\Unit\TestCase;
use LimonHasan\SocialAutoPoster\Services\InstagramService;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'autopost.instagram_access_token' => 'test_instagram_token',
            'autopost.instagram_account_id' => 'test_account_id',
        ]);
    }

    public function testInstagramServiceSingleton()
    {
        $service1 = InstagramService::getInstance();
        $service2 = InstagramService::getInstance();
        
        $this->assertSame($service1, $service2);
    }

    public function testInstagramServiceWithMissingCredentials()
    {
        config(['autopost.instagram_access_token' => null]);
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Instagram API credentials are not fully configured');
        
        InstagramService::getInstance();
    }

    public function testShareImageSuccess()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response(['id' => 'media123'], 200),
            'https://graph.facebook.com/v20.0/test_account_id/media_publish' => Http::response(['id' => 'post123'], 200),
        ]);

        $service = InstagramService::getInstance();
        $result = $service->shareImage('Test image post', 'https://example.com/image.jpg');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('post123', $result['id']);
    }

    public function testShareVideoSuccess()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response(['id' => 'media123'], 200),
            'https://graph.facebook.com/v20.0/test_account_id/media_publish' => Http::response(['id' => 'post123'], 200),
        ]);

        $service = InstagramService::getInstance();
        $result = $service->shareVideo('Test video post', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('post123', $result['id']);
    }

    public function testShareCarouselSuccess()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response(['id' => 'media123'], 200),
            'https://graph.facebook.com/v20.0/test_account_id/media_publish' => Http::response(['id' => 'post123'], 200),
        ]);

        $service = InstagramService::getInstance();
        $images = [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg',
            'https://example.com/image3.jpg'
        ];
        $result = $service->shareCarousel('Test carousel post', $images);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('post123', $result['id']);
    }

    public function testShareStorySuccess()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response(['id' => 'media123'], 200),
            'https://graph.facebook.com/v20.0/test_account_id/media_publish' => Http::response(['id' => 'story123'], 200),
        ]);

        $service = InstagramService::getInstance();
        $result = $service->shareStory('Test story', 'https://example.com/story.jpg');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('story123', $result['id']);
    }

    public function testGetAccountInfoSuccess()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id' => Http::response([
                'id' => 'test_account_id',
                'username' => 'testuser',
                'account_type' => 'BUSINESS'
            ], 200),
        ]);

        $service = InstagramService::getInstance();
        $result = $service->getAccountInfo();

        $this->assertArrayHasKey('username', $result);
        $this->assertEquals('testuser', $result['username']);
    }

    public function testGetRecentMediaSuccess()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response([
                'data' => [
                    ['id' => 'media1', 'media_type' => 'IMAGE'],
                    ['id' => 'media2', 'media_type' => 'VIDEO']
                ]
            ], 200),
        ]);

        $service = InstagramService::getInstance();
        $result = $service->getRecentMedia(25);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
    }

    public function testShareImageWithEmptyCaption()
    {
        $service = InstagramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Caption cannot be empty');
        
        $service->shareImage('', 'https://example.com/image.jpg');
    }

    public function testShareImageWithCaptionTooLong()
    {
        $service = InstagramService::getInstance();
        $longCaption = str_repeat('a', 2201); // Over 2200 character limit
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content exceeds maximum length of 2200 characters');
        
        $service->shareImage($longCaption, 'https://example.com/image.jpg');
    }

    public function testShareImageWithInvalidUrl()
    {
        $service = InstagramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Invalid URL provided');
        
        $service->shareImage('Test image', 'invalid-url');
    }

    public function testShareCarouselWithEmptyImages()
    {
        $service = InstagramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('At least 2 images are required for carousel');
        
        $service->shareCarousel('Test carousel', []);
    }

    public function testShareCarouselWithTooManyImages()
    {
        $service = InstagramService::getInstance();
        $images = array_fill(0, 11, 'https://example.com/image.jpg'); // Over 10 image limit
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Maximum 10 images allowed for carousel');
        
        $service->shareCarousel('Test carousel', $images);
    }

    public function testShareImageWithApiError()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response([
                'error' => ['message' => 'Invalid access token']
            ], 400),
        ]);

        $service = InstagramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share image to Instagram');
        
        $service->shareImage('Test image post', 'https://example.com/image.jpg');
    }

    public function testShareVideoWithApiError()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response([
                'error' => ['message' => 'Invalid video URL']
            ], 400),
        ]);

        $service = InstagramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share video to Instagram');
        
        $service->shareVideo('Test video post', 'https://example.com/video.mp4');
    }

    public function testShareCarouselWithApiError()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response([
                'error' => ['message' => 'Invalid carousel data']
            ], 400),
        ]);

        $service = InstagramService::getInstance();
        $images = ['https://example.com/image1.jpg', 'https://example.com/image2.jpg'];
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share carousel to Instagram');
        
        $service->shareCarousel('Test carousel', $images);
    }

    public function testShareStoryWithApiError()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response([
                'error' => ['message' => 'Invalid story data']
            ], 400),
        ]);

        $service = InstagramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share story to Instagram');
        
        $service->shareStory('Test story', 'https://example.com/story.jpg');
    }

    public function testGetAccountInfoWithApiError()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id' => Http::response([
                'error' => ['message' => 'Invalid account ID']
            ], 400),
        ]);

        $service = InstagramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get Instagram account info');
        
        $service->getAccountInfo();
    }

    public function testGetRecentMediaWithApiError()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response([
                'error' => ['message' => 'Invalid request']
            ], 400),
        ]);

        $service = InstagramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get Instagram recent media');
        
        $service->getRecentMedia(25);
    }

    public function testLoggingOnSuccess()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Instagram image post shared successfully', \Mockery::type('array'));

        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response(['id' => 'media123'], 200),
            'https://graph.facebook.com/v20.0/test_account_id/media_publish' => Http::response(['id' => 'post123'], 200),
        ]);

        $service = InstagramService::getInstance();
        $service->shareImage('Test image post', 'https://example.com/image.jpg');
    }

    public function testLoggingOnError()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to share image to Instagram', \Mockery::type('array'));

        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response([
                'error' => ['message' => 'API Error']
            ], 400),
        ]);

        $service = InstagramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $service->shareImage('Test image post', 'https://example.com/image.jpg');
    }

    public function testRetryLogic()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::sequence()
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['id' => 'media123'], 200),
            'https://graph.facebook.com/v20.0/test_account_id/media_publish' => Http::response(['id' => 'post123'], 200),
        ]);

        $service = InstagramService::getInstance();
        $result = $service->shareImage('Test image post', 'https://example.com/image.jpg');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('post123', $result['id']);
    }

    public function testTimeoutConfiguration()
    {
        config(['autopost.timeout' => 60]);

        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response(['id' => 'media123'], 200),
            'https://graph.facebook.com/v20.0/test_account_id/media_publish' => Http::response(['id' => 'post123'], 200),
        ]);

        $service = InstagramService::getInstance();
        $service->shareImage('Test image post', 'https://example.com/image.jpg');

        Http::assertSent(function ($request) {
            return $request->timeout() === 60;
        });
    }

    public function testMediaCreationWithImageType()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response(['id' => 'media123'], 200),
            'https://graph.facebook.com/v20.0/test_account_id/media_publish' => Http::response(['id' => 'post123'], 200),
        ]);

        $service = InstagramService::getInstance();
        $result = $service->shareImage('Test image post', 'https://example.com/image.jpg');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('post123', $result['id']);
    }

    public function testMediaCreationWithVideoType()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/test_account_id/media' => Http::response(['id' => 'media123'], 200),
            'https://graph.facebook.com/v20.0/test_account_id/media_publish' => Http::response(['id' => 'post123'], 200),
        ]);

        $service = InstagramService::getInstance();
        $result = $service->shareVideo('Test video post', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('post123', $result['id']);
    }
}
