# Laravel Social Auto Poster

[![Latest Version on Packagist](https://img.shields.io/packagist/v/limonhasan/social-autoposter.svg?style=flat-square)](https://packagist.org/packages/limonhasan/social-autoposter)
[![Total Downloads](https://img.shields.io/packagist/dt/limonhasan/social-autoposter.svg?style=flat-square)](https://packagist.org/packages/limonhasan/social-autoposter)
[![License](https://img.shields.io/packagist/l/limonhasan/social-autoposter.svg?style=flat-square)](https://packagist.org/packages/limonhasan/social-autoposter)
[![PHP Version](https://img.shields.io/packagist/php-v/limonhasan/social-autoposter.svg?style=flat-square)](https://packagist.org/packages/limonhasan/social-autoposter)

A comprehensive Laravel package for automatic social media posting across **8 major platforms**: Facebook, Twitter/X, LinkedIn, Instagram, TikTok, YouTube, Pinterest, and Telegram. Post to one platform or all platforms simultaneously with a unified API.

## 🌟 Features

- **8 Social Media Platforms**: Facebook, Twitter/X, LinkedIn, Instagram, TikTok, YouTube, Pinterest, Telegram
- **Unified API**: Post to multiple platforms with a single command
- **Individual Platform Access**: Direct access to each platform's specific features
- **Comprehensive Content Types**: Text, Images, Videos, Documents, Stories, Carousels
- **Advanced Analytics**: Facebook Page Insights, Twitter Analytics, LinkedIn Metrics
- **Production Ready**: Error handling, retry logic, rate limiting, logging
- **Laravel Native**: Perfect integration with Laravel ecosystem
- **Extensible**: Easy to add new platforms and features

## 📋 Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Usage](#usage)
  - [Unified API](#unified-api)
  - [Individual Platforms](#individual-platforms)
  - [Platform-Specific Features](#platform-specific-features)
- [Advanced Features](#advanced-features)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Examples](#examples)
- [API Reference](#api-reference)
- [Contributing](#contributing)
- [License](#license)

## 🚀 Installation

### Requirements

- PHP 8.1 or higher
- Laravel 12.0 or higher
- Composer

### Install via Composer

```bash
composer require limonhasan/social-autoposter
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="LimonHasan\SocialAutoPoster\SocialShareServiceProvider" --tag=autopost
```

## ⚙️ Configuration

### Environment Variables

Add the following environment variables to your `.env` file:

```env
# Facebook
FACEBOOK_ACCESS_TOKEN=your_facebook_access_token
FACEBOOK_PAGE_ID=your_facebook_page_id

# Twitter/X
TWITTER_BEARER_TOKEN=your_twitter_bearer_token
TWITTER_API_KEY=your_twitter_api_key
TWITTER_API_SECRET=your_twitter_api_secret
TWITTER_ACCESS_TOKEN=your_twitter_access_token
TWITTER_ACCESS_TOKEN_SECRET=your_twitter_access_token_secret

# LinkedIn
LINKEDIN_ACCESS_TOKEN=your_linkedin_access_token
LINKEDIN_PERSON_URN=your_linkedin_person_urn
LINKEDIN_ORGANIZATION_URN=your_linkedin_organization_urn

# Instagram
INSTAGRAM_ACCESS_TOKEN=your_instagram_access_token
INSTAGRAM_ACCOUNT_ID=your_instagram_account_id

# TikTok
TIKTOK_ACCESS_TOKEN=your_tiktok_access_token
TIKTOK_CLIENT_KEY=your_tiktok_client_key
TIKTOK_CLIENT_SECRET=your_tiktok_client_secret

# YouTube
YOUTUBE_API_KEY=your_youtube_api_key
YOUTUBE_ACCESS_TOKEN=your_youtube_access_token
YOUTUBE_CHANNEL_ID=your_youtube_channel_id

# Pinterest
PINTEREST_ACCESS_TOKEN=your_pinterest_access_token
PINTEREST_BOARD_ID=your_pinterest_board_id

# Telegram
TELEGRAM_BOT_TOKEN=your_telegram_bot_token
TELEGRAM_CHAT_ID=your_telegram_chat_id
```

### Configuration File

The published `config/autopost.php` file contains all configuration options:

```php
return [
    // Platform configurations
    'facebook_access_token' => env('FACEBOOK_ACCESS_TOKEN'),
    'facebook_page_id' => env('FACEBOOK_PAGE_ID'),
    'facebook_api_version' => env('FACEBOOK_API_VERSION', 'v20.0'),
    
    // ... other platform configurations
    
    // General settings
    'default_platforms' => ['facebook', 'twitter', 'linkedin'],
    'enable_logging' => env('SOCIAL_MEDIA_LOGGING', true),
    'timeout' => env('SOCIAL_MEDIA_TIMEOUT', 30),
    'retry_attempts' => env('SOCIAL_MEDIA_RETRY_ATTEMPTS', 3),
];
```

## 🎯 Quick Start

### Basic Usage

```php
use LimonHasan\SocialAutoPoster\Facades\SocialMedia;

// Post to multiple platforms
$result = SocialMedia::share(['facebook', 'twitter', 'linkedin'], 'Hello World!', 'https://example.com');

// Post to all platforms
$result = SocialMedia::shareToAll('Hello World!', 'https://example.com');

// Share images
$result = SocialMedia::shareImage(['instagram', 'pinterest'], 'Check this out!', 'https://example.com/image.jpg');

// Share videos
$result = SocialMedia::shareVideo(['youtube', 'tiktok'], 'Watch this!', 'https://example.com/video.mp4');
```

### Individual Platform Access

```php
use LimonHasan\SocialAutoPoster\Facades\FaceBook;
use LimonHasan\SocialAutoPoster\Facades\Twitter;
use LimonHasan\SocialAutoPoster\Facades\LinkedIn;

// Facebook
FaceBook::share('Hello Facebook!', 'https://example.com');
FaceBook::shareImage('Check this image!', 'https://example.com/image.jpg');

// Twitter
Twitter::share('Hello Twitter!', 'https://example.com');
Twitter::shareImage('Check this image!', 'https://example.com/image.jpg');

// LinkedIn
LinkedIn::share('Hello LinkedIn!', 'https://example.com');
LinkedIn::shareToCompanyPage('Company update!', 'https://example.com');
```

## 📖 Usage

### Unified API

The `SocialMedia` facade provides a unified interface for posting to multiple platforms:

#### Share to Multiple Platforms

```php
use SocialMedia;

// Post to specific platforms
$result = SocialMedia::share(['facebook', 'twitter', 'linkedin'], 'Content', 'https://example.com');

// Share images to visual platforms
$result = SocialMedia::shareImage(['instagram', 'pinterest'], 'Caption', 'https://example.com/image.jpg');

// Share videos to video platforms
$result = SocialMedia::shareVideo(['youtube', 'tiktok'], 'Caption', 'https://example.com/video.mp4');
```

#### Share to All Platforms

```php
// Post to all available platforms
$result = SocialMedia::shareToAll('Content', 'https://example.com');

// Share images to all platforms
$result = SocialMedia::shareImageToAll('Caption', 'https://example.com/image.jpg');

// Share videos to all platforms
$result = SocialMedia::shareVideoToAll('Caption', 'https://example.com/video.mp4');
```

#### Platform-Specific Access

```php
// Access individual platforms
$facebookService = SocialMedia::facebook();
$twitterService = SocialMedia::twitter();
$linkedinService = SocialMedia::linkedin();

// Use platform-specific methods
$result = SocialMedia::linkedin()->shareToCompanyPage('Content', 'https://example.com');
$result = SocialMedia::instagram()->shareCarousel('Caption', ['img1.jpg', 'img2.jpg']);
```

### Individual Platforms

Each platform has its own facade with specific methods:

#### Facebook

```php
use FaceBook;

// Basic posting
FaceBook::share('Content', 'https://example.com');
FaceBook::shareImage('Caption', 'https://example.com/image.jpg');
FaceBook::shareVideo('Caption', 'https://example.com/video.mp4');

// Analytics
$insights = FaceBook::getPageInsights(['page_impressions', 'page_engaged_users']);
$pageInfo = FaceBook::getPageInfo();
```

#### Twitter/X

```php
use Twitter;

// Posting
Twitter::share('Content', 'https://example.com');
Twitter::shareImage('Caption', 'https://example.com/image.jpg');
Twitter::shareVideo('Caption', 'https://example.com/video.mp4');

// Analytics
$timeline = Twitter::getTimeline(10);
$userInfo = Twitter::getUserInfo();
```

#### LinkedIn

```php
use LinkedIn;

// Personal posts
LinkedIn::share('Content', 'https://example.com');
LinkedIn::shareImage('Caption', 'https://example.com/image.jpg');
LinkedIn::shareVideo('Caption', 'https://example.com/video.mp4');

// Company page posts
LinkedIn::shareToCompanyPage('Content', 'https://example.com');

// User info
$userInfo = LinkedIn::getUserInfo();
```

#### Instagram

```php
use Instagram;

// Posts
Instagram::shareImage('Caption', 'https://example.com/image.jpg');
Instagram::shareVideo('Caption', 'https://example.com/video.mp4');

// Carousel posts
Instagram::shareCarousel('Caption', ['img1.jpg', 'img2.jpg', 'img3.jpg']);

// Stories
Instagram::shareStory('Caption', 'https://example.com');

// Analytics
$accountInfo = Instagram::getAccountInfo();
$recentMedia = Instagram::getRecentMedia(25);
```

#### TikTok

```php
use TikTok;

// Video posting
TikTok::shareVideo('Caption', 'https://example.com/video.mp4');

// Analytics
$userInfo = TikTok::getUserInfo();
$userVideos = TikTok::getUserVideos(20);
```

#### YouTube

```php
use YouTube;

// Video uploads
YouTube::shareVideo('Title', 'https://example.com/video.mp4');

// Community posts
YouTube::createCommunityPost('Content', 'https://example.com');

// Analytics
$channelInfo = YouTube::getChannelInfo();
$channelVideos = YouTube::getChannelVideos(25);
$videoAnalytics = YouTube::getVideoAnalytics('video_id');
```

#### Pinterest

```php
use Pinterest;

// Pins
Pinterest::shareImage('Caption', 'https://example.com/image.jpg');
Pinterest::shareVideo('Caption', 'https://example.com/video.mp4');

// Boards
Pinterest::createBoard('Board Name', 'Description');

// Analytics
$userInfo = Pinterest::getUserInfo();
$boards = Pinterest::getBoards(25);
$boardPins = Pinterest::getBoardPins('board_id', 25);
$pinAnalytics = Pinterest::getPinAnalytics('pin_id');
```

#### Telegram

```php
use Telegram;

// Messages
Telegram::share('Content', 'https://example.com');
Telegram::shareImage('Caption', 'https://example.com/image.jpg');
Telegram::shareVideo('Caption', 'https://example.com/video.mp4');
Telegram::shareDocument('Caption', 'https://example.com/document.pdf');

// Bot updates
$updates = Telegram::getUpdates();
```

## 🔧 Advanced Features

### Error Handling

The package provides comprehensive error handling:

```php
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;

try {
    $result = SocialMedia::share(['facebook', 'twitter'], 'Content', 'https://example.com');
    
    // Check results
    if ($result['error_count'] > 0) {
        foreach ($result['errors'] as $platform => $error) {
            echo "Error on {$platform}: {$error}\n";
        }
    }
    
} catch (SocialMediaException $e) {
    echo "Social media error: " . $e->getMessage();
}
```

### Retry Logic

The package automatically retries failed requests with exponential backoff:

```php
// Configure retry attempts
config(['autopost.retry_attempts' => 5]);

// Configure timeout
config(['autopost.timeout' => 60]);
```

### Logging

All operations are automatically logged:

```php
// Enable/disable logging
config(['autopost.enable_logging' => true]);

// Check Laravel logs for detailed information
tail -f storage/logs/laravel.log
```

## 🧪 Testing

### Run Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Feature/

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

## 📄 License

This package is licensed under the [MIT License](LICENSE).

## 🆘 Support

- **Documentation**: [GitHub Wiki](https://github.com/limonhassan606/social-autoposter/wiki)
- **Issues**: [GitHub Issues](https://github.com/limonhassan606/social-autoposter/issues)
- **Email**: limonhassan606@gmail.com
- **Portfolio**: [https://limon.qzz.io](https://limon.qzz.io)

## 🙏 Acknowledgments 

- Laravel Framework
- All social media platform APIs
- The open-source community

---

**Made with ❤️ by [Limon Hasan](https://limon.qzz.io)**
