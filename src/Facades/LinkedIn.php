<?php

namespace LimonHasan\SocialAutoPoster\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class LinkedIn
 *
 * @method static mixed share(string $caption, string $url)
 * @method static mixed shareImage(string $caption, string $image_url)
 * @method static mixed shareVideo(string $caption, string $video_url)
 * @method static mixed shareToCompanyPage(string $caption, string $url)
 * @method static mixed getUserInfo()
 *
 * @see \LimonHasan\SocialAutoPoster\Services\LinkedInService
 */
class LinkedIn extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \LimonHasan\SocialAutoPoster\Services\LinkedInService::class;
    }
}
