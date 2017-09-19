<?php

include 'upload_path.php';

function deleteFile() {
    global $upload_dir;
    $hash = $_GET['hash'];
    $file_array = array_slice(scandir($upload_dir), 2);
    $filename = "";
    foreach ($file_array as $current_filename) {
        if (strlen($current_filename) > 32) {
        	$current_hash = substr( $current_filename, 0, 32);
        	if ($hash == $current_hash){
        		$filename = $current_filename;
        	}
        }
    }
    if ($filename !== ""){
	    $deleted = unlink($upload_dir . $filename);
	    echo $deleted;
	    header("HTTP/1.0 200 Ok");
	}
	else {
		header('HTTP/1.1 404 Not Found');
	}
}

deleteFile();

?>