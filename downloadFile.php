<?php

include 'upload_path.php';

function downloadFile(){
    global $upload_dir;
    //$filename = $_GET["file"];
    $filename = file_get_contents("php://input");
    $file = $upload_dir . $filename;
    //                header("HTTP/1.0 200 Ok");
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    //header("Content-Type: application/force-download");
    header('Content-Disposition: attachment; filename=' . urlencode(basename($filename)));
    // header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    ob_clean();
    flush();
    readfile($file);
    exit;
}

downloadFile();


?>