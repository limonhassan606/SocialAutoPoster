<?php

namespace LimonHasan\SocialAutoPoster\Commands;

use Illuminate\Console\Command;
use LimonHasan\SocialAutoPoster\Services\SchedulerService;

class ProcessScheduledPosts extends Command
{
    protected $signature = 'social:process-scheduled {--dry-run} {--limit=10}';
    protected $description = 'Process and publish scheduled social media posts';

    public function handle(): int
    {
        $this->info('🚀 Processing scheduled posts...');

        if ($this->option('dry-run')) {
            $this->warn('⚠️ DRY RUN MODE');
            $upcoming = SchedulerService::getUpcoming($this->option('limit'));
            $this->table(['ID', 'Content', 'Publish At'], array_map(fn($p) => [
                $p['id'],
                substr($p['content'], 0, 30),
                $p['publish_at']->format('Y-m-d H:i')
            ], $upcoming));
            return 0;
        }

        try {
            $results = SchedulerService::processDue();
            $this->info("✓ Processed: {$results['processed']}");
            $this->info("✓ Successful: {$results['successful']}");

            if ($results['failed'] > 0) {
                $this->error("✗ Failed: {$results['failed']}");
                foreach ($results['errors'] as $error) {
                    $this->line("  Post #{$error['post_id']}: {$error['error']}");
                }
                return 1;
            }
            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
