<?php //include "config.php"; ?> 

<?php
function cleanURL($url) { 
  $array = explode('/', $url);
  $newArray = array(); 
  foreach ($array as $value) { 
    if ($value == "") { continue; } 
    $newArray[] = rawurlencode($value); 
  }
  return implode('/', $newArray); 
}
require_once('getid3/getid3.php'); 
$getID3 = new getID3; 
?> 
<?php $sdir = "/data/spep/spepmedia.com/"; ?> 
<?php
//ini_set('display_errors', 1);
//error_reporting(~0);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
<title>Sermon Archive</title>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" />

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css" />


<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

<!-- Latest compiled and minified JavaScript -->
<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

<link rel="stylesheet" href="/style.css" /> 

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
<?php

// Path in which to explore for sermons/other content. 
$path = urldecode($_SERVER['REQUEST_URI']);

//Strip leading slash if the path is "/".
if ($path == "/") {
  $path = "";
}

//Explode the path into a breadcrumb trail
$output = array(); 
$output[] = '<a href="/">Home</a>';
$chunks = explode('/', $path);
foreach ($chunks as $i => $chunk) {
    if ($chunk == "" ) { continue; } 
    //Print out the breadcrumb trail.
    $output[] = sprintf(
        '<a href="%s">%s</a>',
        implode('/', array_slice(array_map('cleanURL', $chunks), 0, $i + 1)),
        $chunk
    );
}
  
//Scan the directory for items. 
$items=scandir($sdir . $path); 


$allDirs=true; 
$readme=false; 
$featured=false; 

if (count($items) < 6) { $allDirs=false; } 
//Look to see if the path is only directories
foreach($items as $item) { 
  if ($item == "readme.md") { 
    $readme=true; 
  } else if ($item == "featured.csv") { 
    $featured = true; 
    continue; 
  }
  else if (!is_dir($sdir . $path . "/" . $item) && $allDirs) { 
    $allDirs=false; 
  }
}

?>
<div class="content-fluid">
<div class="row"> 
  <div class="col-md-8 col-md-offset-2"> 
    <h1>SPEP Sermon Archive</h1> 
  </div> 
</div>
<div class="row"> 
<div class="col-md-8 col-md-offset-2">
<h4>
<?php echo implode(' &gt;&gt; ', $output); ?> 
</h4>
</div>

</div> 

<?php if ($readme) { ?> 
<div class="row"> 
<div class="col-md-6 col-md-offset-3">
<div class="well well-sm">
<?php echo "" . shell_exec("markdown " . $sdir . $path . "/readme.md"); ?> 
</div>
</div>
</div> 
<?php } if ($path == "" && $featured) { 
$featured = array_map('str_getcsv', file($sdir . "/featured.csv")); 
?> 
<div class="row">
  <div class="col-md-8 col-md-offset-2"> 
  <h3>Featured</h3>
<?php 
foreach ($featured as $feature) { 
  if ($feature[0] == "title" || $feature[0] == "") 
    continue; 
  
?> 
   <div class="cover">
      <a href="<?php echo cleanURL($feature[1]); ?>">
      <img src="<?php echo $feature[2]; ?>" width="100%" height="100%" alt="<?php echo $feature[0]; ?>" />
      <div class="info title">
        <?php echo $feature[0]; ?>
      </div>
      <div class="info pastor"> 
        <?php echo $feature[3]; ?> 
      </div> 
    </a>
  </div>
<?php } ?>

  </div>
</div>

<?php } ?>

<div class="row">

<div class="col-md-8 col-md-offset-2">

<table class="table table-striped">
<thead>
<?php if ($allDirs) { ?> 
<tr><th class="col-md-1"></th><th class="col-md-5">Title</th><th class="col-md-1"></th><th class="col-md-5">Title</th></tr> 
<?php } else { ?>
<tr><th></th><th>Title</th><th>Comments</th><th>Pastor/Artist</th></tr> 
<?php } ?> 
</thead><tbody>

<?php
if ($allDirs) { $inc=2; } else { $inc = 1; } 
for ($i = 0; $i < count($items); $i+=$inc ) { 
  while ($items[$i] == "." || $items[$i] == ".." || $items[$i] == "readme.md" || $items[$i] == "featured.csv") {
    $i+=1;
  }
  if ($i >= count($items)) { 
    break; 
  } 
if (is_dir($sdir . $path . "/" . $items[$i])) { 
  ?>
    <tr>
      <td><span class="glyphicon glyphicon-folder-close"></span></td>
      <td><?php echo "<a href=\"/".cleanURL($path)."/" . cleanURL($items[$i]) . "\">" . $items[$i] . "</a>"; ?> </td>
      <?php if ($allDirs) { 
          while ($items[$i+1] == "." || $items[$i+1] == ".." || $items[$i+1] == "readme.md" || $items[$i+1] == "featured.csv") {
            $i+=1;
            echo "Foobar: $i : " . count($items); 
          }
          if ($i >= count($items)) { 
            break; 
          } 

          if (count($items) != $i+1) { ?><td><span class="glyphicon glyphicon-folder-close"></span></td>
                                <td><?php echo "<a href=\"".cleanURL($path)."/" . cleanURL($items[$i+1]) . "\">" . $items[$i+1] . "</a>"; ?> </td> 
      <?php } else { ?> <td>&nbsp;</td><td>&nbsp; </td> <?php }} else { ?> <td>&nbsp;</td><td>&nbsp; </td> <?php }  ?> </tr> <?php
  } else { 
  $file = $sdir . $path . "/" . $items[$i]; 
  if (pathinfo($file, PATHINFO_EXTENSION) == "mp3") { 
    $info = $getID3->analyze($sdir . $path . "/" . $items[$i]);
  }
  else
    $info = null; 
?>  
  <tr>
    <td><span class="glyphicon glyphicon-play"></span></td>
    <td><?php if ($info != null && array_key_exists(0, $info['tags']['id3v2']['title'])) { 
      echo "<a href=\"/sermons/" . cleanURL($path) . "/" . cleanURL($items[$i]) . "\">" . $info['tags']['id3v2']['title'][0] . "</a>"; 
    } else { 
      echo "<a href=\"/sermons" . cleanURL($path) . "/" . cleanURL($items[$i]) . "\">" . $items[$i] . "</a>"; } ?> </td>
    <td><?php echo $info['tags']['id3v2']['comment'][0]; ?> </td>
    <td><?php echo $info['tags']['id3v2']['artist'][0]; ?></td>
  </tr> 



<?php }}?> 
</tbody></table>
</div>

</div>
</div> 
<div class="footer"> 
  <div class="container"> 
    <p class="text-muted"><a href="http://spepchurch.org">Severna Park EP Church (PCA)</a> :: Hosted on <a href="https://www.digitalocean.com/?refcode=c0167ae9a50a">DigitalOcean</a> :: <a href="http://validator.w3.org/check?uri=archive.spepmedia.com">Valid XHTML</a></p>
  </div>
</div> 
</body>
</html>

<!--Debug info goes here
<?php
echo $path; 
echo "\n"; 
echo cleanURL($path); 
echo "\n"; 
?>
/debug-->
