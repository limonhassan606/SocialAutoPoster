<?php

namespace LimonHasan\SocialAutoPoster\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class TikTok
 *
 * @method static mixed share(string $caption, string $url)
 * @method static mixed shareImage(string $caption, string $image_url)
 * @method static mixed shareVideo(string $caption, string $video_url)
 * @method static mixed getUserInfo()
 * @method static mixed getUserVideos(int $max_count = 20)
 *
 * @see \LimonHasan\SocialAutoPoster\Services\TikTokService
 */
class TikTok extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \LimonHasan\SocialAutoPoster\Services\TikTokService::class;
    }
}
