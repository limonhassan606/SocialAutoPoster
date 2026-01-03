<?php

namespace LimonHasan\SocialAutoPoster\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use LimonHasan\SocialAutoPoster\Exceptions\SocialMediaException;

class SchedulerService
{
    protected array $platforms = [];
    protected ?string $content = null;
    protected ?string $mediaUrl = null;
    protected ?string $mediaType = null;
    protected ?Carbon $publishAt = null;
    protected ?string $timezone = null;
    protected ?string $recurringType = null;
    protected ?string $recurringTime = null;
    protected ?Carbon $recurringUntil = null;
    protected array $metadata = [];
    protected ?int $priority = 5;

    public function platforms(array $platforms): self
    {
        $this->platforms = $platforms;
        return $this;
    }

    public function content(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function media(string $url, string $type = 'image'): self
    {
        $this->mediaUrl = $url;
        $this->mediaType = $type;
        return $this;
    }

    public function publishAt(string|Carbon $datetime): self
    {
        if (is_string($datetime)) {
            $datetime = Carbon::parse($datetime);
        }
        $this->publishAt = $datetime;
        return $this;
    }

    public function timezone(string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function recurring(string $type, string $time): self
    {
        $validTypes = ['daily', 'weekly', 'monthly'];
        if (!in_array($type, $validTypes)) {
            throw new SocialMediaException("Invalid recurring type. Must be one of: " . implode(', ', $validTypes));
        }
        $this->recurringType = $type;
        $this->recurringTime = $time;
        return $this;
    }

    public function until(string|Carbon $datetime): self
    {
        if (is_string($datetime)) {
            $datetime = Carbon::parse($datetime);
        }
        $this->recurringUntil = $datetime;
        return $this;
    }

    public function priority(int $priority): self
    {
        if ($priority < 1 || $priority > 10) {
            throw new SocialMediaException("Priority must be between 1 and 10");
        }
        $this->priority = $priority;
        return $this;
    }

    public function metadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    public function save(): array
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

    protected function saveRecurringPost(int $scheduledPostId): void
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

    protected function calculateNextRun(): Carbon
    {
        $timezone = $this->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);

        [$hour, $minute] = explode(':', $this->recurringTime);

        $nextRun = match ($this->recurringType) {
            'daily' => $now->copy()->setTime((int) $hour, (int) $minute),
            'weekly' => $now->copy()->next(Carbon::MONDAY)->setTime((int) $hour, (int) $minute),
            'monthly' => $now->copy()->addMonth()->startOfMonth()->setTime((int) $hour, (int) $minute),
            default => throw new SocialMediaException("Invalid recurring type"),
        };

        if ($nextRun->isPast()) {
            $nextRun = match ($this->recurringType) {
                'daily' => $nextRun->addDay(),
                'weekly' => $nextRun->addWeek(),
                'monthly' => $nextRun->addMonth(),
            };
        }

        return $nextRun->setTimezone('UTC');
    }

    public static function getUpcoming(int $limit = 10): array
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

    public static function processDue(): array
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
                    $result = match ($post->media_type) {
                        'video' => $socialMedia->shareVideo($platforms, $post->content, $post->media_url),
                        default => $socialMedia->shareImage($platforms, $post->content, $post->media_url),
                    };
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

    protected static function handleRecurring(int $scheduledPostId): void
    {
        $recurring = DB::table('recurring_posts')
            ->where('scheduled_post_id', $scheduledPostId)
            ->where('is_active', true)
            ->first();

        if (!$recurring)
            return;

        if ($recurring->until && Carbon::parse($recurring->until)->isPast()) {
            DB::table('recurring_posts')->where('id', $recurring->id)->update(['is_active' => false]);
            return;
        }

        // Logic to calculate next run and create new scheduled post...
        // For brevity in this turn, assuming the full logic is implemented as designed in the guide
        // Re-implementing specific recurring logic here

        $timezone = $recurring->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);
        [$hour, $minute] = explode(':', $recurring->time);

        $nextRun = match ($recurring->type) {
            'daily' => $now->copy()->addDay()->setTime((int) $hour, (int) $minute),
            'weekly' => $now->copy()->addWeek()->setTime((int) $hour, (int) $minute),
            'monthly' => $now->copy()->addMonth()->setTime((int) $hour, (int) $minute),
            default => $now->addDay(),
        };
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

    protected function validate(): void
    {
        if (empty($this->platforms))
            throw new SocialMediaException("At least one platform must be specified");
        if (empty($this->content))
            throw new SocialMediaException("Content is required");
        if (!$this->publishAt)
            throw new SocialMediaException("Publish date/time is required");
        if ($this->publishAt->isPast())
            throw new SocialMediaException("Publish date/time must be in the future");
    }
}
