<?php

return [
    // Middleware to be used for FilePond routes.
    'middleware' => [
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        \Arbify\Http\Middleware\LogFilepondRequests::class,
    ],
    
    // Route prefix for FilePond API
    'route_prefix' => 'filepond',
    
    // Directory where FilePond uploaded files will be temporarily stored.
    'temporary_files_path' => 'filepond',
    'temporary_files_disk' => 'local',
    
    // Chunks directory
    'chunks_path' => 'filepond' . DIRECTORY_SEPARATOR . 'chunks',
    
    // Input field name
    'input_name' => 'files',
];
