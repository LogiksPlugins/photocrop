<?php
if(!defined('ROOT')) exit('No direct script access allowed');
$src=$_SESSION["CROP_SRC"];
unset($_SESSION["CROP_SRC"]);
$cacheImg=APPROOT.APPS_CACHE_FOLDER."photocrop/$src";

_css("colors");

$xtraImgAttrs="";
if(isset($_REQUEST['fitphoto']) && $_REQUEST['fitphoto']=="true") $xtraImgAttrs="width='100%' height='100%' ";
elseif(isset($_REQUEST['zoomPhoto'])) {
	if(file_exists($cacheImg)) {
		$size=getimagesize($cacheImg);
		if($size!=null && count($size)>0) {
			$w=($size[0]*$_REQUEST['zoomPhoto'])."px";
			$h=($size[1]*$_REQUEST['zoomPhoto'])."px";
		}
	} else {
		$w=(100*$_REQUEST['zoomPhoto'])."%";
		$h=(100*$_REQUEST['zoomPhoto'])."%";
	}
	$xtraImgAttrs="width='$w' height='$h' ";
}
?>
<script type="text/javascript" src="<?=SiteLocation?>api/js/jquery/jquery.js"></script>
<script type="text/javascript" src="<?=SiteLocation?>api/js/jquery/jquery.jcrop.js"></script>
<script type="text/javascript" src="<?=SiteLocation?>api/js/ajax.js"></script>
<link rel="stylesheet" type="text/css" href="<?=SiteLocation?>misc/skins/default/jquery.jcrop.css" />
<style>
html,body {
	width:100%;height:100%;
	padding:0px;margin:0px;
	overflow:hidden;
}
.overflowAuto {
	overflow:auto;
}
</style>
<div id=imgCropHolder123 style='width:100%;height:90%;overflow:hidden;border:0px;' align=center>
	<img id="jcrop_target" src="<?=SiteLocation?>services/?scmd=photocrop&site=<?=SITENAME?>&action=cachedphoto&src=<?=$src?>" 
		style='margin:auto;border:0px;' <?=$xtraImgAttrs?> />
</div>
<div style='width:100%;height:25px;border:0px;margin:0px;margin-top:5px;' align=center>
	<button class='clr_darkblue' onclick='saveCropedImage();$(this).hide();' style='width:150px;height:25px;'>Save</button>
</div>
<script>
cropX="";cropY="";cropW="";cropH="";
jcrop=null;
$(function() {
	jcrop=$('#jcrop_target').Jcrop({
			setSelect:[10,10,100,100],
			bgColor:     'black',
            bgOpacity:   .4,
            //aspectRatio: 16/9,
			onSelect:updatePCCoords
		});
	//$("button").button();
	var parentWindow =(window.parent==window)?window.opener:window.parent;
	parentWindow["photoLoaded"]("");
});
function updatePCCoords(c) {
	cropX=c.x;
	cropY=c.y;
	cropW=c.w;
	cropH=c.h;
}
function saveCropedImage() {
	if(parseInt(cropW)>0) {
		var parentWindow =(window.parent==window)?window.opener:window.parent;
		lnk="<?=_service("photocrop")?>&action=cropphoto&src=<?=$src?>&pckey="+parentWindow.pcKey;
		q="&cropX="+cropX+"&cropY="+cropY+"&cropW="+cropW+"&cropH="+cropH;
		processAJAXPostQuery(lnk,q,function(txt) {
				if(txt.indexOf("src#")==0) {
					src=txt.substr(4);
					parentWindow["updateCropListeners"](txt);
				} else {
					alert(txt);
				}
			});
	}
}

function cropRandom() {
	var dim = jcrop.getBounds();
	return [
		Math.round(Math.random() * dim[0]),
		Math.round(Math.random() * dim[1]),
		Math.round(Math.random() * dim[0]),
		Math.round(Math.random() * dim[1])
	];
};
</script>
