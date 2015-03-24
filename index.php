<?php
if(!defined('ROOT')) exit('No direct script access allowed');

_css(array("formfields","ajax"));

if(!isset($_REQUEST['forTable'])) $_REQUEST['forTable']="";
if(!isset($_REQUEST['forPhotoCol'])) $_REQUEST['forPhotoCol']="";
if(!isset($_REQUEST['forIdCol'])) $_REQUEST['forIdCol']="";
if(!isset($_REQUEST['forIdVal'])) $_REQUEST['forIdVal']="";

$key=_randomId()."_PhotoCrop";
_dataBus("$key.src",$_REQUEST['src']);
_dataBus("$key.rel",$_REQUEST['rel']);
_dataBus("$key.forTable",$_REQUEST['forTable']);
_dataBus("$key.forPhotoCol",$_REQUEST['forPhotoCol']);
_dataBus("$key.forIdCol",$_REQUEST['forIdCol']);
_dataBus("$key.forIdVal",$_REQUEST['forIdVal']);
_dataBus("$key.site",$_REQUEST['site']);
_dataBus("$key.func",$_REQUEST['func']);

//printArray($_REQUEST);exit();

cleanPCCache();
?>
<style>
html,body {
	width:100%;height:100%;
	padding:0px;margin:0px;
	overflow:hidden;
	background:#FFF;
	min-width:600px;
}
</style>
<script>
pcKey="<?=$key?>";
funcName="<?=$_REQUEST['func']?>";
src="<?=$_REQUEST['src']?>";
</script>
<?php
include "uploadform.php";

function cleanPCCache() {
	$cacheDir=APPROOT.APPS_CACHE_FOLDER."photocrop/";
	if(!file_exists($cacheDir)) {
		mkdir($cacheDir,0777,true);
		chmod($cacheDir,0777);
	}
	if(!file_exists($cacheDir)) {
		exit("<h3>Error Creating PhotoCroping Cache Folder</h3>");
	}
	
	$fs=scandir($cacheDir);
	unset($fs[0]);unset($fs[1]);
	foreach($fs as $a) {
		$f="{$cacheDir}{$a}";
		if((time()-filectime($f))>getConfig("CACHE_EXPIRY")) {
			unlink($f);
		}
	}
}
?>
