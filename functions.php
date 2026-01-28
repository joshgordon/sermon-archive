<?php

declare(strict_types=1);

/**
 * Sermon Archive - Utility Functions
 *
 * Modern PHP 8+ utility functions for the sermon archive.
 */

/**
 * Clean and encode a URL path for safe linking.
 */
function cleanURL(string $url): string
{
    if ($url === '') {
        return '';
    }

    $segments = array_filter(
        explode('/', $url),
        fn(string $segment): bool => $segment !== ''
    );

    $encoded = array_map('rawurlencode', $segments);

    return '/' . implode('/', $encoded);
}

/**
 * Check if an item should be skipped in directory listings.
 */
function shouldSkipItem(string $item): bool
{
    // Skip hidden files and special entries
    if (str_starts_with($item, '.')) {
        return true;
    }

    // Skip known special files
    $skipFiles = ['readme.md', 'featured.csv', 'robots.txt'];
    if (in_array($item, $skipFiles, strict: true)) {
        return true;
    }

    // Skip image files
    if (str_ends_with($item, '.jpg')) {
        return true;
    }

    return false;
}

/**
 * Get ID3 tag value safely from nested array structure.
 */
function getID3Tag(?array $info, string $version, string $tag): ?string
{
    return $info['tags'][$version][$tag][0] ?? null;
}

/**
 * Get ID3 tag value, preferring id3v2 over id3v1.
 */
function getID3TagPreferV2(?array $info, string $tag): ?string
{
    return getID3Tag($info, 'id3v2', $tag)
        ?? getID3Tag($info, 'id3v1', $tag);
}

/**
 * Build breadcrumb navigation HTML from a path.
 */
function buildBreadcrumbs(string $path): string
{
    $breadcrumbs = ['<a href="/">Home</a>'];
    $chunks = explode('/', $path);

    foreach ($chunks as $i => $chunk) {
        if ($chunk === '') {
            continue;
        }

        $href = implode('', array_slice(array_map('cleanURL', $chunks), 0, $i + 1));
        $breadcrumbs[] = sprintf('<a href="%s">%s</a>', $href, htmlspecialchars($chunk));
    }

    return implode(' &gt;&gt; ', $breadcrumbs);
}

/**
 * Parse featured.csv and return structured array of featured items.
 */
function parseFeaturedItems(string $csvPath): array
{
    if (!file_exists($csvPath)) {
        return [];
    }

    $items = [];
    $rows = array_map('str_getcsv', file($csvPath));

    foreach ($rows as $row) {
        // Skip header row and empty rows
        if (empty($row[0]) || $row[0] === 'title') {
            continue;
        }

        $items[] = [
            'title' => htmlspecialchars($row[0]),
            'link' => cleanURL($row[1] ?? ''),
            'image' => cleanURL($row[2] ?? ''),
            'pastor' => htmlspecialchars($row[3] ?? ''),
        ];
    }

    return $items;
}

/**
 * Analyze directory contents and return metadata.
 *
 * @return array{allDirs: bool, hasReadme: bool, hasFeatured: bool}
 */
function analyzeDirectory(string $basePath, string $relativePath, array $items): array
{
    $result = [
        'allDirs' => count($items) >= 6,
        'hasReadme' => false,
        'hasFeatured' => false,
    ];

    foreach ($items as $item) {
        match ($item) {
            'readme.md' => $result['hasReadme'] = true,
            'featured.csv' => $result['hasFeatured'] = true,
            'robots.txt' => null, // Ignore
            default => $result['allDirs'] = $result['allDirs']
                && (str_starts_with($item, '.') || is_dir($basePath . $relativePath . '/' . $item)),
        };
    }

    return $result;
}

/**
 * Build a directory row for the listing table.
 */
function buildDirectoryRow(string $path, string $name, bool $allDirs, array $items, int &$index): array
{
    $row = [
        'type' => 'directory',
        'link' => cleanURL($path) . cleanURL($name),
        'name' => htmlspecialchars($name),
        'second_link' => null,
        'second_name' => null,
    ];

    if ($allDirs) {
        // Find the next valid item for second column
        $nextIndex = $index + 1;
        while ($nextIndex < count($items) && shouldSkipItem($items[$nextIndex])) {
            $nextIndex++;
        }

        if ($nextIndex < count($items)) {
            $row['second_link'] = cleanURL($path) . cleanURL($items[$nextIndex]);
            $row['second_name'] = htmlspecialchars($items[$nextIndex]);
            $index = $nextIndex;
        }
    }

    return $row;
}

/**
 * Build a file row for the listing table.
 */
function buildFileRow(string $path, string $filename, ?array $id3Info): array
{
    $title = getID3TagPreferV2($id3Info, 'title') ?? $filename;

    return [
        'type' => 'file',
        'link' => '/sermons' . cleanURL($path) . cleanURL($filename),
        'title' => htmlspecialchars($title),
        'comment' => htmlspecialchars(getID3TagPreferV2($id3Info, 'comment') ?? ''),
        'artist' => htmlspecialchars(getID3TagPreferV2($id3Info, 'artist') ?? ''),
    ];
}

/**
 * Get page title from path.
 */
function getPageTitleFromPath(string $path): string
{
    if ($path === '') {
        return '';
    }

    $tokens = explode('/', $path);
    $lastToken = end($tokens);

    return $lastToken !== '' ? $lastToken . ' - ' : '';
}

/**
 * Render markdown file to HTML using Parsedown.
 */
function renderMarkdownFile(string $filePath): string
{
    if (!file_exists($filePath)) {
        return '';
    }

    static $parsedown = null;
    if ($parsedown === null) {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
    }

    $markdown = file_get_contents($filePath);
    return $parsedown->text($markdown);
}
