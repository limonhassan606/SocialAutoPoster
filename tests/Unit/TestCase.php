<?php

namespace LimonHasan\SocialAutoPoster\Tests\Unit;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        config([
            'autopost.facebook_access_token' => 'test_facebook_token',
            'autopost.facebook_page_id' => 'test_page_id',
            'autopost.twitter_bearer_token' => 'test_twitter_token',
            'autopost.linkedin_access_token' => 'test_linkedin_token',
            'autopost.instagram_access_token' => 'test_instagram_token',
            'autopost.tiktok_access_token' => 'test_tiktok_token',
            'autopost.youtube_api_key' => 'test_youtube_key',
            'autopost.pinterest_access_token' => 'test_pinterest_token',
            'autopost.telegram_bot_token' => 'test_telegram_token',
            'autopost.telegram_chat_id' => 'test_chat_id',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            \LimonHasan\SocialAutoPoster\SocialShareServiceProvider::class,
        ];
    }
}
