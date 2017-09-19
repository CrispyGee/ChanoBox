<?php

include 'upload_path.php';

function deleteFile() {
    global $upload_dir;
    $filename = file_get_contents("php://input");
    $deleted = unlink($upload_dir . $filename);
    echo $deleted;
    header("HTTP/1.0 200 Ok");
}

deleteFile();

?>