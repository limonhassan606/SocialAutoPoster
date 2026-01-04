# Compatibility Guide

## Supported Versions

This package has been designed to work across a wide range of PHP and Laravel versions to maximize compatibility.

### PHP Versions
- ✅ PHP 7.4
- ✅ PHP 8.0
- ✅ PHP 8.1
- ✅ PHP 8.2
- ✅ PHP 8.3

### Laravel Versions
- ✅ Laravel 7.x
- ✅ Laravel 8.x
- ✅ Laravel 9.x
- ✅ Laravel 10.x
- ✅ Laravel 11.x
- ✅ Laravel 12.x

## What Was Changed for Compatibility

### PHP 7.4 Compatibility
To support PHP 7.4, the following changes were made:

1. **Removed Typed Properties**
   - Changed from: `protected ?string $content = null;`
   - Changed to: `/** @var string|null */ protected $content = null;`

2. **Removed Union Types**
   - Changed from: `public function publishAt(string|Carbon $datetime)`
   - Changed to: `public function publishAt($datetime)` with docblock `@param string|Carbon $datetime`

3. **Replaced Match Expressions**
   - Changed from: `match($type) { 'daily' => ..., 'weekly' => ... }`
   - Changed to: Traditional `switch` statements

4. **Array Destructuring**
   - Changed from: `[$hour, $minute] = explode(...)`
   - Changed to: `list($hour, $minute) = explode(...)`

### Laravel 7 Compatibility

1. **Migration Format**
   - Changed from: Anonymous class migrations (`return new class extends Migration`)
   - Changed to: Named class migrations (`class CreateSocialSchedulerTables extends Migration`)

2. **Foreign Key Syntax**
   - Used explicit foreign key definitions compatible with Laravel 7

## Testing Across Versions

### Recommended Testing Matrix

| PHP Version | Laravel Version | Status |
|-------------|----------------|--------|
| 7.4 | 7.x | ✅ Compatible |
| 7.4 | 8.x | ✅ Compatible |
| 8.0 | 8.x | ✅ Compatible |
| 8.0 | 9.x | ✅ Compatible |
| 8.1 | 9.x | ✅ Compatible |
| 8.1 | 10.x | ✅ Compatible |
| 8.2 | 10.x | ✅ Compatible |
| 8.2 | 11.x | ✅ Compatible |
| 8.3 | 11.x | ✅ Compatible |
| 8.3 | 12.x | ✅ Compatible |

## Installation Notes

### For Laravel 7/8 Users
If you're using Laravel 7 or 8, make sure to publish the migrations:

```bash
php artisan vendor:publish --provider="LimonHasan\SocialAutoPoster\SocialShareServiceProvider" --tag=autopost-migrations
php artisan migrate
```

### For PHP 7.4 Users
Ensure you have the following extensions enabled:
- `ext-json`
- `ext-curl`

Check with:
```bash
php -m | grep -E "json|curl"
```

## Known Limitations

### Laravel 7 Specific
- Some newer Laravel features (like anonymous migrations) are not available
- Ensure you're using compatible versions of dependencies

### PHP 7.4 Specific
- No native support for union types (handled via docblocks)
- No match expressions (using switch statements instead)
- No typed properties (using docblocks for type hints)

## Upgrading

If you're upgrading from an older version of this package, run:

```bash
composer update limonhasan/social-autoposter
php artisan migrate
php artisan config:clear
php artisan cache:clear
```

## Support

If you encounter compatibility issues with a specific PHP or Laravel version, please:
1. Check this compatibility guide
2. Review the [GitHub Issues](https://github.com/limonhassan606/social-autoposter/issues)
3. Create a new issue with your environment details
