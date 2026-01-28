<?php

declare(strict_types=1);

/**
 * Sermon Archive - Main Index
 *
 * Displays a browsable archive of sermons and media files.
 */

// ============================================================================
// CONFIGURATION & DEPENDENCIES
// ============================================================================

// Load configuration
global $config;
$config = require __DIR__ . '/config.php';

require_once __DIR__ . '/Parsedown.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/getid3/getid3.php';

$getID3 = new getID3();

// Extract commonly used config values
$sdir = config('data_directory');
$siteName = config('site_name', 'Sermon Archive');
$orgName = config('organization_name');
$orgUrl = config('organization_url');
$gaId = config('google_analytics_id');
$hostingProvider = config('hosting_provider');
$validatorDomain = config('validator_domain');

// ============================================================================
// DATA PREPARATION
// ============================================================================

// Parse the request path
$path = urldecode($_SERVER['REQUEST_URI']);
if ($path === '/') {
    $path = '';
}

// Build page title and breadcrumbs
$pageTitle = getPageTitleFromPath($path);
$breadcrumbHtml = buildBreadcrumbs($path);

// Scan and analyze directory contents
$items = scandir($sdir . $path);
$dirInfo = analyzeDirectory($sdir, $path, $items);

$allDirs = $dirInfo['allDirs'];
$hasReadme = $dirInfo['hasReadme'];
$hasFeatured = $dirInfo['hasFeatured'];

// Prepare readme content if present
$readmeHtml = '';
if ($hasReadme) {
    $readmeHtml = renderMarkdownFile($sdir . $path . '/readme.md');
}

// Prepare featured items if on home page
$featuredItems = [];
if ($path === '' && $hasFeatured) {
    $featuredItems = parseFeaturedItems($sdir . '/featured.csv');
}

// Build the file/directory listing
$listingRows = [];
$itemCount = count($items);

for ($i = 0; $i < $itemCount; $i++) {
    // Skip items that need skipping
    while ($i < $itemCount && shouldSkipItem($items[$i])) {
        $i++;
    }

    if ($i >= $itemCount) {
        break;
    }

    $currentItem = $items[$i];
    $fullPath = $sdir . $path . '/' . $currentItem;

    if (is_dir($fullPath)) {
        $listingRows[] = buildDirectoryRow($path, $currentItem, $allDirs, $items, $i);
    } else {
        $id3Info = null;
        if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'mp3') {
            $id3Info = $getID3->analyze($fullPath);
        }
        $listingRows[] = buildFileRow($path, $currentItem, $id3Info);
    }

    // In allDirs mode, we process two items per iteration
    if ($allDirs) {
        $i++;
    }
}

