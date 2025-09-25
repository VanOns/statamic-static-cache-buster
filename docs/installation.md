# Installation

First, install the package via Composer:

```bash
composer require van-ons/statamic-static-cache-buster
```

Once installed, you simply need to use the buster class in Statamic's static cache invalidation configuration:

```php
'invalidation' => [
    'class' => VanOns\StatamicStaticCacheBuster\StaticCaching\Buster::class,
],
```

And you will immediately get the full power of the static cache busting.
