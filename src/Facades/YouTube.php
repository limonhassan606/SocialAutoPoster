<?php

namespace LimonHasan\SocialAutoPoster\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class YouTube
 *
 * @method static mixed share(string $caption, string $url)
 * @method static mixed shareImage(string $caption, string $image_url)
 * @method static mixed shareVideo(string $caption, string $video_url)
 * @method static mixed createCommunityPost(string $text, string $url, string $type = 'text')
 * @method static mixed getChannelInfo()
 * @method static mixed getChannelVideos(int $maxResults = 25)
 * @method static mixed getVideoAnalytics(string $videoId)
 *
 * @see \LimonHasan\SocialAutoPoster\Services\YouTubeService
 */
class YouTube extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \LimonHasan\SocialAutoPoster\Services\YouTubeService::class;
    }
}
