# Configuration

## Additional entry paths

### Description

Only the direct URL of entries are invalidated.
But sometimes you will have more than 1 URL in use for entries.
With this configuration you can specify these additional paths,
when an entry is invalidated it will also invalidate these.

### Examples

The default behaviour includes no additional paths:

```php
'additional_entry_paths' => [],
```

If you have a `products` collection where each entry also has a downloads URL:

```php
'additional_entry_paths' => [
    'products' => [
        '/downloads'
    ]
],
```

When entries in your `blog_posts` collection also have separate comments and questions URLs:

```php
'additional_entry_paths' => [
    'blog_posts' => [
        '/comments'
        '/questions'
    ]
],
```

## Queue

Due to the high volume of entries that need to be check for invalidation,
the buster splits this up into separate jobs.

These jobs are normally added to the default queue, but you can adjust this as necessary:
```php
'queue' => 'static_cache_invalidation',
```

## Chunk size

While the entries to check are chunked to prevent the jobs from taking too long,
the time it takes to process each entry depends on the size of your blueprint and stored data.

If the jobs run into your job time limit too often, you can reduce the chunk size.
Conversely, if your jobs take a short time to finish,
you can increase the chunk size to reduce the amount of jobs added to the queue.

The default chunk size is 500:
```php
'chunk_size' => 500,
```

# Extending the buster

If there is additional logic needed for your website that is not covered by the configuration,
you can also extend the buster to implement any specific use cases that should be covered.

The buster is meant to provide a broader basis to work from,
while being easy to adapt to any specific needs.
