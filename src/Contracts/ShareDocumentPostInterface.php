<?php

namespace LimonHasan\SocialAutoPoster\Contracts;

interface ShareDocumentPostInterface {

    public function shareDocument(string $caption, string $document_url): array;

}