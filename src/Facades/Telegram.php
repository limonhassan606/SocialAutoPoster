<?php

namespace LimonHasan\SocialAutoPoster\Facades;

use Illuminate\Support\Facades\Facade;
/**
 * Class Telegram
 *
 * @method static mixed share(string $caption, string $url)
 * @method static mixed shareImage(string $caption, string $image_url)
 * @method static mixed shareDocument(string $caption, string $document_url)
 * @method static mixed shareVideo(string $caption, string $video_url)
 * @method static mixed getUpdates()
 *
 * @see \LimonHasan\SocialAutoPoster\Services\TelegramService
 */
class Telegram extends Facade {

    protected static function getFacadeAccessor() {
        return \LimonHasan\SocialAutoPoster\Services\TelegramService::class;
    }
}