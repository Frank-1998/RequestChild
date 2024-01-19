<?php
include 'ChildRequest.php';
$astiri = new ChildRequest(5);
$astiri->childProcess("uri2");
$astiri->childProcess("uri1");
$astiri->childProcess("uri1");
$astiri->childProcess("uri1");
$astiri->childProcess("uri1");
$astiri->childProcess("uri1");
$astiri->childProcess("uri1");
$astiri->childProcess("uri1");
$astiri->childProcess("uri1");
$astiri->childProcess("uri1");
$astiri->childProcess("uri1");

$imgArray = $astiri->retrieveHistogram();
$img = $imgArray["uri1"]; 
header("Content-type:image/png");
imagepng($img);
$_REQUEST['asdfad']=234234;

?>