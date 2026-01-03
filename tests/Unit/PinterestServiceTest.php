<?php

namespace LimonHasan\SocialAutoPoster\Tests\Unit;

use LimonHasan\SocialAutoPoster\Tests\Unit\TestCase;
use LimonHasan\SocialAutoPoster\Services\PinterestService;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PinterestServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'autopost.pinterest_access_token' => 'test_pinterest_token',
            'autopost.pinterest_board_id' => 'test_board_id',
        ]);
    }

    public function testPinterestServiceSingleton()
    {
        $service1 = PinterestService::getInstance();
        $service2 = PinterestService::getInstance();
        
        $this->assertSame($service1, $service2);
    }

    public function testPinterestServiceWithMissingCredentials()
    {
        config(['autopost.pinterest_access_token' => null]);
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Pinterest API credentials are not fully configured');
        
        PinterestService::getInstance();
    }

    public function testShareImageSuccess()
    {
        Http::fake([
            'https://api.pinterest.com/v5/pins' => Http::response(['id' => 'pin123'], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->shareImage('Test Pinterest pin', 'https://example.com/image.jpg');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('pin123', $result['id']);
    }

    public function testShareVideoSuccess()
    {
        Http::fake([
            'https://api.pinterest.com/v5/pins' => Http::response(['id' => 'pin456'], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->shareVideo('Test Pinterest video', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('pin456', $result['id']);
    }

    public function testCreateBoardSuccess()
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response(['id' => 'board123'], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->createBoard('Test Board', 'Test board description');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('board123', $result['id']);
    }

    public function testGetUserInfoSuccess()
    {
        Http::fake([
            'https://api.pinterest.com/v5/user_account' => Http::response([
                'id' => 'user123',
                'username' => 'testuser',
                'account_type' => 'BUSINESS'
            ], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->getUserInfo();

        $this->assertArrayHasKey('username', $result);
        $this->assertEquals('testuser', $result['username']);
    }

    public function testGetBoardsSuccess()
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response([
                'items' => [
                    ['id' => 'board1', 'name' => 'Board 1'],
                    ['id' => 'board2', 'name' => 'Board 2']
                ]
            ], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->getBoards(25);

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);
    }

    public function testGetBoardPinsSuccess()
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards/test_board_id/pins' => Http::response([
                'items' => [
                    ['id' => 'pin1', 'title' => 'Pin 1'],
                    ['id' => 'pin2', 'title' => 'Pin 2']
                ]
            ], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->getBoardPins('test_board_id', 25);

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);
    }

    public function testGetPinAnalyticsSuccess()
    {
        Http::fake([
            'https://api.pinterest.com/v5/pins/pin123/analytics' => Http::response([
                'daily_metrics' => [
                    ['date' => '2024-01-01', 'impression' => 1000, 'clickthrough' => 50]
                ]
            ], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->getPinAnalytics('pin123');

        $this->assertArrayHasKey('daily_metrics', $result);
        $this->assertCount(1, $result['daily_metrics']);
    }

    public function testShareImageWithEmptyCaption()
    {
        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Caption cannot be empty');
        
        $service->shareImage('', 'https://example.com/image.jpg');
    }

    public function testShareImageWithCaptionTooLong()
    {
        $service = PinterestService::getInstance();
        $longCaption = str_repeat('a', 2201); // Over 2200 character limit
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content exceeds maximum length of 2200 characters');
        
        $service->shareImage($longCaption, 'https://example.com/image.jpg');
    }

    public function testShareImageWithInvalidUrl()
    {
        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Invalid URL provided');
        
        $service->shareImage('Test pin', 'invalid-url');
    }

    public function testCreateBoardWithEmptyName()
    {
        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Board name cannot be empty');
        
        $service->createBoard('', 'Description');
    }

    public function testCreateBoardWithNameTooLong()
    {
        $service = PinterestService::getInstance();
        $longName = str_repeat('a', 181); // Over 180 character limit
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content exceeds maximum length of 180 characters');
        
        $service->createBoard($longName, 'Description');
    }

    public function testShareImageWithApiError()
    {
        Http::fake([
            'https://api.pinterest.com/v5/pins' => Http::response([
                'message' => 'Invalid access token'
            ], 401),
        ]);

        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share image to Pinterest');
        
        $service->shareImage('Test Pinterest pin', 'https://example.com/image.jpg');
    }

    public function testShareVideoWithApiError()
    {
        Http::fake([
            'https://api.pinterest.com/v5/pins' => Http::response([
                'message' => 'Invalid video URL'
            ], 400),
        ]);

        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share video to Pinterest');
        
        $service->shareVideo('Test Pinterest video', 'https://example.com/video.mp4');
    }

    public function testCreateBoardWithApiError()
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response([
                'message' => 'Invalid board data'
            ], 400),
        ]);

        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to create Pinterest board');
        
        $service->createBoard('Test Board', 'Description');
    }

    public function testGetUserInfoWithApiError()
    {
        Http::fake([
            'https://api.pinterest.com/v5/user_account' => Http::response([
                'message' => 'Invalid user request'
            ], 400),
        ]);

        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get Pinterest user info');
        
        $service->getUserInfo();
    }

    public function testGetBoardsWithApiError()
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response([
                'message' => 'Invalid boards request'
            ], 400),
        ]);

        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get Pinterest boards');
        
        $service->getBoards(25);
    }

    public function testGetBoardPinsWithApiError()
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards/test_board_id/pins' => Http::response([
                'message' => 'Invalid board ID'
            ], 400),
        ]);

        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get Pinterest board pins');
        
        $service->getBoardPins('test_board_id', 25);
    }

    public function testGetPinAnalyticsWithApiError()
    {
        Http::fake([
            'https://api.pinterest.com/v5/pins/pin123/analytics' => Http::response([
                'message' => 'Invalid pin ID'
            ], 400),
        ]);

        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get Pinterest pin analytics');
        
        $service->getPinAnalytics('pin123');
    }

    public function testLoggingOnSuccess()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Pinterest image post shared successfully', \Mockery::type('array'));

        Http::fake([
            'https://api.pinterest.com/v5/pins' => Http::response(['id' => 'pin123'], 200),
        ]);

        $service = PinterestService::getInstance();
        $service->shareImage('Test Pinterest pin', 'https://example.com/image.jpg');
    }

    public function testLoggingOnError()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to share image to Pinterest', \Mockery::type('array'));

        Http::fake([
            'https://api.pinterest.com/v5/pins' => Http::response([
                'message' => 'API Error'
            ], 400),
        ]);

        $service = PinterestService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $service->shareImage('Test Pinterest pin', 'https://example.com/image.jpg');
    }

    public function testRetryLogic()
    {
        Http::fake([
            'https://api.pinterest.com/v5/pins' => Http::sequence()
                ->push(['message' => 'Rate limited'], 429)
                ->push(['message' => 'Rate limited'], 429)
                ->push(['id' => 'pin123'], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->shareImage('Test Pinterest pin', 'https://example.com/image.jpg');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('pin123', $result['id']);
    }

    public function testTimeoutConfiguration()
    {
        config(['autopost.timeout' => 60]);

        Http::fake([
            'https://api.pinterest.com/v5/pins' => Http::response(['id' => 'pin123'], 200),
        ]);

        $service = PinterestService::getInstance();
        $service->shareImage('Test Pinterest pin', 'https://example.com/image.jpg');

        Http::assertSent(function ($request) {
            return $request->timeout() === 60;
        });
    }

    public function testCreateBoardWithPrivacy()
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response(['id' => 'board123'], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->createBoard('Test Board', 'Description', 'PUBLIC');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('board123', $result['id']);
    }

    public function testGetBoardsWithPagination()
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response([
                'items' => [
                    ['id' => 'board1', 'name' => 'Board 1']
                ],
                'bookmark' => 'next_bookmark'
            ], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->getBoards(25);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('bookmark', $result);
    }

    public function testGetBoardPinsWithMaxCount()
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards/test_board_id/pins' => Http::response([
                'items' => [
                    ['id' => 'pin1', 'title' => 'Pin 1']
                ]
            ], 200),
        ]);

        $service = PinterestService::getInstance();
        $result = $service->getBoardPins('test_board_id', 1);

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(1, $result['items']);
    }
}
