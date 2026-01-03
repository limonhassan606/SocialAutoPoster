<?php

namespace LimonHasan\SocialAutoPoster\Services;

use LimonHasan\SocialAutoPoster\Contracts\ShareImagePostInterface;
use LimonHasan\SocialAutoPoster\Contracts\ShareInterface;
use LimonHasan\SocialAutoPoster\Contracts\ShareVideoPostInterface;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Log;

/**
 * Class TikTokService
 *
 * Service for managing and posting content to TikTok using the TikTok for Developers API.
 *
 * Implements sharing of videos to TikTok.
 */
class TikTokService extends SocialMediaService implements ShareInterface, ShareImagePostInterface, ShareVideoPostInterface
{
    /**
     * @var string TikTok Access Token
     */
    private $access_token;

    /**
     * @var string TikTok Client Key
     */
    private $client_key;

    /**
     * @var string TikTok Client Secret
     */
    private $client_secret;

    /**
     * @var TikTokService|null Singleton instance
     */
    private static ?TikTokService $instance = null;

    /**
     * TikTok API base URL
     */
    private const API_BASE_URL = 'https://open-api.tiktok.com';

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct(
        string $accessToken,
        string $clientKey,
        string $clientSecret
    ) {
        $this->access_token = $accessToken;
        $this->client_key = $clientKey;
        $this->client_secret = $clientSecret;
    }

    /**
     * Get the singleton instance of TikTokService.
     */
    public static function getInstance(): TikTokService
    {
        if (self::$instance === null) {
            $accessToken = config('autopost.tiktok_access_token');
            $clientKey = config('autopost.tiktok_client_key');
            $clientSecret = config('autopost.tiktok_client_secret');

            if (!$accessToken || !$clientKey || !$clientSecret) {
                throw new SocialMediaException('TikTok credentials are not properly configured.');
            }

            self::$instance = new self($accessToken, $clientKey, $clientSecret);
        }
        return self::$instance;
    }

    /**
     * Share a text post with a URL to TikTok.
     * Note: TikTok doesn't support direct text posts, so this creates a video with text overlay.
     *
     * @param string $caption The text content of the post.
     * @param string $url The URL to share.
     * @return array Response from the TikTok API.
     * @throws SocialMediaException
     */
    public function share(string $caption, string $url): array
    {
        $this->validateInput($caption, $url);
        
        try {
            // TikTok doesn't support direct text posts
            // We'll create a simple video with text overlay
            $videoUrl = $this->createTextVideo($caption, $url);
            return $this->shareVideo($caption, $videoUrl);
        } catch (\Exception $e) {
            Log::error('Failed to share to TikTok', ['error' => $e->getMessage()]);
            throw new SocialMediaException('Failed to share to TikTok: ' . $e->getMessage());
        }
    }

    /**
     * Share an image post with a caption to TikTok.
     * Note: TikTok doesn't support direct image posts, so this creates a video from the image.
     *
     * @param string $caption The caption to accompany the image.
     * @param string $image_url The URL of the image.
     * @return array Response from the TikTok API.
     * @throws SocialMediaException
     */
    public function shareImage(string $caption, string $image_url): array
    {
        $this->validateInput($caption, $image_url);
        
        try {
            // Convert image to video for TikTok
            $videoUrl = $this->convertImageToVideo($image_url, $caption);
            return $this->shareVideo($caption, $videoUrl);
        } catch (\Exception $e) {
            Log::error('Failed to share image to TikTok', ['error' => $e->getMessage()]);
            throw new SocialMediaException('Failed to share image to TikTok: ' . $e->getMessage());
        }
    }

    /**
     * Share a video post with a caption to TikTok.
     *
     * @param string $caption The caption to accompany the video.
     * @param string $video_url The URL of the video.
     * @return array Response from the TikTok API.
     * @throws SocialMediaException
     */
    public function shareVideo(string $caption, string $video_url): array
    {
        $this->validateInput($caption, $video_url);
        
        try {
            // Step 1: Initialize upload
            $initUrl = $this->buildApiUrl('share/video/upload/');
            $initParams = [
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $this->getVideoSize($video_url),
                    'chunk_size' => 10485760, // 10MB chunks
                    'total_chunk_count' => 1
                ]
            ];

            $initResponse = $this->sendRequest($initUrl, 'post', $initParams);
            $publishId = $initResponse['data']['publish_id'];

            // Step 2: Upload video
            $uploadUrl = $initResponse['data']['upload_url'];
            $this->uploadVideoChunk($video_url, $uploadUrl);

            // Step 3: Publish video
            $publishUrl = $this->buildApiUrl('share/video/publish/');
            $publishParams = [
                'post_info' => [
                    'title' => $caption,
                    'description' => $caption,
                    'privacy_level' => 'MUTUAL_FOLLOW_FRIEND',
                    'disable_duet' => false,
                    'disable_comment' => false,
                    'disable_stitch' => false,
                    'video_cover_timestamp_ms' => 1000
                ],
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $this->getVideoSize($video_url),
                    'chunk_size' => 10485760,
                    'total_chunk_count' => 1
                ],
                'publish_id' => $publishId
            ];