// ============================================================================
// HTML TEMPLATE
// ============================================================================
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= htmlspecialchars($pageTitle . $siteName) ?></title>

    <!-- Color scheme detection (runs before CSS to prevent flash) -->
    <script>
        (function() {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const stored = localStorage.getItem('theme');
            const theme = stored || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom styles -->
    <link rel="stylesheet" href="/style.css">

    <?php if ($gaId): ?>
    <!-- Google Analytics -->
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
        ga('create', '<?= htmlspecialchars($gaId) ?>', 'auto');
        ga('send', 'pageview');
    </script>
    <?php endif; ?>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <h1 class="mb-0"><?= htmlspecialchars($pageTitle . $siteName) ?></h1>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="theme-toggle" aria-label="Toggle dark mode">
                        <i class="bi bi-moon-fill" id="theme-icon-dark"></i>
                        <i class="bi bi-sun-fill d-none" id="theme-icon-light"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Breadcrumb Navigation -->
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <nav aria-label="breadcrumb">
                    <div class="fs-5 text-secondary"><?= $breadcrumbHtml ?></div>
                </nav>
            </div>
        </div>

        <!-- Readme Section -->
        <?php if ($hasReadme): ?>
        <div class="row mt-3">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-body">
                        <?= $readmeHtml ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Featured Section -->
        <?php if ($featuredItems !== []): ?>
        <div class="row mt-4">
            <div class="col-md-8 offset-md-2">
                <h3>Featured</h3>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($featuredItems as $feature): ?>
                    <div class="col">
                        <a href="<?= $feature['link'] ?>" class="text-decoration-none">
                            <div class="card h-100 cover">
                                <img src="<?= $feature['image'] ?>" class="card-img-top" alt="<?= $feature['title'] ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $feature['title'] ?></h5>
                                    <p class="card-text text-muted"><?= $feature['pastor'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- File/Directory Listing -->
        <div class="row mt-4">
            <div class="col-md-8 offset-md-2">
                <table class="table table-striped table-hover">
                    <thead>
                        <?php if ($allDirs): ?>
                        <tr>
                            <th style="width: 5%"></th>
                            <th style="width: 45%">Title</th>
                            <th style="width: 5%"></th>
                            <th style="width: 45%">Title</th>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <th style="width: 5%"></th>
                            <th>Title</th>
                            <th>Comments</th>
                            <th>Pastor/Artist</th>
                        </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($listingRows as $row): ?>
                            <?php if ($row['type'] === 'directory'): ?>
                            <tr>
                                <td><i class="bi bi-folder-fill text-warning"></i></td>
                                <td><a href="<?= $row['link'] ?>" class="text-decoration-none"><?= $row['name'] ?></a></td>
                                <?php if ($allDirs): ?>
                                    <?php if ($row['second_name'] !== null): ?>
                                    <td><i class="bi bi-folder-fill text-warning"></i></td>
                                    <td><a href="<?= $row['second_link'] ?>" class="text-decoration-none"><?= $row['second_name'] ?></a></td>
                                    <?php else: ?>
                                    <td></td>
                                    <td></td>
                                    <?php endif; ?>
                                <?php else: ?>
                                <td></td>
                                <td></td>
                                <?php endif; ?>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td><i class="bi bi-play-circle-fill text-primary"></i></td>
                                <td><a href="<?= $row['link'] ?>" class="text-decoration-none"><?= $row['title'] ?></a></td>
                                <td class="text-muted"><?= $row['comment'] ?></td>
                                <td><?= $row['artist'] ?></td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5 py-3 bg-body-tertiary">
        <div class="container text-center">
            <span class="text-body-secondary">
                <?php if ($orgName && $orgUrl): ?>
                <a href="<?= htmlspecialchars($orgUrl) ?>" class="text-body-secondary text-decoration-none"><?= htmlspecialchars($orgName) ?></a>
                <?php elseif ($orgName): ?>
                <?= htmlspecialchars($orgName) ?>
                <?php endif; ?>
                <?php if ($hostingProvider): ?>
                &middot; Hosted on <a href="<?= htmlspecialchars($hostingProvider['url']) ?>" class="text-body-secondary text-decoration-none"><?= htmlspecialchars($hostingProvider['name']) ?></a>
                <?php endif; ?>
                <?php if ($validatorDomain): ?>
                &middot; <a href="https://validator.w3.org/check?uri=<?= htmlspecialchars($validatorDomain) ?><?= cleanURL($path) ?>" class="text-body-secondary text-decoration-none">Valid HTML</a>
                <?php endif; ?>
            </span>
        </div>
    </footer>

    <!-- Bootstrap 5 JS (optional, only needed for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Theme toggle script -->
    <script>
        (function() {
            const toggle = document.getElementById('theme-toggle');
            const iconDark = document.getElementById('theme-icon-dark');
            const iconLight = document.getElementById('theme-icon-light');

            function updateIcons(theme) {
                if (theme === 'dark') {
                    iconDark.classList.add('d-none');
                    iconLight.classList.remove('d-none');
                } else {
                    iconDark.classList.remove('d-none');
                    iconLight.classList.add('d-none');
                }
            }

            function setTheme(theme) {
                document.documentElement.setAttribute('data-bs-theme', theme);
                localStorage.setItem('theme', theme);
                updateIcons(theme);
            }

            // Initialize icons based on current theme
            updateIcons(document.documentElement.getAttribute('data-bs-theme'));

            // Toggle button click handler
            toggle.addEventListener('click', function() {
                const current = document.documentElement.getAttribute('data-bs-theme');
                setTheme(current === 'dark' ? 'light' : 'dark');
            });

            // Listen for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!localStorage.getItem('theme')) {
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });
        })();
    </script>
</body>
</html>
