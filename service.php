<?php
if (!defined('ROOT')) exit('No direct script access allowed');
checkServiceSession();

if(isset($_REQUEST['action'])) {
	if($_REQUEST['action']=="preview" && isset($_FILES['photo'])) {
		//$pcKey=$_REQUEST["pckey"];
		$lnk=moveFileToCache('photo');
		if(!is_array($lnk)) {
			printPhotoFrame($lnk);
		} else {
			printArray($lnk);
		}
	} elseif($_REQUEST['action']=="cachedphoto" && isset($_REQUEST['src'])) {
		printFromCache($_REQUEST['src']);
	} elseif($_REQUEST['action']=="cropphoto") {
		cropAndSavePhoto();
	}
}
exit();
function getDBConnX() {
	if(isAdminSite(false)) {
		if(isset($_SESSION["LGKS_CMS_SITE"])) {
			loadModule("dbcon");
			$dbCon=getDBControls($_SESSION["LGKS_CMS_SITE"]);
			return $dbCon;
		} else {
			return _db();
		}
	} else {
		return _db();
	}
}
function getPhotoSite($key) {
	if(isAdminSite(false)) {
		if(isset($_SESSION["LGKS_CMS_SITE"])) {
			return $_SESSION["LGKS_CMS_SITE"];
		} else {
			return _dataBus("$key.site");
		}
	} else {
		return _dataBus("$key.site");
	}
}
function moveFileToCache($name) {
	$cacheDir=APPROOT.APPS_CACHE_FOLDER."photocrop/";
	$fileTypes = array('jpg','jpeg','gif','png');
	
	$fname=$_FILES[$name]['name'];
	$type=$_FILES[$name]['type'];
	$size=$_FILES[$name]['size'];
	$tempFile=$_FILES[$name]['tmp_name'];
	$error=$_FILES[$name]['error'];
	
	$newName = md5(rand() * time());
	$pathInfo=pathinfo($fname);
	$ext=$pathInfo['extension'];
		
	$targetPath="{$cacheDir}{$newName}.{$ext}";
	if(!file_exists(dirname($targetPath))) {
		mkdir(dirname($targetPath),0777,true);
		chmod(dirname($targetPath),0777);
	}
	if(!file_exists(dirname($targetPath))) {
		return array("Error"=>"Failed To Create Cache Folder.");
	}
	if(in_array(strtolower($ext),$fileTypes)) {
		$a=move_uploaded_file($tempFile,$targetPath);
		if($a) return $targetPath;
		else return array("Error"=>"Caching Photo.");
	} else {
		return array("Error"=>"Invalid Photo Type.");
	}
}
function printFromCache($src) {
	$cacheDir=APPROOT.APPS_CACHE_FOLDER."photocrop/";
	$file="{$cacheDir}/{$src}";
	if(!file_exists($file)) {
		echo "No Image Found";
		return;
	}
	$pathInfo=pathinfo($file);
	$ext=$pathInfo['extension'];
	$mime="image/$ext";
	$filename=basename($file);
	
	header("Content-type: $mime");
	header("Content-Transfer-Encoding: binary");
	header("Expires: 0");
	header('Pragma: no-cache');
	readfile($file);
}
function printPhotoFrame($file) {
	$_SESSION["CROP_SRC"]=basename($file);
	loadModuleLib("photocrop","cropframe");
}
function cropAndSavePhoto() {
	$cacheDir=APPROOT.APPS_CACHE_FOLDER."photocrop/";
	
	$key=$_REQUEST['pckey'];
	$pcArr=array();
	$pcArr["src"]=_dataBus("$key.src");
	$pcArr["rel"]=_dataBus("$key.rel");
	$pcArr["forTable"]=_dataBus("$key.forTable");
	$pcArr["photoCol"]=_dataBus("$key.forPhotoCol");
	$pcArr["IdCol"]=_dataBus("$key.forIdCol");
	$pcArr["IdVal"]=_dataBus("$key.forIdVal");
	
	$pcArr["site"]=getPhotoSite($key);
	
	$dbCon=getDBConnX();

	//printArray($pcArr);exit();
	//printArray($_REQUEST);exit();

	$photoID=0;
	$photoID=updatePhotoDB($pcArr,$dbCon);
	
	if(strlen($pcArr["forTable"])>0 && strlen($pcArr["photoCol"])>0 && strlen($pcArr["IdVal"])>0 && $photoID>0) {
		$photoID=updateTargetDB($photoID,$pcArr,$dbCon);
	}
	
	if($photoID>0) echo "src#$photoID";
	
}
function updatePhotoDB($pcArr,$dbCon) {
	$cacheDir=APPROOT.APPS_CACHE_FOLDER."photocrop/";
	$photoID=0;
	
	$tmpSrc="{$cacheDir}{$_REQUEST['src']}";
	$tmpFile="{$cacheDir}".md5($_REQUEST['src']).".jpg";
	$tmpThumb="{$cacheDir}".md5($_REQUEST['src'])."_thumb.jpg";
	
	if(!file_exists($tmpSrc)) {
		echo "Photo Cache Error!";
		return 0;
	}
	
	$image=new ImageProps();
	$image->load($tmpSrc);
	$image->crop($_POST["cropX"],$_POST["cropY"],$_POST["cropW"],$_POST["cropH"]);
	$image->save($tmpFile);
	$image->createThumb($tmpThumb);
	$image->clearImages();
	
	$imageType="image/".getConfig("IMAGE_STORAGE_FORMAT");
	$imageSize=filesize($tmpFile);
	$imageData=file_get_contents($tmpFile);
	$imageData=mysql_real_escape_string($imageData);
	$thumbData=file_get_contents($tmpThumb);
	$thumbData=mysql_real_escape_string($tmpThumb);
	
	if(isset($pcArr["src"]) && isset($pcArr["rel"])) {
		$table=$pcArr["src"];
		$date=date("Y-m-d");
		$userid=$_SESSION['SESS_USER_ID'];
		$site=$pcArr["site"];
		$sql="";
		if($pcArr["rel"]!==0 && strlen($pcArr['rel'])>0) {
			$tSQL="SELECT COUNT(*) AS cnt FROM $table WHERE id={$pcArr['rel']}";
			$r=$dbCon->executeQuery($tSQL);
			if($r) {
				$r=_dbData($r);
				$r=$r[0];
				if($r["cnt"]==0) {
					$pcArr["rel"]=0;
				}
				$dbCon->freeResult($r);
			}
		} else {
			$pcArr["rel"]=0;
		}
		
		if($pcArr["rel"]===0) {
			$sql="INSERT INTO $table (image_data,image_type,image_size,thumbnails,site,userid,doc,doe) VALUES";
			$sql.="('$imageData','$imageType','$imageSize','$thumbData','$site','$userid','$date','$date')";
			$dbCon->executeQuery($sql);
			$photoID=$dbCon->insert_id();
		} else {
			$sql="UPDATE $table SET image_data='$imageData',image_type='$imageType',image_size='$imageSize',thumbnails='$thumbData',site='$site',userid='$userid',doc='$date'";
			$sql.=" WHERE id={$pcArr['rel']}";
			$dbCon->executeQuery($sql);
			$photoID=$pcArr['rel'];
		}	
	}	
	unlink($tmpSrc);unlink($tmpFile);unlink($tmpThumb);
	$imageData="";
	$thumbData="";
	return $photoID;
}
function updateTargetDB($photoID,$pcArr,$dbCon) {
	$date=date("Y-m-d");
	$scanBy=$_SESSION['SESS_USER_ID'];
	
	$tblCols=_db()->getTableInfo($pcArr['forTable']);
	$tblCols=$tblCols[0];
	
	$sql="UPDATE {$pcArr['forTable']} SET {$pcArr['photoCol']}='$photoID'";
	if(in_array("doe",$tblCols)) {
		$sql.=",doe='{$date}'";
	}
	if(in_array("scan_by",$tblCols)) {
		$sql.=",scan_by='$scanBy'";
	}
	if(in_array("scanBy",$tblCols)) {
		$sql.=",scanBy='$scanBy'";
	}
	$sql.=" WHERE {$pcArr['IdCol']}='{$pcArr['IdVal']}'";
	//echo $sql;
	$dbCon->executeQuery($sql);
	return $photoID;
}
?>
