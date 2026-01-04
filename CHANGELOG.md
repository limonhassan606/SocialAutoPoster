# Changelog

All notable changes to `social-autoposter` will be documented in this file.

## [1.5.0] - 2026-01-04

### Added
- **Broad Compatibility**: Now supports PHP 7.4, 8.0, 8.1, 8.2, and 8.3
- **Laravel 7+ Support**: Compatible with Laravel 7.x through 12.x
- **Smart Scheduling System**: Schedule posts for specific times with recurring options (daily, weekly, monthly)
- **Artisan Command**: `php artisan social:process-scheduled` to process scheduled posts
- **Database Migrations**: Tables for scheduled and recurring posts
- **Compatibility Guide**: Comprehensive documentation for version compatibility

### Changed
- Refactored `SchedulerService` to use PHP 7.4-compatible syntax
- Converted migrations to Laravel 7-compatible class-based format
- Updated composer dependencies to support wider version ranges
- Improved code documentation with PHPDoc annotations

### Technical Details
- Removed typed properties for PHP 7.4 compatibility
- Replaced `match()` expressions with `switch` statements
- Converted union types to docblock annotations
- Updated array destructuring to use `list()` syntax

---

## [1.0.0] - 2026-01-03

### Initial Release
- Launched `limonhasan/social-autoposter` package
- Complete solution for social media auto-posting
- Support for 8 major platforms: Facebook, Twitter/X, LinkedIn, Instagram, TikTok, YouTube, Pinterest, Telegram
- Unified API for multi-platform sharing
- Comprehensive analytics and reporting
- Production-ready error handling and retry logic
