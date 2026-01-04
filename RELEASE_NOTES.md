# Release Notes - Version 1.5.0

**Release Date**: January 4, 2026  
**Package**: limonhasan/social-autoposter  
**Maintainer**: Limon Hasan

---

## 🎉 Major Update: Universal Compatibility

We're excited to announce version **1.5.0** with **massive compatibility improvements** and the new **Smart Scheduling System**!

---

## 🌟 What's New

### 📦 Broad Version Support

Your package now works with a **much wider range** of environments:

#### PHP Compatibility
- ✅ PHP 7.4
- ✅ PHP 8.0
- ✅ PHP 8.1
- ✅ PHP 8.2
- ✅ PHP 8.3

#### Laravel Compatibility
- ✅ Laravel 7.x
- ✅ Laravel 8.x
- ✅ Laravel 9.x
- ✅ Laravel 10.x
- ✅ Laravel 11.x
- ✅ Laravel 12.x

**This means you can now use Social Auto Poster in legacy projects and cutting-edge applications alike!**

---

### ⏰ Smart Scheduling System

Schedule your social media posts for optimal engagement:

```php
use LimonHasan\SocialAutoPoster\Services\SchedulerService;

// Schedule a one-time post
(new SchedulerService())
    ->platforms(['facebook', 'twitter'])
    ->content('Scheduled post for tomorrow!')
    ->publishAt('2026-01-05 10:00:00')
    ->timezone('America/New_York')
    ->priority(8)
    ->save();

// Schedule recurring posts
(new SchedulerService())
    ->platforms(['linkedin'])
    ->content('Weekly update every Monday')
    ->publishAt('2026-01-06 09:00:00')
    ->recurring('weekly', '09:00')
    ->until('2026-12-31 23:59:59')
    ->save();
```

#### Features:
- **One-time Scheduling**: Post at specific dates and times
- **Recurring Posts**: Daily, weekly, or monthly schedules
- **Timezone Support**: Schedule in any timezone
- **Priority System**: Control post order (1-10)
- **Artisan Command**: `php artisan social:process-scheduled` to publish due posts
- **Dry Run Mode**: Preview scheduled posts without publishing

---

## 🔧 Technical Improvements

### Code Modernization
- Refactored for **PHP 7.4+ compatibility** while maintaining modern code quality
- Replaced PHP 8-specific syntax with backwards-compatible alternatives
- Enhanced PHPDoc annotations for better IDE support

### Migration Updates
- Converted to **Laravel 7-compatible** class-based migrations
- Ensures smooth installation across all supported Laravel versions

### Dependency Management
- Updated Composer requirements to support wider version ranges
- Compatible test suites for PHPUnit 9, 10, and 11
- Orchestra Testbench support for Laravel 5 through 10

---

## 📦 Installation & Upgrade

### New Installation

```bash
composer require limonhasan/social-autoposter
```

### Upgrading from 1.0.0

```bash
composer update limonhasan/social-autoposter
php artisan vendor:publish --provider="LimonHasan\SocialAutoPoster\SocialShareServiceProvider" --tag=autopost-migrations
php artisan migrate
php artisan config:clear
```

---

## � New Documentation

- **[COMPATIBILITY.md](COMPATIBILITY.md)**: Detailed compatibility matrix and migration guide
- **[API_KEYS_GUIDE.md](API_KEYS_GUIDE.md)**: Step-by-step guide for obtaining API credentials

---

## 🚀 Getting Started with Scheduling

### 1. Publish Migrations

```bash
php artisan vendor:publish --provider="LimonHasan\SocialAutoPoster\SocialShareServiceProvider" --tag=autopost-migrations
php artisan migrate
```

### 2. Schedule a Post

```php
use LimonHasan\SocialAutoPoster\Services\SchedulerService;

(new SchedulerService())
    ->platforms(['facebook', 'twitter', 'linkedin'])
    ->content('Hello from the future!')
    ->publishAt('2026-01-05 14:00:00')
    ->save();
```

### 3. Process Scheduled Posts

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('social:process-scheduled')->everyMinute();
}
```

Or run manually:

```bash
php artisan social:process-scheduled
```

---

## 🎯 Use Cases

### Content Calendar Management
Schedule an entire month of posts in advance:

```php
$posts = [
    ['date' => '2026-01-10 09:00', 'content' => 'Week 1 update'],
    ['date' => '2026-01-17 09:00', 'content' => 'Week 2 update'],
    ['date' => '2026-01-24 09:00', 'content' => 'Week 3 update'],
];

foreach ($posts as $post) {
    (new SchedulerService())
        ->platforms(['facebook', 'linkedin'])
        ->content($post['content'])
        ->publishAt($post['date'])
        ->save();
}
```

### Automated Daily Updates
Post daily motivational quotes:

```php
(new SchedulerService())
    ->platforms(['twitter', 'instagram'])
    ->content('Daily inspiration!')
    ->publishAt('2026-01-05 08:00:00')
    ->recurring('daily', '08:00')
    ->save();
```

---

## 📞 Support & Community

- **Documentation**: See the [README](README.md) for full details
- **Compatibility Guide**: [COMPATIBILITY.md](COMPATIBILITY.md)
- **API Setup**: [API_KEYS_GUIDE.md](API_KEYS_GUIDE.md)
- **Issues**: [GitHub Issues](https://github.com/limonhassan606/social-autoposter/issues)
- **Portfolio**: [https://limon.qzz.io](https://limon.qzz.io)
- **Email**: limonhassan606@gmail.com

---

## 🙏 Thank You

Thank you to everyone using Social Auto Poster! This update makes the package accessible to **thousands more projects** running on older but stable Laravel versions.

---

**Made with ❤️ by [Limon Hasan](https://limon.qzz.io)**
