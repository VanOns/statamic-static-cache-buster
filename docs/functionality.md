# Functionality

## Introduction

### Statamic's default behaviour

Statamic's static caching will save a static cache of URLs when they are first visited.
This static cache bakes the content directly into the view,
to display for all next visits to the same URL.

This poses a problem when the content is changed, since then the view is no longer accurate.
To solve this, the static cache should be invalidated so Statamic
knows it should create a new static cache with the next visit.

The basic functionality of Statamic will only invalidate the URL directly
associated with whatever was changed, and any children (if applicable).
While this works fine with simple websites,
this is not sufficient when things like globals and entries are
rendered in other places (such as an entry listing).

Statamic allows you to configure rules to invalidate certain URLs when certain things are changed.
But these are limited to fixed URLs, while most websites will contain many dynamic URLs.

Thus, the default implementation of Statamic is often not enough,
and you will need to write a custom invalidation class.
This is where Statamic Static Cache Buster comes in to do the hard work for you!

## Statamic Static Cache Buster

The Statamic Static Cache Buster checks if any entries are using the thing that was changed.
If this is the case, the URL of that entry will also be invalidated.

Here is an overview of how this is implemented:

| Thing changed   | What is invalidated                                                                           |
|-----------------|-----------------------------------------------------------------------------------------------|
| Asset           | _Used in global set:_ All URLs within global's site <br/> _Used in entry:_ just the entry URL |
| Entry           | All entries using the entry                                                                   |
| Taxonomy Term   | All entries using the taxonomy                                                                |
| Navigation item | All URLs within site                                                                          |
| Global set      | All URLs within site                                                                          |

This is the invalidation logic the buster adds
to the basic Statamic Static Cache Invalidation logic,
which is still fully implemented meaning you can still
configure and use the fixed URL invalidation rules as normal.

The buster also has some [configuration] to adjust the logic to your needs.

[configuration]: configuration.md
