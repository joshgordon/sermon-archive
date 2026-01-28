<?php

declare(strict_types=1);

/**
 * Sermon Archive - Configuration
 *
 * Copy this file to config.php and customize the values for your installation.
 */

return [
    // =========================================================================
    // Site Settings
    // =========================================================================

    // The name of your site (appears in title and header)
    'site_name' => 'SPEP Sermon Archive',

    // Your organization name (appears in footer)
    'organization_name' => 'Severna Park EP Church (PCA)',

    // Your organization's website URL
    'organization_url' => 'http://spepchurch.org',

    // =========================================================================
    // File Paths
    // =========================================================================

    // Absolute path to the directory containing your sermon files
    'data_directory' => '/data/spep/spepmedia.com/',

    // URL prefix for serving media files (used in file links)
    // This should match your web server's alias/location configuration
    'media_url_prefix' => '/sermons',

    // =========================================================================
    // Analytics & Tracking
    // =========================================================================

    // Google Analytics tracking ID (leave empty to disable)
    // Format: 'UA-XXXXXXXX-X' or 'G-XXXXXXXXXX'
    'google_analytics_id' => 'UA-47721127-4',

    // =========================================================================
    // Footer Links
    // =========================================================================

    // Hosting provider link (set to null to hide)
    'hosting_provider' => [
        'name' => 'DigitalOcean',
        'url' => 'https://www.digitalocean.com/?refcode=c0167ae9a50a',
    ],

    // Domain for W3C validator link (set to null to hide validator link)
    'validator_domain' => 'archive.spepmedia.com',

    // =========================================================================
    // Display Options
    // =========================================================================

    // Minimum number of items required to display two-column directory layout
    'two_column_min_items' => 6,

    // Files to skip in directory listings (in addition to hidden files)
    'skip_files' => ['readme.md', 'featured.csv', 'robots.txt'],

    // File extensions to skip in directory listings
    'skip_extensions' => ['.jpg'],
];
