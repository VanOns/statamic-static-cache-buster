<?php

namespace VanOns\StatamicStaticCacheBuster\StaticCaching;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Statamic\Assets\Asset;
use Statamic\Assets\AssetCollection;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Fieldset;
use Statamic\Facades\GlobalSet as GlobalSetFacade;
use Statamic\Facades\Site as SiteFacade;
use Statamic\Fields\Field;
use Statamic\Globals\GlobalSet;
use Statamic\Globals\Variables;
use Statamic\Sites\Site;
use Statamic\StaticCaching\Cacher;
use Statamic\StaticCaching\DefaultInvalidator;
use Statamic\Structures\Nav;
use Statamic\Structures\Page;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Taxonomies\TermCollection;
use VanOns\StatamicStaticCacheBuster\Jobs\BustEntryStaticCache;

class Buster extends DefaultInvalidator
{
    protected int $chunkSize = 500;
    protected ?string $queue;

    public function __construct(Cacher $cacher, $rules = [])
    {
        parent::__construct($cacher, $rules);

        $this->chunkSize = config('statamic/static-cache-buster.chunk_size', 500);
        $this->queue = config('statamic/static-cache-buster.queue');
    }

    // region Invalidation methods
    public function bustEntry(
        Entry $entry,
        Asset|Entry|LocalizedTerm $value,
    ): void {
        if ($this->valueInFieldSet($value, $entry, $entry->blueprint()->fields()->all())) {
            $this->invalidateEntry($entry);
        }
    }

    /**
     * @param Asset $asset
     */
    protected function invalidateAssetUrls($asset): void
    {
        parent::invalidateAssetUrls($asset);

        GlobalSetFacade::all()->each(function (GlobalSet $globalSet) use ($asset) {
            /** @var Field $field */
            foreach (SiteFacade::all() as $site) {
                /** @var Variables $variables */
                $variables = $globalSet->in($site->handle());

                if ($this->valueInFieldSet($asset, $variables, $variables->blueprint()->fields()->all())) {
                    $this->invalidateAllUrls($site);
                    break;
                }
            }
        });

        EntryFacade::query()->chunk($this->chunkSize, function (Collection $entries) use ($asset) {
            BustEntryStaticCache::dispatch($entries, $asset)->onQueue($this->queue);
        });
    }

    /**
     * @param Entry $entry
     */
    protected function invalidateEntryUrls($entry): void
    {
        parent::invalidateEntryUrls($entry);

        EntryFacade::query()->chunk($this->chunkSize, function (Collection $entries) use ($entry) {
            BustEntryStaticCache::dispatch($entries, $entry)->onQueue($this->queue);
        });
    }

    /**
     * @param LocalizedTerm $term
     */
    protected function invalidateTermUrls($term): void
    {
        parent::invalidateTermUrls($term);

        EntryFacade::query()->chunk($this->chunkSize, function (Collection $entries) use ($term) {
            BustEntryStaticCache::dispatch($entries, $term)->onQueue($this->queue);
        });
    }

    /**
     * @param Nav $nav
     */
    protected function invalidateNavUrls($nav): void
    {
        $this->invalidateAllUrls();
    }

    /**
     * @param Variables $variables
     */
    protected function invalidateGlobalUrls($variables): void
    {
        $this->invalidateAllUrls();
    }
    // endregion Invalidation methods

    // region Helper methods
    private function invalidateEntry(Entry $entry): void
    {
        $this->cacher->invalidateUrl($entry->absoluteUrl());

        foreach (config('statamic/static-cache-buster.additional_entry_paths.' . $entry->collectionHandle(), []) as $path) {
            $this->cacher->invalidateUrl($entry->absoluteUrl() . $path);
        }
    }

