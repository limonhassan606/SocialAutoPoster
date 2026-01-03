<?php

namespace LimonHasan\SocialAutoPoster\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class FaceBook
 *
 * @method static mixed share(string $caption, string $url)
 * @method static mixed shareImage(string $caption, string $image_url)
 * @method static mixed shareVideo(string $caption, string $video_url)
 * @method static mixed getPageInsights(array $metrics = [], array $additionalParams = [])
 * @method static mixed getPageInfo()
 *
 * @see \LimonHasan\SocialAutoPoster\Services\FacebookService
 */
class FaceBook extends Facade {

    protected static function getFacadeAccessor() {
        return \LimonHasan\SocialAutoPoster\Services\FacebookService::class;
    }
}
