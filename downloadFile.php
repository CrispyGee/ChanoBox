<?php

include 'upload_path.php';

function downloadFile(){
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
	    $file = $upload_dir . $filename;
	    header('Content-Description: File Transfer');
	    header("Content-Type: x-type/subtype");
	    $actual_filename = substr($filename, 32, strlen($filename)-1);
	    header('Content-Disposition: attachment; filename=' . urlencode(basename($actual_filename)));
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	    header('Pragma: public');
	    header('Content-Length: ' . filesize($file));
	    ob_clean();
	    flush();
	    readfile($file);
    }
    else {
    	header('HTTP/1.1 404 Not Found');
    }
    exit;
}

downloadFile();


?>