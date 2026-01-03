<?php

namespace LimonHasan\SocialAutoPoster\Facades;

use Illuminate\Support\Facades\Facade;
use LimonHasan\SocialAutoPoster\Services\SocialMediaManager;

/**
 * Class SocialMedia
 *
 * Unified facade for posting to multiple social media platforms simultaneously.
 *
 * @method static array share(array $platforms, string $caption, string $url)
 * @method static array shareImage(array $platforms, string $caption, string $image_url)
 * @method static array shareVideo(array $platforms, string $caption, string $video_url)
 * @method static array shareToAll(string $caption, string $url)
 * @method static array shareImageToAll(string $caption, string $image_url)
 * @method static array shareVideoToAll(string $caption, string $video_url)
 * @method static SocialMediaManager platform(string $platform)
 * @method static SocialMediaManager facebook()
 * @method static SocialMediaManager twitter()
 * @method static SocialMediaManager linkedin()
 * @method static SocialMediaManager instagram()
 * @method static SocialMediaManager tiktok()
 * @method static SocialMediaManager youtube()
 * @method static SocialMediaManager pinterest()
 * @method static SocialMediaManager telegram()
 *
 * @see \LimonHasan\SocialAutoPoster\Services\SocialMediaManager
 */
class SocialMedia extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SocialMediaManager::class;
    }
}
