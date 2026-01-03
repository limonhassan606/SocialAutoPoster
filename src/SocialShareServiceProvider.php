<?php

namespace LimonHasan\SocialAutoPoster;

use LimonHasan\SocialAutoPoster\Services\FacebookService;
use LimonHasan\SocialAutoPoster\Services\TelegramService;
use LimonHasan\SocialAutoPoster\Services\TwitterService;
use LimonHasan\SocialAutoPoster\Services\LinkedInService;
use LimonHasan\SocialAutoPoster\Services\InstagramService;
use LimonHasan\SocialAutoPoster\Services\TikTokService;
use LimonHasan\SocialAutoPoster\Services\YouTubeService;
use LimonHasan\SocialAutoPoster\Services\PinterestService;
use LimonHasan\SocialAutoPoster\Services\SocialMediaManager;
use Illuminate\Support\ServiceProvider;

class SocialShareServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/autopost.php' => config_path('autopost.php'),
        ], 'autopost');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'autopost-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\ProcessScheduledPosts::class,
            ]);
        }
    }

    public function register()
    {
        // Register all social media services as singletons
        $this->app->singleton(FacebookService::class, function ($app) {
            return FacebookService::getInstance();
        });

        $this->app->singleton(TelegramService::class, function ($app) {
            return TelegramService::getInstance();
        });

        $this->app->singleton(TwitterService::class, function ($app) {
            return TwitterService::getInstance();
        });

        $this->app->singleton(LinkedInService::class, function ($app) {
            return LinkedInService::getInstance();
        });

        $this->app->singleton(InstagramService::class, function ($app) {
            return InstagramService::getInstance();
        });

        $this->app->singleton(TikTokService::class, function ($app) {
            return TikTokService::getInstance();
        });

        $this->app->singleton(YouTubeService::class, function ($app) {
            return YouTubeService::getInstance();
        });

        $this->app->singleton(PinterestService::class, function ($app) {
            return PinterestService::getInstance();
        });

        $this->app->singleton(SocialMediaManager::class, function ($app) {
            return new SocialMediaManager();
        });

        // Register service aliases
        $this->app->alias(FacebookService::class, 'facebook');
        $this->app->alias(TelegramService::class, 'telegram');
        $this->app->alias(TwitterService::class, 'twitter');
        $this->app->alias(LinkedInService::class, 'linkedin');
        $this->app->alias(InstagramService::class, 'instagram');
        $this->app->alias(TikTokService::class, 'tiktok');
        $this->app->alias(YouTubeService::class, 'youtube');
        $this->app->alias(PinterestService::class, 'pinterest');
        $this->app->alias(SocialMediaManager::class, 'socialmedia');

        // Register config file
        $this->mergeConfigFrom(__DIR__ . '/config/autopost.php', 'autopost');
    }

}