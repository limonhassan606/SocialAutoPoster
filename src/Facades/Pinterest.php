<?php

namespace LimonHasan\SocialAutoPoster\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Pinterest
 *
 * @method static mixed share(string $caption, string $url)
 * @method static mixed shareImage(string $caption, string $image_url)
 * @method static mixed shareVideo(string $caption, string $video_url)
 * @method static mixed createPin(string $note, string $mediaUrl, string $mediaType = 'image')
 * @method static mixed createBoard(string $name, string $description = '', string $privacy = 'PUBLIC')
 * @method static mixed getBoards(int $pageSize = 25)
 * @method static mixed getBoardPins(string $boardId, int $pageSize = 25)
 * @method static mixed getUserInfo()
 * @method static mixed getPinAnalytics(string $pinId)
 * @method static mixed searchPins(string $query, int $pageSize = 25)
 *
 * @see \LimonHasan\SocialAutoPoster\Services\PinterestService
 */
class Pinterest extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \LimonHasan\SocialAutoPoster\Services\PinterestService::class;
    }
}
