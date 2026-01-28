<?php
/**
 * Sermon Archive - Main Index
 *
 * This file displays a browsable archive of sermons and media files.
 */

// ============================================================================
// CONFIGURATION
// ============================================================================

$sdir = "/data/spep/spepmedia.com/";

// Uncomment for debugging:
// ini_set('display_errors', 1);
// error_reporting(~0);

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Clean and encode a URL path for safe linking.
 */
function cleanURL($url) {
    if ($url == "") {
        return "";
    }
    $array = explode('/', $url);
    $newArray = array();
    foreach ($array as $value) {
        if ($value == "") {
            continue;
        }
        $newArray[] = rawurlencode($value);
    }
    return "/" . implode('/', $newArray);
}

/**
 * Check if a string ends with a given suffix.
 * Courtesy of StackOverflow: http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
 */
function endsWith($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== FALSE;
}

/**
 * Check if a string starts with a given prefix.
 */
function startsWith($haystack, $needle) {
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

/**
 * Check if an item should be skipped in directory listings.
 */
function shouldSkipItem($item) {
    return $item == "."
        || $item == ".."
        || $item == "readme.md"
        || $item == "featured.csv"
        || endsWith($item, ".jpg")
        || startsWith($item, ".")
        || $item == "robots.txt";
}

/**
 * Get ID3 tag value safely from nested array structure.
 */
function getID3Tag($info, $version, $tag) {
    if ($info !== null
        && isset($info['tags'][$version][$tag])
        && array_key_exists(0, $info['tags'][$version][$tag])) {
        return $info['tags'][$version][$tag][0];
    }
    return null;
}

/**
 * Get ID3 tag value, preferring id3v2 over id3v1.
 */
function getID3TagPreferV2($info, $tag) {
    $value = getID3Tag($info, 'id3v2', $tag);
    if ($value === null) {
        $value = getID3Tag($info, 'id3v1', $tag);
    }
    return $value;
}

// ============================================================================
// INITIALIZATION
// ============================================================================

require_once('getid3/getid3.php');
$getID3 = new getID3;

// ============================================================================
// DATA PREPARATION
// ============================================================================

// Parse the request path
$path = urldecode($_SERVER['REQUEST_URI']);
if ($path == "/") {
    $path = "";
}

// Build page title from path
$pageTitle = "";
$tokens = explode('/', $path);
if (count($tokens) > 1) {
    $pageTitle = $tokens[count($tokens) - 1] . " - ";
}

// Debug info for HTML comment
$debugTokens = $tokens;
$debugTokenCount = count($tokens);
$debugLastToken = $tokens[count($tokens) - 1];

// Build breadcrumb trail
$breadcrumbs = array();
$breadcrumbs[] = '<a href="/">Home</a>';
$chunks = explode('/', $path);
foreach ($chunks as $i => $chunk) {
    if ($chunk == "") {
        continue;
    }
    $breadcrumbs[] = sprintf(
        '<a href="%s">%s</a>',
        implode('', array_slice(array_map('cleanURL', $chunks), 0, $i + 1)),
        $chunk
    );
}
$breadcrumbHtml = implode(' &gt;&gt; ', $breadcrumbs);

// Scan the directory
$items = scandir($sdir . $path);

// Analyze directory contents
$allDirs = true;
$hasReadme = false;
$hasFeatured = false;

if (count($items) < 6) {
    $allDirs = false;
}

foreach ($items as $item) {
    if ($item == "readme.md") {
        $hasReadme = true;
    } else if ($item == "featured.csv") {
        $hasFeatured = true;
    } else if ($item == "robots.txt") {
        continue;
    } else if (!startsWith($item, ".") && !is_dir($sdir . $path . "/" . $item) && $allDirs) {
        $allDirs = false;
    }
}

// Prepare readme content if present
$readmeHtml = "";
if ($hasReadme) {
    $readmeHtml = shell_exec("markdown " . $sdir . $path . "/readme.md");
}

// Prepare featured items if on home page
$featuredItems = array();
if ($path == "" && $hasFeatured) {
    $featuredData = array_map('str_getcsv', file($sdir . "/featured.csv"));
    foreach ($featuredData as $feature) {
        if ($feature[0] == "title" || $feature[0] == "") {
            continue;
        }
        $featuredItems[] = array(
            'title' => htmlspecialchars($feature[0]),
            'link' => cleanURL($feature[1]),
            'image' => cleanURL($feature[2]),
            'pastor' => htmlspecialchars($feature[3])
        );
    }
}

// Build the file/directory listing
$listingRows = array();
$increment = $allDirs ? 2 : 1;

for ($i = 0; $i < count($items); $i += $increment) {
    // Skip items that need skipping
    while ($i < count($items) && shouldSkipItem($items[$i])) {
        $i++;
    }

    if ($i >= count($items)) {
        break;
    }

    $row = array();
    $currentItem = $items[$i];
    $isDirectory = is_dir($sdir . $path . "/" . $currentItem);

    if ($isDirectory) {
        // Directory entry
        $row['type'] = 'directory';
        $row['link'] = cleanURL($path) . cleanURL($currentItem);
        $row['name'] = $currentItem;

        if ($allDirs) {
            // Find the next valid item for second column
            $nextIndex = $i + 1;
            while ($nextIndex < count($items) && shouldSkipItem($items[$nextIndex])) {
                $nextIndex++;
            }

            if ($nextIndex < count($items)) {
                $row['second_link'] = cleanURL($path) . cleanURL($items[$nextIndex]);
                $row['second_name'] = $items[$nextIndex];
                // Adjust i to skip items we've processed
                $i = $nextIndex - 1; // -1 because loop will add $increment
            } else {
                $row['second_link'] = null;
                $row['second_name'] = null;
            }
        }
    } else {
        // File entry
        $row['type'] = 'file';
        $file = $sdir . $path . "/" . $currentItem;

        $info = null;
        if (pathinfo($file, PATHINFO_EXTENSION) == "mp3") {
            $info = $getID3->analyze($file);
        }

        $title = getID3TagPreferV2($info, 'title');
        if ($title === null) {
            $title = $currentItem;
        }

        $row['link'] = "/sermons" . cleanURL($path) . cleanURL($currentItem);
        $row['title'] = htmlspecialchars($title);
        $row['comment'] = htmlspecialchars(getID3TagPreferV2($info, 'comment') ?? '');
        $row['artist'] = htmlspecialchars(getID3TagPreferV2($info, 'artist') ?? '');
    }

    $listingRows[] = $row;
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
    <!-- <?php var_dump($debugTokens); echo $debugTokenCount . $debugLastToken . " - "; ?> -->
    <title><?php echo $pageTitle; ?> Sermon Archive</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css" />

    <!-- jQuery and Bootstrap JS -->
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="/style.css" />

    <!-- Google Analytics -->
    <script type="text/javascript">
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
                <h1><?php echo $pageTitle; ?>SPEP Sermon Archive</h1>
            </div>
        </div>

        <!-- Breadcrumb Navigation -->
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h4><?php echo $breadcrumbHtml; ?></h4>
            </div>
        </div>

        <!-- Readme Section -->
        <?php if ($hasReadme): ?>
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="well well-sm">
                    <?php echo $readmeHtml; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Featured Section -->
        <?php if (!empty($featuredItems)): ?>
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h3>Featured</h3>
                <?php foreach ($featuredItems as $feature): ?>
                <div class="cover">
                    <a href="<?php echo $feature['link']; ?>">
                        <img src="<?php echo $feature['image']; ?>" width="100%" height="100%" alt="<?php echo $feature['title']; ?>" />
                        <span class="info title"><?php echo $feature['title']; ?></span>
                        <span class="info pastor"><?php echo $feature['pastor']; ?></span>
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
                            <?php if ($row['type'] == 'directory'): ?>
                            <tr>
                                <td><span class="glyphicon glyphicon-folder-close"></span></td>
                                <td><a href="<?php echo $row['link']; ?>"><?php echo $row['name']; ?></a></td>
                                <?php if ($allDirs): ?>
                                    <?php if ($row['second_name'] !== null): ?>
                                    <td><span class="glyphicon glyphicon-folder-close"></span></td>
                                    <td><a href="<?php echo $row['second_link']; ?>"><?php echo $row['second_name']; ?></a></td>
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
                                <td><a href="<?php echo $row['link']; ?>"><?php echo $row['title']; ?></a></td>
                                <td><?php echo $row['comment']; ?></td>
                                <td><?php echo $row['artist']; ?></td>
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
                <a href="http://validator.w3.org/check?uri=archive.spepmedia.com<?php echo cleanURL($path); ?>">Valid XHTML</a>
            </p>
        </div>
    </div>

<!--Debug info goes here
<?php
echo $path;
echo "\n";
echo cleanURL($path);
echo "\n";
?>
/debug-->
</body>
</html>
