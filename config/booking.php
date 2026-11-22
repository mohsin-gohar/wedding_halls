<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reservation Lock Store
    |--------------------------------------------------------------------------
    |
    | Hall reservation mutexes must be visible to every application instance.
    | The default is the shared "database" store (backed by `cache_locks`), so a
    | multi-server deployment can never double-book the same hall/date from two
    | independent local caches. Set BOOKING_LOCK_STORE=redis only when all app
    | servers share the same Redis instance.
    |
    */

    'lock_store' => env('BOOKING_LOCK_STORE', env('CACHE_STORE', 'database')),

];
