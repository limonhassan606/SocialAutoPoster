<?php

namespace LimonHasan\SocialAutoPoster\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;

class SchedulerService
{
    /** @var array */
    protected $platforms = [];

    /** @var string|null */
    protected $content = null;

    /** @var string|null */
    protected $mediaUrl = null;

    /** @var string|null */
    protected $mediaType = null;

    /** @var Carbon|null */
    protected $publishAt = null;

    /** @var string|null */
    protected $timezone = null;

    /** @var string|null */
    protected $recurringType = null;

    /** @var string|null */
    protected $recurringTime = null;

    /** @var Carbon|null */
    protected $recurringUntil = null;

    /** @var array */
    protected $metadata = [];

    /** @var int|null */
    protected $priority = 5;

    /**
     * @param array $platforms
     * @return $this
     */
    public function platforms(array $platforms)
    {
        $this->platforms = $platforms;
        return $this;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function content($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param string $url
     * @param string $type
     * @return $this
     */
    public function media($url, $type = 'image')
    {
        $this->mediaUrl = $url;
        $this->mediaType = $type;
        return $this;
    }

    /**
     * @param string|Carbon $datetime
     * @return $this
     */
    public function publishAt($datetime)
    {
        if (is_string($datetime)) {
            $datetime = Carbon::parse($datetime);
        }
        $this->publishAt = $datetime;
        return $this;
    }

    /**
     * @param string $timezone
     * @return $this
     */
    public function timezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @param string $type
     * @param string $time
     * @return $this
     */
    public function recurring($type, $time)
    {
        $validTypes = ['daily', 'weekly', 'monthly'];
        if (!in_array($type, $validTypes)) {
            throw new SocialMediaException("Invalid recurring type. Must be one of: " . implode(', ', $validTypes));
        }
        $this->recurringType = $type;
        $this->recurringTime = $time;
        return $this;
    }

    /**
     * @param string|Carbon $datetime
     * @return $this
     */
    public function until($datetime)
    {
        if (is_string($datetime)) {
            $datetime = Carbon::parse($datetime);
        }
        $this->recurringUntil = $datetime;
        return $this;
    }

    /**
     * @param int $priority
     * @return $this
     */
    public function priority($priority)
    {
        if ($priority < 1 || $priority > 10) {
            throw new SocialMediaException("Priority must be between 1 and 10");
        }
        $this->priority = $priority;
        return $this;
    }

    /**
     * @param array $metadata
     * @return $this
     */
    public function metadata(array $metadata)
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    /**
     * @return array
     */
    public function save()
    {
        $this->validate();

        $publishAtUtc = $this->timezone ? $this->publishAt->copy()->setTimezone('UTC') : $this->publishAt;

        $scheduledPost = [
            'platforms' => json_encode($this->platforms),
            'content' => $this->content,
            'media_url' => $this->mediaUrl,
            'media_type' => $this->mediaType,
            'publish_at' => $publishAtUtc,
            'timezone' => $this->timezone ?? config('app.timezone'),
            'priority' => $this->priority,
            'metadata' => json_encode($this->metadata),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('scheduled_posts')->insertGetId($scheduledPost);

        if ($this->recurringType) {
            $this->saveRecurringPost($id);
        }

        return [
            'success' => true,
            'id' => $id,
            'publish_at' => $publishAtUtc,
            'platforms' => $this->platforms,
            'recurring' => $this->recurringType !== null,
        ];
    }

    /**
     * @param int $scheduledPostId
     * @return void
     */
    protected function saveRecurringPost($scheduledPostId)
    {
        $recurringPost = [
            'scheduled_post_id' => $scheduledPostId,
            'type' => $this->recurringType,
            'time' => $this->recurringTime,
            'until' => $this->recurringUntil,
            'timezone' => $this->timezone ?? config('app.timezone'),
            'last_run_at' => null,
            'next_run_at' => $this->calculateNextRun(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('recurring_posts')->insert($recurringPost);
    }

    /**
     * @return Carbon
     */
    protected function calculateNextRun()
    {
        $timezone = $this->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);

        list($hour, $minute) = explode(':', $this->recurringTime);

        // Replace match() with switch for PHP 7.4 compatibility
        switch ($this->recurringType) {
            case 'daily':
                $nextRun = $now->copy()->setTime((int) $hour, (int) $minute);
                break;
            case 'weekly':
                $nextRun = $now->copy()->next(Carbon::MONDAY)->setTime((int) $hour, (int) $minute);
                break;
            case 'monthly':
                $nextRun = $now->copy()->addMonth()->startOfMonth()->setTime((int) $hour, (int) $minute);
                break;
            default:
                throw new SocialMediaException("Invalid recurring type");
        }

        if ($nextRun->isPast()) {
            switch ($this->recurringType) {
                case 'daily':
                    $nextRun = $nextRun->addDay();
                    break;
                case 'weekly':
                    $nextRun = $nextRun->addWeek();
                    break;
                case 'monthly':
                    $nextRun = $nextRun->addMonth();
                    break;
            }
        }

        return $nextRun->setTimezone('UTC');
    }

    /**
     * @param int $limit
     * @return array
     */
    public static function getUpcoming($limit = 10)
    {
        $posts = DB::table('scheduled_posts')
            ->where('status', 'pending')
            ->where('publish_at', '>', now())
            ->orderBy('publish_at')
            ->limit($limit)
            ->get();

        return $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'platforms' => json_decode($post->platforms, true),
                'content' => $post->content,
                'publish_at' => Carbon::parse($post->publish_at),
                'timezone' => $post->timezone,
                'priority' => $post->priority,
            ];
        })->toArray();
    }

    /**
     * @return array
     */
    public static function processDue()
    {
        $duePosts = DB::table('scheduled_posts')
            ->where('status', 'pending')
            ->where('publish_at', '<=', now())
            ->orderBy('priority', 'desc')
            ->orderBy('publish_at')
            ->get();

        $results = ['processed' => 0, 'successful' => 0, 'failed' => 0, 'errors' => []];

        foreach ($duePosts as $post) {
            $results['processed']++;
            try {
                $socialMedia = app('socialmedia');
                $platforms = json_decode($post->platforms, true);

                if ($post->media_url) {
                    // Replace match() with if-else for PHP 7.4 compatibility
                    if ($post->media_type === 'video') {
                        $result = $socialMedia->shareVideo($platforms, $post->content, $post->media_url);
                    } else {
                        $result = $socialMedia->shareImage($platforms, $post->content, $post->media_url);
                    }
                } else {
                    $result = $socialMedia->share($platforms, $post->content, '');
                }

                DB::table('scheduled_posts')->where('id', $post->id)->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'result' => json_encode($result),
                    'updated_at' => now(),
                ]);

                $results['successful']++;
                static::handleRecurring($post->id);

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = ['post_id' => $post->id, 'error' => $e->getMessage()];

                DB::table('scheduled_posts')->where('id', $post->id)->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $results;
    }

    /**
     * @param int $scheduledPostId
     * @return void
     */
    protected static function handleRecurring($scheduledPostId)
    {
        $recurring = DB::table('recurring_posts')
            ->where('scheduled_post_id', $scheduledPostId)
            ->where('is_active', true)
            ->first();

        if (!$recurring) {
            return;
        }

        if ($recurring->until && Carbon::parse($recurring->until)->isPast()) {
            DB::table('recurring_posts')->where('id', $recurring->id)->update(['is_active' => false]);
            return;
        }

        $timezone = $recurring->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);
        list($hour, $minute) = explode(':', $recurring->time);

        // Replace match() with switch for PHP 7.4 compatibility
        switch ($recurring->type) {
            case 'daily':
                $nextRun = $now->copy()->addDay()->setTime((int) $hour, (int) $minute);
                break;
            case 'weekly':
                $nextRun = $now->copy()->addWeek()->setTime((int) $hour, (int) $minute);
                break;
            case 'monthly':
                $nextRun = $now->copy()->addMonth()->setTime((int) $hour, (int) $minute);
                break;
            default:
                $nextRun = $now->addDay();
                break;
        }

        $nextRun = $nextRun->setTimezone('UTC');

        $originalPost = DB::table('scheduled_posts')->find($scheduledPostId);

        $newId = DB::table('scheduled_posts')->insertGetId([
            'platforms' => $originalPost->platforms,
            'content' => $originalPost->content,
            'media_url' => $originalPost->media_url,
            'media_type' => $originalPost->media_type,
            'publish_at' => $nextRun,
            'timezone' => $originalPost->timezone,
            'priority' => $originalPost->priority,
            'metadata' => $originalPost->metadata,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('recurring_posts')->where('id', $recurring->id)->update([
            'scheduled_post_id' => $newId,
            'last_run_at' => now(),
            'next_run_at' => $nextRun,
            'updated_at' => now(),
        ]);
    }

    /**
     * @return void
     */
    protected function validate()
    {
        if (empty($this->platforms)) {
            throw new SocialMediaException("At least one platform must be specified");
        }
        if (empty($this->content)) {
            throw new SocialMediaException("Content is required");
        }
        if (!$this->publishAt) {
            throw new SocialMediaException("Publish date/time is required");
        }
        if ($this->publishAt->isPast()) {
            throw new SocialMediaException("Publish date/time must be in the future");
        }
    }
}
