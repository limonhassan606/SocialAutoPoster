<?php

namespace LimonHasan\SocialAutoPoster\Services;

use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;
use Illuminate\Support\Facades\Log;

/**
 * Class SocialMediaManager
 *
 * Manager service for handling multiple social media platforms.
 * Provides unified interface for posting to multiple platforms simultaneously.
 */
class SocialMediaManager
{
    /**
     * Available platforms mapping.
     */
    private const PLATFORMS = [
        'facebook' => FacebookService::class,
        'twitter' => TwitterService::class,
        'linkedin' => LinkedInService::class,
        'instagram' => InstagramService::class,
        'tiktok' => TikTokService::class,
        'youtube' => YouTubeService::class,
        'pinterest' => PinterestService::class,
        'telegram' => TelegramService::class,
    ];

    /**
     * Share content to multiple platforms.
     *
     * @param array $platforms Array of platform names.
     * @param string $caption The caption text.
     * @param string $url The URL to share.
     * @return array Results from all platforms.
     */
    public function share(array $platforms, string $caption, string $url): array
    {
        return $this->executeOnPlatforms($platforms, 'share', [$caption, $url]);
    }

    /**
     * Share image to multiple platforms.
     *
     * @param array $platforms Array of platform names.
     * @param string $caption The caption text.
     * @param string $image_url The image URL.
     * @return array Results from all platforms.
     */
    public function shareImage(array $platforms, string $caption, string $image_url): array
    {
        return $this->executeOnPlatforms($platforms, 'shareImage', [$caption, $image_url]);
    }

    /**
     * Share video to multiple platforms.
     *
     * @param array $platforms Array of platform names.
     * @param string $caption The caption text.
     * @param string $video_url The video URL.
     * @return array Results from all platforms.
     */
    public function shareVideo(array $platforms, string $caption, string $video_url): array
    {
        return $this->executeOnPlatforms($platforms, 'shareVideo', [$caption, $video_url]);
    }

    /**
     * Share content to all available platforms.
     *
     * @param string $caption The caption text.
     * @param string $url The URL to share.
     * @return array Results from all platforms.
     */
    public function shareToAll(string $caption, string $url): array
    {
        return $this->share(array_keys(self::PLATFORMS), $caption, $url);
    }

    /**
     * Share image to all available platforms.
     *
     * @param string $caption The caption text.
     * @param string $image_url The image URL.
     * @return array Results from all platforms.
     */
    public function shareImageToAll(string $caption, string $image_url): array
    {
        return $this->shareImage(array_keys(self::PLATFORMS), $caption, $image_url);
    }

    /**
     * Share video to all available platforms.
     *
     * @param string $caption The caption text.
     * @param string $video_url The video URL.
     * @return array Results from all platforms.
     */
    public function shareVideoToAll(string $caption, string $video_url): array
    {
        return $this->shareVideo(array_keys(self::PLATFORMS), $caption, $video_url);
    }

    /**
     * Get a specific platform service.
     *
     * @param string $platform The platform name.
     * @return mixed The platform service instance.
     * @throws SocialMediaException
     */
    public function platform(string $platform)
    {
        if (!isset(self::PLATFORMS[$platform])) {
            throw new SocialMediaException("Platform '{$platform}' is not supported.");
        }

        $serviceClass = self::PLATFORMS[$platform];
        return $serviceClass::getInstance();
    }

    /**
     * Get Facebook service.
     */
    public function facebook()
    {
        return $this->platform('facebook');
    }

    /**
     * Get Twitter service.
     */
    public function twitter()
    {
        return $this->platform('twitter');
    }

    /**
     * Get LinkedIn service.
     */
    public function linkedin()
    {
        return $this->platform('linkedin');
    }

    /**
     * Get Instagram service.
     */
    public function instagram()
    {
        return $this->platform('instagram');
    }

    /**
     * Get TikTok service.
     */
    public function tiktok()
    {
        return $this->platform('tiktok');
    }

    /**
     * Get YouTube service.
     */
    public function youtube()
    {
        return $this->platform('youtube');
    }

    /**
     * Get Pinterest service.
     */
    public function pinterest()
    {
        return $this->platform('pinterest');
    }

    /**
     * Get Telegram service.
     */
    public function telegram()
    {
        return $this->platform('telegram');
    }

    /**
     * Execute a method on multiple platforms.
     *
     * @param array $platforms Array of platform names.
     * @param string $method The method to execute.
     * @param array $parameters The method parameters.
     * @return array Results from all platforms.
     */
    private function executeOnPlatforms(array $platforms, string $method, array $parameters): array
    {
        $results = [];
        $errors = [];

        foreach ($platforms as $platform) {
            try {
                if (!isset(self::PLATFORMS[$platform])) {
                    $errors[$platform] = "Platform '{$platform}' is not supported.";
                    continue;
                }

                $serviceClass = self::PLATFORMS[$platform];
                $service = $serviceClass::getInstance();

                if (!method_exists($service, $method)) {
                    $errors[$platform] = "Method '{$method}' is not supported on platform '{$platform}'.";
                    continue;
                }

                $result = call_user_func_array([$service, $method], $parameters);
                $results[$platform] = [
                    'success' => true,
                    'data' => $result
                ];

                Log::info("Successfully posted to {$platform}", [
                    'platform' => $platform,
                    'method' => $method,
                    'result' => $result
                ]);

            } catch (\Exception $e) {
                $errors[$platform] = $e->getMessage();
                $results[$platform] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                Log::error("Failed to post to {$platform}", [
                    'platform' => $platform,
                    'method' => $method,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'results' => $results,
            'errors' => $errors,
            'success_count' => count(array_filter($results, fn($r) => $r['success'])),
            'error_count' => count($errors),
            'total_platforms' => count($platforms)
        ];
    }

    /**
     * Get available platforms.
     *
     * @return array Array of available platform names.
     */
    public function getAvailablePlatforms(): array
    {
        return array_keys(self::PLATFORMS);
    }

    /**
     * Check if a platform is available.
     *
     * @param string $platform The platform name.
     * @return bool True if platform is available.
     */
    public function isPlatformAvailable(string $platform): bool
    {
        return isset(self::PLATFORMS[$platform]);
    }

    /**
     * Get platform service class.
     *
     * @param string $platform The platform name.
     * @return string|null The service class name or null if not found.
     */
    public function getPlatformService(string $platform): ?string
    {
        return self::PLATFORMS[$platform] ?? null;
    }
}
