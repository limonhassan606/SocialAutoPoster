<?php

namespace LimonHasan\SocialAutoPoster\Contracts;

interface ShareInterface {

    public function share(string $caption, string $url): array;

}