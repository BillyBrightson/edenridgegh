<?php
/**
 * Eden Ridge — application configuration.
 * Copy to config.php (chmod 0600) or let /install generate it.
 * This file lives OUTSIDE both document roots.
 */
return [
    // Absolute URLs, no trailing slash.
    'site_url'   => 'https://edenridgegh.com',
    'admin_url'  => 'https://app.edenridgegh.com',

    'env'        => 'production',   // production | development
    'debug'      => false,

    // 32+ random bytes, used for CSRF/session/token hashing pepper.
    'app_key'    => 'change-me',

    'db_path'    => __DIR__ . '/storage/db/edenridge.sqlite',

    // Absolute paths to the two document roots. Only needed when they are not
    // the in-repo public/ and admin/public/ folders — which is the case on
    // hosts that serve each domain from its own directory (DreamHost et al).
    // Image derivatives are written into <public_path>/media.
    'public_path'        => __DIR__ . '/public',
    'admin_public_path'  => __DIR__ . '/admin/public',

    'mail' => [
        'transport'  => 'smtp',     // smtp | log
        'host'       => 'localhost',
        'port'       => 587,
        'encryption' => 'tls',      // tls | ssl | none
        'username'   => '',
        'password'   => '',
        'from_email' => 'no-reply@edenridgegh.com',
        'from_name'  => 'Eden Ridge',
    ],
];
