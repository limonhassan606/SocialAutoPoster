<?php

namespace LimonHasan\SocialAutoPoster\Tests\Unit;

use LimonHasan\SocialAutoPoster\Tests\Unit\TestCase;
use LimonHasan\SocialAutoPoster\Services\TelegramService;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'autopost.telegram_bot_token' => 'test_telegram_token',
            'autopost.telegram_chat_id' => 'test_chat_id',
        ]);
    }

    public function testTelegramServiceSingleton()
    {
        $service1 = TelegramService::getInstance();
        $service2 = TelegramService::getInstance();
        
        $this->assertSame($service1, $service2);
    }

    public function testTelegramServiceWithMissingCredentials()
    {
        config(['autopost.telegram_bot_token' => null]);
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Telegram API credentials are not fully configured');
        
        TelegramService::getInstance();
    }

    public function testShareSuccess()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 123]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->share('Test message', 'https://example.com');

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
        $this->assertEquals(123, $result['result']['message_id']);
    }

    public function testShareImageSuccess()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 456]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->shareImage('Test image', 'https://example.com/image.jpg');

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
        $this->assertEquals(456, $result['result']['message_id']);
    }

    public function testShareVideoSuccess()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 789]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->shareVideo('Test video', 'https://example.com/video.mp4');

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
        $this->assertEquals(789, $result['result']['message_id']);
    }

    public function testShareDocumentSuccess()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 101]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->shareDocument('Test document', 'https://example.com/document.pdf');

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
        $this->assertEquals(101, $result['result']['message_id']);
    }

    public function testGetUpdatesSuccess()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => [
                    ['update_id' => 1, 'message' => ['text' => 'Hello']],
                    ['update_id' => 2, 'message' => ['text' => 'World']]
                ]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->getUpdates();

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['result']);
    }

    public function testShareWithEmptyMessage()
    {
        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Message cannot be empty');
        
        $service->share('', 'https://example.com');
    }

    public function testShareWithMessageTooLong()
    {
        $service = TelegramService::getInstance();
        $longMessage = str_repeat('a', 4097); // Over 4096 character limit
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Text content exceeds maximum length of 4096 characters');
        
        $service->share($longMessage, 'https://example.com');
    }

    public function testShareWithInvalidUrl()
    {
        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Invalid URL provided');
        
        $service->share('Test message', 'invalid-url');
    }

    public function testShareImageWithEmptyCaption()
    {
        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Caption cannot be empty');
        
        $service->shareImage('', 'https://example.com/image.jpg');
    }

    public function testShareVideoWithEmptyCaption()
    {
        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Caption cannot be empty');
        
        $service->shareVideo('', 'https://example.com/video.mp4');
    }

    public function testShareDocumentWithEmptyCaption()
    {
        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Caption cannot be empty');
        
        $service->shareDocument('', 'https://example.com/document.pdf');
    }

    public function testShareWithApiError()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => false,
                'description' => 'Invalid bot token'
            ], 401),
        ]);

        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share to Telegram');
        
        $service->share('Test message', 'https://example.com');
    }

    public function testShareImageWithApiError()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => false,
                'description' => 'Invalid image URL'
            ], 400),
        ]);

        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share image to Telegram');
        
        $service->shareImage('Test image', 'https://example.com/image.jpg');
    }

    public function testShareVideoWithApiError()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => false,
                'description' => 'Invalid video URL'
            ], 400),
        ]);

        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share video to Telegram');
        
        $service->shareVideo('Test video', 'https://example.com/video.mp4');
    }

    public function testShareDocumentWithApiError()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => false,
                'description' => 'Invalid document URL'
            ], 400),
        ]);

        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to share document to Telegram');
        
        $service->shareDocument('Test document', 'https://example.com/document.pdf');
    }

    public function testGetUpdatesWithApiError()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => false,
                'description' => 'Invalid bot token'
            ], 401),
        ]);

        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $this->expectExceptionMessage('Failed to get Telegram updates');
        
        $service->getUpdates();
    }

    public function testLoggingOnSuccess()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Telegram message sent successfully', \Mockery::type('array'));

        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 123]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $service->share('Test message', 'https://example.com');
    }

    public function testLoggingOnError()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to share to Telegram', \Mockery::type('array'));

        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => false,
                'description' => 'API Error'
            ], 400),
        ]);

        $service = TelegramService::getInstance();
        
        $this->expectException(SocialMediaException::class);
        $service->share('Test message', 'https://example.com');
    }

    public function testRetryLogic()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::sequence()
                ->push(['ok' => false, 'description' => 'Rate limited'], 429)
                ->push(['ok' => false, 'description' => 'Rate limited'], 429)
                ->push(['ok' => true, 'result' => ['message_id' => 123]], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->share('Test message', 'https://example.com');

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
        $this->assertEquals(123, $result['result']['message_id']);
    }

    public function testTimeoutConfiguration()
    {
        config(['autopost.timeout' => 60]);

        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 123]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $service->share('Test message', 'https://example.com');

        Http::assertSent(function ($request) {
            return $request->timeout() === 60;
        });
    }

    public function testShareWithMarkdown()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 123]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->share('Test *bold* message', 'https://example.com');

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
    }

    public function testShareWithHtml()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 123]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->share('Test <b>bold</b> message', 'https://example.com');

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
    }

    public function testGetUpdatesWithOffset()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => []
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->getUpdates(123);

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
    }

    public function testShareWithCustomChatId()
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 123]
            ], 200),
        ]);

        $service = TelegramService::getInstance();
        $result = $service->share('Test message', 'https://example.com', 'custom_chat_id');

        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']);
    }
}
