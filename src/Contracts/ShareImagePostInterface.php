<?php

namespace LimonHasan\SocialAutoPoster\Contracts;

interface ShareImagePostInterface {

    public function shareImage(string $caption, string $image_url): array;

}