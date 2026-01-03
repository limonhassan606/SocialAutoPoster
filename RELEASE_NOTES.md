# Release Notes - Version 1.0.0

**Release Date**: January 3, 2026  
**Package**: limonhasan/social-autoposter  
**Maintainer**: Limon Hasan

---

## 🎉 Initial Release

We are excited to announce the first official release of **Social Auto Poster**, a comprehensive Laravel package designed to simplify and automate social media management.

### Key Features

#### 🌐 8 Major Platforms Supported
✅ Facebook  
✅ Twitter/X  
✅ LinkedIn  
✅ Instagram  
✅ TikTok  
✅ YouTube  
✅ Pinterest  
✅ Telegram  

#### ⚡ Core Capabilities
- **Unified API**: Post to one or multiple platforms with a single line of code.
- **Smart Scheduling**: Schedule posts for specific times, including support for recurring (daily, weekly, monthly) posts.
- **Media Support**: Share text, images, videos, documents, stories, and carousels seamlessly.
- **Advanced Analytics**: Track performance with built-in insights for supported platforms.
- **Robust Architecture**: Built with error handling, automatic retries, and detailed logging for production environments.

---

## 📦 Installation

```bash
composer require limonhasan/social-autoposter
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="LimonHasan\SocialAutoPoster\SocialShareServiceProvider" --tag=autopost
```

---

## 🚀 Getting Started

Here's how easy it is to share content across platforms:

```php
use LimonHasan\SocialAutoPoster\Facades\SocialMedia;

// Instant Share
SocialMedia::share(['facebook', 'twitter'], 'Hello World!', 'https://example.com');

// Schedule for Later
SocialMedia::schedule(['linkedin'])
    ->content('Scheduled post example')
    ->publishAt('2026-01-04 10:00:00')
    ->save();
```

---

## 📞 Support & Community

- **Documentation**: See the [README](README.md) for full details.
- **Issues**: [GitHub Issues](https://github.com/limonhassan606/social-autoposter/issues)
- **Portfolio**: [https://limon.qzz.io](https://limon.qzz.io)
- **Email**: limonhassan606@gmail.com

---

**Made with ❤️ by [Limon Hasan](https://limon.qzz.io)**
