<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Additional entry paths
    |--------------------------------------------------------------------------
    |
    | Here you can define any additional paths that should be invalidated when an entry needs to be invalidated.
    | The paths configured are suffixed to the entry URL.
    |
    */

    'additional_entry_paths' => [],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Figuring out which entries are using the changed resource is resource-intensive.
    | To prevent the server from getting stuck, this process is split up into jobs along the queue.
    | You can choose which queue is used here. If set to null, the default queue is used.
    |
    */

    'queue' => null,

    /*
    |--------------------------------------------------------------------------
    | Chunk size
    |--------------------------------------------------------------------------
    |
    | All entries are checked when a resource is changed.
    | To prevent the jobs from taking too long, the entries are chunked.
    | You can adjust the chunk size here, the default is 500.
    |
    */

    'chunk_size' => 500,
];
