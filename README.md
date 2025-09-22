<p align="center"><img src="art/social-card.png" alt="Social card of Statamic Static Cache Buster"></p>

# Statamic Static Cache Buster

<!-- BADGES -->

Plugin for Statamic that expands the standard cache validation to invalidate the static cache of all relevant URLs

## Quick start

### Requirements

| Dependency | Minimum version |
|------------|-----------------|
| PHP        | 8.0             |
| Laravel    | 10.0            |
| Statamic   | 5.0             |

### Installation

```bash
composer require van-ons/statamic-static-cache-buster
```

### Usage

Once installed, you simply need to use the buster class in Statamic's static cache invalidation configuration:
```php
'invalidation' => [
    'class' => VanOns\StatamicStaticCacheBuster\StaticCaching\Buster::class,
],
```
And you will immediately get the full power of the static cache busting.

The buster adds a lot of extra decisions to the static cache invalidation logic.
It is good to be aware of everything it does,
you can check [functionality] for a full description of
how the buster decides which URLs to invalidate.

### Configuration

The default buster configuration should work just fine for most websites.
However, there are some options to adjust the buster to your specific needs.

See [configuration] for the configuration options.

## Documentation

Please see the [documentation] for detailed information about installation and usage.

## Contributing

Please see [contributing] for more information about how you can contribute.

## Changelog

Please see [changelog] for more information about what has changed recently.

## Upgrading

Please see [upgrading] for more information about how to upgrade.

## Security

Please see [security] for more information about how we deal with security.

## Credits

We would like to thank the following contributors for their contributions to this project:

- [All Contributors][all-contributors]

## License

The scripts and documentation in this project are released under the [MIT License][license].

---

<p align="center"><a href="https://van-ons.nl/" target="_blank"><img src="https://opensource.van-ons.nl/files/cow.png" width="50" alt="Logo of Van Ons"></a></p>

[functionality]: docs/functionality.md

[configuration]: docs/configuration.md

[contributing]: CONTRIBUTING.md

[changelog]: CHANGELOG.md

[upgrading]: UPGRADING.md

[security]: SECURITY.md

[email]: mailto:opensource@van-ons.nl

[all-contributors]: ../../contributors

[license]: LICENSE.md
