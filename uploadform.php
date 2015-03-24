<?php
if(!defined('ROOT')) exit('No direct script access allowed');
?>
<style>
.photopreview {
	width:100%;
	border-size:2px;
}
form {
	margin-bottom: 0px;
}
</style>
<table width=100% border=0 cellpadding=0 cellspacing=0>
	<tr>
		<td id="photopreviewerframeholder" colspan=10 align=center>
			<iframe id="photopreviewerframe" class='photopreview imageholder' style='width:98%;height:400px;margin-top:5px;' frameborder=0></iframe>
		</td>
	</tr>
	<tr>
		<th align=center>
			<form id="uploadPhotofield" target='photopreviewerframe' method=POST enctype='multipart/form-data'>
				<input type=file name=photo accept='image/*' style='width:100%' onchange="previewPhoto();" />
			</form>
			<div align=right style='display:none;'>
				<label>0<input type=range id="zoomPhoto" min=5 max=15 value=10 />10</label>
				<label><input type=checkbox id="fitPhoto" />Fit Photo To Window</label>
			</div>
		</th>
	</tr>
</table>
<script language=javascript>
function previewPhoto() {
	$("#photopreviewerframeholder").html("<iframe id=photopreviewerframe class='photopreview ajaxloading6' style='height:400px;'></iframe>");
	
	lnk=getServiceCMD("photocrop")+"&action=preview&pckey="+pcKey;
	if($("#fitPhoto").is(":checked")) lnk+="&fitphoto=true";
	else {
		lnk+="&zoomPhoto="+$("#zoomPhoto").val()/($("#zoomPhoto").attr("max")-$("#zoomPhoto").attr("min"));
	}
	$("form#uploadPhotofield").attr("action",lnk);
	$("form#uploadPhotofield").submit();
}
function updateCropListeners(txt) {
	if(txt.indexOf("src#")==0) {
		var parentWindow =(window.parent==window)?window.opener:window.parent;
		src=txt.substr(4);
		if(funcName.length>0) parentWindow[funcName](src);
	} else {
		alert("Failed To Update The Crop To Server. Try Again.");
	}
}
function photoLoaded(txt) {
	$("#photopreviewerframe").removeClass("ajaxloading6");
}
</script>
