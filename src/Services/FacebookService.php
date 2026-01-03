<?php

namespace LimonHasan\SocialAutoPoster\Services;

use LimonHasan\SocialAutoPoster\Contracts\ShareImagePostInterface;
use LimonHasan\SocialAutoPoster\Contracts\ShareInterface;
use LimonHasan\SocialAutoPoster\Contracts\ShareVideoPostInterface;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Log;

/**
 * Class FacebookService
 *
 * Service for managing and posting content to Facebook using the Graph API.
 *
 * Implements sharing of general posts, images, and videos to a Facebook page.
 */
class FacebookService extends SocialMediaService implements ShareInterface, ShareImagePostInterface, ShareVideoPostInterface
{

    /**
     * @var string Facebook access token
     */
    private $access_token;

    /**
     * @var string Facebook page ID
     */
    private $page_id;

    /**
     * @var FacebookService|null Singleton instance
     */
    private static ?FacebookService $instance = null;
    /**
     * Facebook API version
     */
    private const API_VERSION = 'v20.0';


    /**
     * Private constructor to prevent direct instantiation.
     */

    private function __construct(string $accessToken, string $pageId)
    {
        $this->access_token = $accessToken;
        $this->page_id = $pageId;
    }

    /**
     * Get the singleton instance of FacebookService.
     *
     * @return FacebookService
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            $accessToken = config('autopost.facebook_access_token');
            $pageId = config('autopost.facebook_page_id');
            self::$instance = new self($accessToken, $pageId);
        }
        return self::$instance;
    }

    /**
     * Share an image post with a caption and an image URL to Facebook.
     *
     * @param string $caption The caption to accompany the image.
     * @param string $image_url The URL of the image.
     *
     * @return array Response from the Facebook API.
     * @throws SocialMediaException
     */
    public function shareImage(string $caption, string $image_url): array
    {
        $this->validateText($caption, 2000);
        $this->validateUrl($image_url);

        try {
            $url = $this->buildApiUrl('photos');
            $params = $this->buildParams([
                'url' => $image_url,
                'caption' => $caption,
            ]);

            $response = $this->sendRequest($url, 'post', $params);
            Log::info('Facebook image post shared successfully', ['post_id' => $response['id'] ?? null]);
            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to share image to Facebook', ['error' => $e->getMessage()]);
            throw new SocialMediaException('Failed to share image to Facebook: ' . $e->getMessage());
        }
    }

    /**
     * Share a text post with a caption and a URL to Facebook.
     *
     * @param string $caption The caption to accompany the post.
     * @param string $url The URL to share.
     *
     * @return array Response from the Facebook API.
     * @throws SocialMediaException
     */
    public function share(string $caption, string $url): array
    {
        $this->validateText($caption, 2000);
        $this->validateUrl($url);

        try {
            $feedUrl = $this->buildApiUrl('feed');
            $params = $this->buildParams([
                'message' => $caption,
                'link' => $url,
            ]);

            $response = $this->sendRequest($feedUrl, 'post', $params);
            Log::info('Facebook post shared successfully', ['post_id' => $response['id'] ?? null]);
            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to share to Facebook', ['error' => $e->getMessage()]);
            throw new SocialMediaException('Failed to share to Facebook: ' . $e->getMessage());
        }
    }

