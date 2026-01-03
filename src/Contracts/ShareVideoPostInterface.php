<?php

namespace LimonHasan\SocialAutoPoster\Contracts;

interface ShareVideoPostInterface {

    public function shareVideo(string $caption, string $video_url): array;

}