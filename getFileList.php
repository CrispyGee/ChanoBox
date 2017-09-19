<?php

include 'upload_path.php';

class FileProperties
{
    public $name;
    public $size;
    public $timestamp;
    public $hash;
}

function human_filesize($bytes, $decimals = 2) {
  $sz = "BKMGTP";
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

function getFileList() {
    global $upload_dir;
    $file_array = array_slice(scandir($upload_dir), 2);
    $filePropertyArray = array();
    foreach ($file_array as $file_name) {
        if (substr( $file_name, 0, 5 ) != "_temp" && strlen($file_name) > 32) {
        	$currentFile = new FileProperties();
			$currentFile->size = human_filesize(filesize($upload_dir . $file_name));
			$currentFile->hash = substr( $file_name, 0, 31);
			$currentFile->name = substr( $file_name, 32, strlen($file_name)-1);
			$currentFile->timestamp = filemtime($upload_dir . $file_name);
			array_push($filePropertyArray, $currentFile);
        }
    }
    echo json_encode($filePropertyArray);
    header("HTTP/1.0 200 Ok");
}

getFileList();

?>