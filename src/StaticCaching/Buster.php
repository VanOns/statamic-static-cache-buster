<?php

namespace VanOns\StatamicStaticCacheBuster\StaticCaching;

use Illuminate\Contracts\Support\Arrayable;
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
use Statamic\StaticCaching\DefaultInvalidator;
use Statamic\Structures\Nav;
use Statamic\Structures\Page;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Taxonomies\TermCollection;

class Buster extends DefaultInvalidator
{
    // region Invalidation methods
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

        EntryFacade::all()->each(function (Entry $entry) use ($asset) {
            if ($this->valueInFieldSet($asset, $entry, $entry->blueprint()->fields()->all())) {
                $this->cacher->invalidateUrl($entry->absoluteUrl());
            }
        });
    }

    /**
     * @param Entry $entry
     */
    protected function invalidateEntryUrls($entry): void
    {
        parent::invalidateEntryUrls($entry);

        EntryFacade::all()->each(function (Entry $entryToCheck) use ($entry) {
            if ($this->valueInFieldSet($entry, $entryToCheck, $entryToCheck->blueprint()->fields()->all())) {
                $this->cacher->invalidateUrl($entryToCheck->absoluteUrl());
            }
        });
    }

    /**
     * @param LocalizedTerm $term
     */
    protected function invalidateTermUrls($term): void
    {
        parent::invalidateTermUrls($term);

        EntryFacade::all()->each(function (Entry $entryToCheck) use ($term) {
            if ($this->valueInFieldSet($term, $entryToCheck, $entryToCheck->blueprint()->fields()->all())) {
                $this->cacher->invalidateUrl($entryToCheck->absoluteUrl());
            }
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
    private function valueInFieldSet(
        Asset|Entry|LocalizedTerm $value,
        Arrayable|array           $data,
        Arrayable|array           $fieldset,
        string                    $prefix = '',
    ): bool
    {
        foreach ($fieldset as $field) {
            if ($this->valueInField($value, $data, $field, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function valueInField(
        Asset|Entry|LocalizedTerm $value,
        Arrayable|array           $data,
        Arrayable|array           $field,
        string                    $prefix = '',
    ): bool
    {
        if (is_array($field) && array_key_exists('import', $field)) {
            $fieldset = Fieldset::find($field['import']);
            if (array_key_exists('prefix', $field)) {
                $prefix .= $field['prefix'];
            }

            if ($this->valueInFieldSet($value, $data, $fieldset->fields()->all(), $prefix)) {
                return true;
            }
            return false;
        }

        if ($field instanceof Field) {
            $type = $field->type();
            $handle = $prefix . $field->handle();
        } else {
            if (!array_key_exists('field', $field)
                || !is_array($field['field'])
                || !array_key_exists('type', $field['field'])
                || !array_key_exists('handle', $field)
            ) {
                return false;
            }
            $type = $field['field']['type'];
            $handle = $prefix . $field['handle'];
        }

        if (
            (
                ($value instanceof Asset && $type === 'assets')
                || ($value instanceof Entry && $type === 'entries')
                || ($value instanceof LocalizedTerm && $type === 'terms')
            )
            && $this->valueMatchesField($value, $data[$handle])
        ) {
            return true;
        }

        if ($type === 'replicator') {
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

    private function valueMatchesField(
        Asset|Entry|LocalizedTerm                                                          $value,
        Asset|AssetCollection|Page|Entry|EntryCollection|LocalizedTerm|TermCollection|null $fieldValue
    ): bool
    {
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
        array  $sets,
    ): array
    {
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
        if (!$site) {
            foreach (SiteFacade::all() as $site) {
                $this->cacher->invalidateUrl($site->absoluteUrl() . '/*');
            }
        } else {
            $this->cacher->invalidateUrl($site->absoluteUrl() . '/*');
        }
    }
    // endregion Helper methods
}