    /**
     * Share a video post with a caption and a video URL to Facebook.
     *
     * @param string $caption The caption to accompany the video.
     * @param string $video_url The URL of the video (local file path or remote URL).
     *
     * @return mixed Response from the Facebook API.
     */
    public function shareVideo(string $caption, string $video_url): array
    {
        $this->validateText($caption, 2000);

        // Step 1: Check if the video URL is remote and download the file if necessary
        $video_path = $this->downloadIfRemote($video_url);

        if (!file_exists($video_path)) {
            throw new SocialMediaException('Failed to download video or file does not exist.');
        }

        // Step 2: Get the size of the video file
        $fileSize = filesize($video_path);
        if ($fileSize === 0) {
            throw new SocialMediaException('Video file is empty.');
        }

        try {
            // Step 3: Start the upload session
            $startUrl = $this->buildApiUrl('videos');
            $params = $this->buildParams([
                'upload_phase' => 'start',
                'file_size' => $fileSize,
            ]);

            $response = $this->sendRequest($startUrl, 'post', $params);
            $uploadSessionId = $response['upload_session_id'] ?? null;

            if (!$uploadSessionId) {
                throw new SocialMediaException('Failed to start video upload session.');
            }

            // Step 4: Upload the video in chunks
            $startOffset = $response['start_offset'] ?? 0;

            while ($startOffset < $fileSize) {
                $endOffset = min($startOffset + (1024 * 1024 * 10), $fileSize); // 10MB chunks
                $chunkPath = $this->saveVideoChunk($video_path, $startOffset, $endOffset);

                if (!file_exists($chunkPath)) {
                    throw new SocialMediaException('Failed to save video chunk.');
                }

                $params = $this->buildParams([
                    'upload_phase' => 'transfer',
                    'upload_session_id' => $uploadSessionId,
                    'start_offset' => $startOffset,
                    'video_file_chunk' => new \CURLFile($chunkPath)
                ]);

                $transferResponse = $this->sendRequest($startUrl, 'post', $params);

                @unlink($chunkPath);
                $startOffset = $transferResponse['start_offset'] ?? $fileSize;
            }

            // Step 5: Complete the video upload
            $result = $this->completeVideoUpload($uploadSessionId, $caption);

            if (strpos($video_path, sys_get_temp_dir()) !== false) {
                @unlink($video_path);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to share video to Facebook', ['error' => $e->getMessage()]);
            throw new SocialMediaException('Failed to share video to Facebook: ' . $e->getMessage());
        }
    }

    /**
     * Helper to download the video file if it's a remote URL.
     *
     * @param string $video_url The remote URL or local file path of the video.
     *
     * @return string Local file path of the video.
     */
    private function downloadIfRemote(string $video_url): string
    {
        if (filter_var($video_url, FILTER_VALIDATE_URL)) {
            $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('fb_video_') . '.mp4';
            $content = $this->downloadFile($video_url);
            file_put_contents($tempPath, $content);
            return $tempPath;
        }
        return $video_url;
    }

    /**
     * Helper to save a chunk of the video file for transfer.
     *
     * @param string $video_path Path to the video file.
     * @param int $start_offset The start byte for the chunk.
     * @param int $end_offset The end byte for the chunk.
     *
     * @return string The path to the saved chunk file.
     */
    private function saveVideoChunk(string $video_path, int $start_offset, int $end_offset): string
    {
        $chunkPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('chunk_') . '.mp4';

        $handle = fopen($video_path, 'rb');
        fseek($handle, $start_offset);
        $chunkData = fread($handle, $end_offset - $start_offset);
        fclose($handle);

        file_put_contents($chunkPath, $chunkData);
        return $chunkPath;
    }

    /**
     * Complete the video upload process.
     *
     * @param string $uploadSessionId The upload session ID.
     * @param string $caption The caption to accompany the video.
     *
     * @return mixed Response from the Facebook API.
     */
    private function completeVideoUpload(string $uploadSessionId, string $caption)
    {
        $completeUrl = $this->buildApiUrl('videos');
        $params = $this->buildParams([
            'upload_phase' => 'finish',
            'upload_session_id' => $uploadSessionId,
            'description' => $caption,
            'title' => $caption,
            'published' => 'true',
        ]);

        return $this->sendRequest($completeUrl, 'post', $params);
    }



    /**
     * Retrieve insights for the Facebook page.
     *
     * @return mixed Response from the Facebook API.
     */
    public function getPageInsights(array $metrics = [], array $additionalParams = []): array
    {
        $url = $this->buildApiUrl('insights');
        $params = $this->buildParams(array_merge([
            'metric' => implode(',', $metrics),
        ], $additionalParams));

        return $this->sendRequest($url, 'get', $params);
    }


    /**
     * Retrieve information about the Facebook page.
     *
     * @return mixed Response from the Facebook API.
     */
    public function getPageInfo()
    {
        $url = $this->buildApiUrl();
        $params = $this->buildParams();

        return $this->sendRequest($url, 'get', $params);
    }


    /**
     * Helper to build Facebook Graph API URL.
     *
     * @param string $endpoint
     *
     * @return string
     */
    private function buildApiUrl(string $endpoint = ''): string
    {
        $apiVersion = config('autopost.facebook_api_version', 'v20.0');
        $isVideos = str_contains($endpoint, 'videos');
        $host = $isVideos ? 'graph-video.facebook.com' : 'graph.facebook.com';
        return "https://{$host}/{$apiVersion}/{$this->page_id}/" . ltrim($endpoint, '/');
    }

    /**
     * Helper to build request parameters.
     *
     * @param array $params
     *
     * @return array
     */
    private function buildParams(array $params = []): array
    {
        return array_merge($params, ['access_token' => $this->access_token]);
    }
}