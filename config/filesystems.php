<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
         |----------------------------------------------------------------
         | Messenger attachments — private, and unreachable by URL
         |----------------------------------------------------------------
         |
         | A dedicated disk rather than `local`, for two reasons:
         |
         | 1. `local` sets `serve => true`, which registers the framework's
         |    `GET storage/{path}` route (`storage.local`). That route gates a
         |    private disk behind a SIGNED URL — which is bearer-style
         |    authorisation: whoever holds the link gets the file. Chat files
         |    must be authorised per REQUEST against conversation membership,
         |    which a signature cannot express.
         |
         | 2. No `url` key and `serve => false` means no route is registered
         |    for this disk at all, and `Storage::disk('chat')->url()` throws
         |    instead of quietly returning a working public link. The failure
         |    mode for "someone renders the path into a Blade view" is now an
         |    exception in development rather than a silent leak in
         |    production — see the note on MessageAttachment.
         |
         | Files are served only by ConversationController::previewAttachment
         | / downloadAttachment, both of which resolve the row through
         | `visibleTo($user)`.
         */
        'chat' => [
            'driver' => 'local',
            'root' => storage_path('app/chat'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'custom' => [
            'driver' => 'local',
            // Files live under public/ (web document root for `artisan serve` / typical vhosts).
            'root' => public_path(''),
            // Do NOT append "/public" — that produces 404s like /public/user/avatar/*.jpg
            // when the server already serves from the public directory.
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/'),
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('media') => storage_path('app/custom'),
    ],

];