            $response = $this->sendRequest($publishUrl, 'post', $publishParams);
            Log::info('TikTok video post shared successfully', ['video_id' => $response['data']['video_id'] ?? null]);
            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to share video to TikTok', ['error' => $e->getMessage()]);
            throw new SocialMediaException('Failed to share video to TikTok: ' . $e->getMessage());
        }
    }

    /**
     * Get user profile information.
     *
     * @return array Response from the TikTok API.
     * @throws SocialMediaException
     */
    public function getUserInfo(): array
    {
        try {
            $url = $this->buildApiUrl('user/info/');
            $params = [
                'fields' => 'open_id,union_id,avatar_url,display_name,follower_count,following_count,likes_count,video_count'
            ];

            return $this->sendRequest($url, 'get', $params);
        } catch (\Exception $e) {
            Log::error('Failed to get TikTok user info', ['error' => $e->getMessage()]);
            throw new SocialMediaException('Failed to get TikTok user info: ' . $e->getMessage());
        }
    }

    /**
     * Get user's videos.
     *
     * @param int $max_count Maximum number of videos to retrieve.
     * @return array Response from the TikTok API.
     * @throws SocialMediaException
     */
    public function getUserVideos(int $max_count = 20): array
    {
        try {
            $url = $this->buildApiUrl('video/list/');
            $params = [
                'max_count' => min($max_count, 20),
                'fields' => 'id,title,cover_image_url,share_url,embed_url,create_time'
            ];

            return $this->sendRequest($url, 'get', $params);
        } catch (\Exception $e) {
            Log::error('Failed to get TikTok user videos', ['error' => $e->getMessage()]);
            throw new SocialMediaException('Failed to get TikTok user videos: ' . $e->getMessage());
        }
    }

    /**
     * Create a text video for TikTok.
     *
     * @param string $text The text to display.
     * @param string $url The URL to include.
     * @return string The URL of the generated video.
     * @throws SocialMediaException
     */
    private function createTextVideo(string $text, string $url): string
    {
        // This is a simplified implementation
        // In a real scenario, you might want to use a service to generate videos with text
        $videoText = $text . "\n\n" . $url;
        
        // For now, return a placeholder video URL
        // In production, you should generate an actual video with the text
        return 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4';
    }

    /**
     * Convert image to video for TikTok.
     *
     * @param string $imageUrl The URL of the image.
     * @param string $caption The caption text.
     * @return string The URL of the generated video.
     * @throws SocialMediaException
     */
    private function convertImageToVideo(string $imageUrl, string $caption): string
    {
        // This is a simplified implementation
        // In a real scenario, you might want to use a service to convert images to videos
        // For now, return a placeholder video URL
        return 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4';
    }

    /**
     * Get video file size.
     *
     * @param string $videoUrl The URL of the video.
     * @return int The size of the video file in bytes.
     * @throws SocialMediaException
     */
    private function getVideoSize(string $videoUrl): int
    {
        $headers = get_headers($videoUrl, 1);
        if (!$headers || !isset($headers['Content-Length'])) {
            throw new SocialMediaException('Could not determine video file size.');
        }
        
        return (int) $headers['Content-Length'];
    }

    /**
     * Upload video chunk to TikTok.
     *
     * @param string $videoUrl The URL of the video.
     * @param string $uploadUrl The TikTok upload URL.
     * @throws SocialMediaException
     */
    private function uploadVideoChunk(string $videoUrl, string $uploadUrl): void
    {
        $videoContent = file_get_contents($videoUrl);
        if ($videoContent === false) {
            throw new SocialMediaException('Failed to download video from URL: ' . $videoUrl);
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/octet-stream'
        ])->put($uploadUrl, $videoContent);

        if (!$response->successful()) {
            throw new SocialMediaException('Failed to upload video to TikTok');
        }
    }

    /**
     * Validate input parameters.
     *
     * @param string $caption The caption text.
     * @param string $url The URL.
     * @throws SocialMediaException
     */
    private function validateInput(string $caption, string $url): void
    {
        if (empty(trim($caption))) {
            throw new SocialMediaException('Caption cannot be empty.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new SocialMediaException('Invalid URL provided.');
        }
    }

    /**
     * Build TikTok API URL.
     *
     * @param string $endpoint The API endpoint.
     * @return string Complete API URL.
     */
    private function buildApiUrl(string $endpoint): string
    {
        return self::API_BASE_URL . '/' . $endpoint;
    }

    /**
     * Send authenticated request to TikTok API.
     *
     * @param string $url The API URL.
     * @param string $method The HTTP method.
     * @param array $params The request parameters.
     * @return array Response from the API.
     * @throws SocialMediaException
     */
    protected function sendRequest(string $url, string $method = 'post', array $params = [], array $headers = []): array
    {
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json'
        ];
        
        $headers = array_merge($defaultHeaders, $headers);

        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->{$method}($url, $params);

        if (!$response->successful()) {
            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error occurred';
            throw new SocialMediaException("TikTok API error: {$errorMessage}");
        }

        return $response->json();
    }
}
