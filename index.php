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

$sdir = '/data/spep/spepmedia.com/';

require_once __DIR__ . '/Parsedown.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/getid3/getid3.php';

$getID3 = new getID3();

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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= htmlspecialchars($pageTitle) ?> Sermon Archive</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css" />

    <!-- jQuery and Bootstrap JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="/style.css" />

    <!-- Google Analytics -->
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
        ga('create', 'UA-47721127-4', 'auto');
        ga('send', 'pageview');
    </script>
</head>
<body>
    <div class="content-fluid">
        <!-- Header -->
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h1><?= htmlspecialchars($pageTitle) ?>SPEP Sermon Archive</h1>
            </div>
        </div>

        <!-- Breadcrumb Navigation -->
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h4><?= $breadcrumbHtml ?></h4>
            </div>
        </div>

        <!-- Readme Section -->
        <?php if ($hasReadme): ?>
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="well well-sm">
                    <?= $readmeHtml ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Featured Section -->
        <?php if ($featuredItems !== []): ?>
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h3>Featured</h3>
                <?php foreach ($featuredItems as $feature): ?>
                <div class="cover">
                    <a href="<?= $feature['link'] ?>">
                        <img src="<?= $feature['image'] ?>" width="100%" height="100%" alt="<?= $feature['title'] ?>" />
                        <span class="info title"><?= $feature['title'] ?></span>
                        <span class="info pastor"><?= $feature['pastor'] ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- File/Directory Listing -->
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <table class="table table-striped">
                    <thead>
                        <?php if ($allDirs): ?>
                        <tr>
                            <th class="col-md-1"></th>
                            <th class="col-md-5">Title</th>
                            <th class="col-md-1"></th>
                            <th class="col-md-5">Title</th>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <th></th>
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
                                <td><span class="glyphicon glyphicon-folder-close"></span></td>
                                <td><a href="<?= $row['link'] ?>"><?= $row['name'] ?></a></td>
                                <?php if ($allDirs): ?>
                                    <?php if ($row['second_name'] !== null): ?>
                                    <td><span class="glyphicon glyphicon-folder-close"></span></td>
                                    <td><a href="<?= $row['second_link'] ?>"><?= $row['second_name'] ?></a></td>
                                    <?php else: ?>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <?php endif; ?>
                                <?php else: ?>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <?php endif; ?>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td><span class="glyphicon glyphicon-play"></span></td>
                                <td><a href="<?= $row['link'] ?>"><?= $row['title'] ?></a></td>
                                <td><?= $row['comment'] ?></td>
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
    <div class="footer">
        <div class="container">
            <p class="text-muted">
                <a href="http://spepchurch.org">Severna Park EP Church (PCA)</a> ::
                Hosted on <a href="https://www.digitalocean.com/?refcode=c0167ae9a50a">DigitalOcean</a> ::
                <a href="http://validator.w3.org/check?uri=archive.spepmedia.com<?= cleanURL($path) ?>">Valid XHTML</a>
            </p>
        </div>
    </div>
</body>
</html>
