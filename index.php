<?php //include "config.php"; ?> 
<?php require_once('getid3/getid3.php'); 
$getID3 = new getID3; 
?> 
<?php $sdir = "/data/sermons/"; ?> 
<?php
//ini_set('display_errors', 1);
//error_reporting(~0);
?>

<html><head><title>Sermon Archive</title>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

</head>
<body>
<div style="font-size:16pt;padding-left:6pt">
<?php
$path = urldecode($_SERVER['REQUEST_URI']);
if ($path == "/") {
  $path = "";
}
$output = array(); 
$output[] = '<a href="/">Home</a>';
$chunks = explode('/', $path);
foreach ($chunks as $i => $chunk) {
    if ($chunk == "" ) { continue; } 
    $output[] = sprintf(
        '<a href="%s">%s</a>',
        implode('/', array_slice($chunks, 0, $i + 1)),
        $chunk
    );
}
  
echo implode(' &gt;&gt; ', $output);
?>
</div>
<div class="row">
<div class="col-md-8 col-md-offset-2">
<table class="table table-striped">
<thead>
<tr><th></th><th>Title</th><th>Comments</th><th>Pastor/Artist</th></tr> 
</thead><tbody>
<?php 
$items=scandir("/data/sermons" . $path); 
foreach ($items as $item) { 
  if ($item == "." || $item == "..") { 
    continue; 
  }
if (is_dir($sdir . $path . "/" . $item)) { 
  ?>
    <tr>
      <td><span class="glyphicon glyphicon-folder-close"></span></td>
      <td><?php echo "<a href=\"$path/$item\">$item</a>"; ?> </td>
      <td>&nbsp;</td><td>&nbsp; </td></tr>
<?php
  } else { 
  $file = $sdir . $path . "/" . $item; 
  if (pathinfo($file, PATHINFO_EXTENSION) == "mp3") { 
    $info = $getID3->analyze($sdir . $path . "/" . $item);
  }
  else
    $info = null; 
?>  
  <tr>
    <td><span class="glyphicon glyphicon-play"></span></td>
    <td><?php if ($info != null && array_key_exists(0, $info['tags']['id3v2']['title'])) { 
      echo "<a href=\"/sermons" . $path . "/" . $item . "\">" . $info['tags']['id3v2']['title'][0] . "</a>"; 
    } else { 
      echo "<a href=\"/sermons" . $path . "/" . $item . "\">" . $item . "</a>"; } ?> </td>
    <td><?php echo $info['tags']['id3v2']['comment'][0]; ?> </td>
    <td><?php echo $info['tags']['id3v2']['artist'][0]; ?></td>
  </tr> 



<?php }} ?> 
</tbody></table>
</div>
</div>
</body>
