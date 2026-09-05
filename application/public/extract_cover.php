<?php
include('../init.php');
$cover = '';
$q = 75;
header('Cache-Control: public, max-age=86400');

function resizeCover($filename, $newwidth, $newheight){
	$i = imagecreatefromstring($filename);
	$width = imagesx($i);
       	$height = imagesy($i);
    if($width > $height && $newheight < $height){
        $newheight = (int)round($height / ($width / $newwidth));
    } else if ($width < $height && $newwidth < $width) {
        $newwidth = (int)round($width / ($height / $newheight));
    } else {
        $newwidth = (int)round($width);
        $newheight = (int)round($height);
    }
    $thumb = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresized($thumb, $i, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
    return $thumb;
}

function lastm($path) {
	$fmtimestamp = filemtime($path);
	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $fmtimestamp <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
		die();
	} else {
		header("Expires: " . gmdate("D, d M Y H:i:s", filemtime($path) + 60*60*24) . " GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($path)) . " GMT");

		echo file_get_contents($path);
	}
}

$small = isset($_GET['small']);

$id = 0;
if (isset($_GET['id'])) {
	$id = intval($_GET['id']);
} else if (isset($_GET['sid'])) {
	$id = intval($_GET['sid']);
	$small = true;
}
$iid = $id;

header("Content-type: image/jpeg");

if ($id <= 0) {
	die();
}

if ($small) {
	if (file_exists(ROOT_PATH . "cache/covers/$id-small.jpg")) {
		lastm(ROOT_PATH . "cache/covers/$id-small.jpg");
		die();
	}
} else {
	if (file_exists(ROOT_PATH . "cache/covers/$id.jpg")) {
		lastm(ROOT_PATH . "cache/covers/$id.jpg");
		die();
	}
}

$stmt = $dbh->prepare("SELECT file FROM libbpics WHERE BookId=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$f = $stmt->fetch();

if ($f && isset($f->file)) {
	$zip = new ZipArchive(); 
	if ($zip->open(ROOT_PATH . "cache/lib.b.attached.zip")) {
		$f_data = $zip->getFromName($f->file);
		if ($f_data !== false && strlen($f_data) > 0) {
			file_put_contents(ROOT_PATH . "cache/covers/$id.jpg", $f_data);
			$thm = resizeCover($f_data, 300, 400);
			imagejpeg($thm, ROOT_PATH . "cache/covers/$id-small.jpg", 75);
			imagedestroy($thm);
			if ($small) {
				if (file_exists(ROOT_PATH . "cache/covers/$id-small.jpg")) {
					lastm(ROOT_PATH . "cache/covers/$id-small.jpg");
					die();
				}
			} else {
				echo $f_data;
				die();
			}
		}
	}
	$zip->close();
}


$stmt = $dbh->prepare("SELECT filetype FROM libbook WHERE bookid=:id LIMIT 1");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$book_res = $stmt->fetch();
if (!$book_res) {
	echo file_get_contents('/application/none.jpg');
	die();
}
$type = trim($book_res->filetype);
if ($type == 'fb2') {
	$u = 0;
} else {
	$u = 1;
}

$stmt = $dbh->prepare("SELECT filename FROM book_zip WHERE :id BETWEEN start_id AND end_id AND usr=:usr LIMIT 1");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':usr', $u, PDO::PARAM_INT);
$stmt->execute();
$zip_row = $stmt->fetch();
if (!$zip_row) {
	echo file_get_contents('/application/none.jpg');
	die();
}
$zip_name = $zip_row->filename;
$zip = new ZipArchive(); 

$stmt = $dbh->prepare("SELECT filename FROM libfilename WHERE BookId=:id LIMIT 1");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch();

if ($result && !empty($result->filename)) {
    $filename = $result->filename;
} else {
    $filename = trim("$id.$type");
}

if ($zip->open(BOOKS_PATH . $zip_name)) {
	$f = $zip->getFromName("$filename");
}


if ($type == 'fb2') {
	$fb2 = simplexml_load_string($f);
	$images = array();
	if (isset($fb2->binary)) {
		foreach ($fb2->binary as $binary) {
			$id = $binary->attributes()['id'];		
			if (
				(strpos($id, "cover") !==  false) ||
				(strpos($id, "jpg") !==  false) ||
				(strpos($id, "obloj") !==  false)
			) {
				$cover = base64_decode($binary);
			}
			$images["$id"] = $binary;
		}
	}
	$zip->close();
}

if ($type == 'epub') {
	file_put_contents(ROOT_PATH . "cache/tmp/$iid.tmp", $f);
	include('/application/epub.php');
	$d = new EPub(ROOT_PATH . "cache/tmp/$iid.tmp");
	$im = $d->Cover();
	if ($im['found'] != '') {
		$cover = $im['data'];
		unlink(ROOT_PATH . "cache/tmp/$iid.tmp");
	} else {
		echo file_get_contents('/application/none.jpg');
	}
}

if (strlen($cover) < 100) {
	$cover = file_get_contents('/application/none.jpg');
	echo $cover;
	die();
} else {
	file_put_contents(ROOT_PATH . "cache/covers/$iid.jpg", $cover);
	$thm = resizeCover($cover, 300, 400);
	imagejpeg($thm, ROOT_PATH . "cache/covers/$iid-small.jpg", 75);
	imagedestroy($thm);
}

if ($small) {
	if (file_exists(ROOT_PATH . "cache/covers/$iid-small.jpg")) {
		lastm(ROOT_PATH . "cache/covers/$iid-small.jpg");
		die();
	}
} else {
	echo $cover;
}