    private function valueInFieldSet(
        Asset|Entry|LocalizedTerm $value,
        Arrayable|array $data,
        Arrayable|array $fieldset,
        string $prefix = '',
    ): bool {
        foreach ($fieldset as $field) {
            if ($this->valueInField($value, $data, $field, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function valueInField(
        Asset|Entry|LocalizedTerm $value,
        Arrayable|array $data,
        Arrayable|array $field,
        string $prefix = '',
    ): bool {
        if (is_array($field) && array_key_exists('import', $field)) {
            return $this->valueInImportedFieldSet($value, $data, $field, $prefix);
        }

        if ($field instanceof Field) {
            $fieldType = $field->type();
            $handle = $prefix . $field->handle();
        } else {
            if (!array_key_exists('field', $field)
                || !is_array($field['field'])
                || !array_key_exists('type', $field['field'])
                || !array_key_exists('handle', $field)
            ) {
                return false;
            }
            $fieldType = $field['field']['type'];
            $handle = $prefix . $field['handle'];
        }

        if (
            $this->valueMatchesFieldType($value, $fieldType)
            && $this->valueMatchesField($value, $data[$handle])
        ) {
            return true;
        }

        if ($fieldType === 'replicator') {
            if ($field instanceof Field) {
                $config = $field->config();
            } else {
                $config = $field['field'];
            }

            foreach ($data[$handle] as $replicatorItem) {
                $fieldSet = $this->getReplicatorItemTypeFieldSet($replicatorItem['type'], $config['sets']);
                if ($this->valueInFieldSet($value, $replicatorItem, $fieldSet)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function valueInImportedFieldSet(
        Asset|Entry|LocalizedTerm $value,
        Arrayable|array $data,
        Arrayable|array $field,
        string $prefix = '',
    ): bool {
        $fieldset = Fieldset::find($field['import']);
        if (array_key_exists('prefix', $field)) {
            $prefix .= $field['prefix'];
        }

        if ($this->valueInFieldSet($value, $data, $fieldset->fields()->all(), $prefix)) {
            return true;
        }
        return false;
    }

    private function valueMatchesFieldType(
        Asset|Entry|LocalizedTerm $value,
        string $fieldType,
    ): bool {
        return ($value instanceof Asset && $fieldType === 'assets')
            || ($value instanceof Entry && $fieldType === 'entries')
            || ($value instanceof LocalizedTerm && $fieldType === 'terms');
    }

    private function valueMatchesField(
        Asset|Entry|LocalizedTerm $value,
        Asset|AssetCollection|Page|Entry|EntryCollection|LocalizedTerm|TermCollection|null $fieldValue
    ): bool {
        if (
            ($value instanceof Asset && $fieldValue instanceof Asset && $fieldValue->id === $value->id)
            || ($value instanceof Entry && ($fieldValue instanceof Entry || $fieldValue instanceof Page) && $fieldValue->id === $value->id)
            || ($value instanceof LocalizedTerm && $fieldValue instanceof LocalizedTerm && $value->id === $fieldValue->id)
        ) {
            return true;
        }

        if (
            ($value instanceof Asset && $fieldValue instanceof AssetCollection)
            || ($value instanceof Entry && $fieldValue instanceof EntryCollection)
            || ($value instanceof LocalizedTerm && $fieldValue instanceof TermCollection)
        ) {
            foreach ($fieldValue as $item) {
                if ($item->id === $value->id) {
                    return true;
                }
            }
        }
        return false;
    }

    private function getReplicatorItemTypeFieldSet(
        string $replicatorItemType,
        array $sets,
    ): array {
        foreach ($sets as $handle => $set) {
            if (array_key_exists('sets', $set)) {
                return $this->getReplicatorItemTypeFieldSet($replicatorItemType, $set['sets']);
            }
            if ($handle === $replicatorItemType && array_key_exists('fields', $set)) {
                return $set['fields'];
            }
        }
        return [];
    }

    private function invalidateAllUrls(Site $site = null): void
    {
        $sites = $site ? [$site] : SiteFacade::all();

        foreach ($sites as $site) {
            $this->cacher->invalidateUrl($site->absoluteUrl() . '/*');
        }
    }
    // endregion Helper methods
}
