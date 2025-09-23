<?php

namespace VanOns\StatamicStaticCacheBuster\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;
use Statamic\Assets\Asset;
use Statamic\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use VanOns\StatamicStaticCacheBuster\StaticCaching\Buster;

class BustEntryStaticCache implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        private Collection $entries,
        private Asset|Entry|LocalizedTerm $value,
    ) {
        $queue = config('statamic/static-cache-buster.queue');
        if ($queue) {
            $this->onQueue($queue);
        }
    }

    public function handle(Buster $buster): void
    {
        foreach ($this->entries as $entry) {
            $buster->bustEntry($entry, $this->value);
        }
    }
}
