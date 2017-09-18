<?php

$upload_dir = "/var/dateien/";


/**
 *
 * Logging operation - to a file (upload_log.txt) and to the stdout
 * @param string $str - the logging string
 */
function _log($str) {

    // log to the output
    $log_str = date('d.m.Y').": {$str}\r\n";
    echo $log_str;

    // log to file
    if (($fp = fopen('upload_log.txt', 'a+')) !== false) {
        fputs($fp, $log_str);
        fclose($fp);
    }
}

# DAS BRAUCHEN WIR NICHT. UND ES IST SEHR GEFAEHRLICH, WENN MAN DAS FALSCHE DIRECTORY ERWISCHT.
/**
 * 
 * Delete a directory RECURSIVELY
 * @param string $dir - directory path
 * @link http://php.net/manual/en/function.rmdir.php
 */
function rrmdir($dir) {
    die("rmdir($dir) VERBOTEN");
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    rrmdir($dir . "/" . $object); 
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

/**
 *
 * Check if all the parts exist, and 
 * gather all the parts of the file together
 * @param string $temp_dir - the temporary directory holding all the parts of the file
 * @param string $fileName - the original file name
 * @param string $chunkSize - each chunk size (in bytes)
 * @param string $totalSize - original file size (in bytes)
 */
function createFileFromChunks($temp_dir, $fileName, $chunkSize, $totalSize,$total_files) {

    global $upload_dir;
    // count all the parts of this file
    $total_files_on_server_size = 0;
    $temp_total = 0;
    foreach(scandir($temp_dir) as $file) {
        $temp_total = $total_files_on_server_size;
        $tempfilesize = filesize($temp_dir.'/'.$file);
        $total_files_on_server_size = $temp_total + $tempfilesize;
    }
    // check that all the parts are present
    // If the Size of all the chunks on the server is equal to the size of the file uploaded.
    if ($total_files_on_server_size >= $totalSize) {
    // create the final destination file 
        if (($fp = fopen($upload_dir.$fileName, 'w')) !== false) {
            for ($i=1; $i<=$total_files; $i++) {
                fwrite($fp, file_get_contents($temp_dir.'/'.$fileName.'.part'.$i));
                //_log('writing chunk '.$i);
            }
            fclose($fp);
        } else {
            _log('cannot create the destination file');
            return false;
        }

        // rename the temporary directory (to avoid access from other 
        // concurrent chunks uploads) and than delete it
        if (rename($temp_dir, $temp_dir.'_UNUSED')) {
            rrmdir($temp_dir.'_UNUSED');
        } else {
            rrmdir($temp_dir);
        }
    }

}

function human_filesize($bytes, $decimals = 2) {
  $sz = "BKMGTP";
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

function getFileList(){
    global $upload_dir;
    $file_array = array_slice(scandir($upload_dir), 2);
    $files_json = "[";
    foreach ($file_array as $file_name) {
        $file_size = human_filesize(filesize($upload_dir . $file_name));
        $files_json .= "{\"name\":\"" . $file_name . "\",\"size\":\"" . $file_size . "\"},";
    }
    if (strlen($files_json) > 1){
    $files_json = substr($files_json, 0, -1);
    }
    $files_json .= "]";
    echo $files_json;
    header("HTTP/1.0 200 Ok");
}

function deleteFile(){
    global $upload_dir;
    $filename = file_get_contents("php://input");
    $deleted = unlink($upload_dir . $filename);
    echo $deleted;
    header("HTTP/1.0 200 Ok");
}

function downloadFile(){
    global $upload_dir;
    //$filename = $_GET["file"];
    $filename = file_get_contents("php://input");
    $file = $upload_dir . $filename;
    //                header("HTTP/1.0 200 Ok");
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Type: application/force-download");
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

//check if request is GET and the requested chunk exists or not. this makes testChunks work
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if(isset($_GET['fileList'])){
        //_log("fileList is set");
        getFileList();
    }
    else {

        if(!(isset($_GET['resumableIdentifier']) && trim($_GET['resumableIdentifier'])!='')){
            $_GET['resumableIdentifier']='';
        }
        $temp_dir = $upload_dir.$_GET['resumableIdentifier'];
        if(!(isset($_GET['resumableFilename']) && trim($_GET['resumableFilename'])!='')){
            $_GET['resumableFilename']='';
        }
        if(!(isset($_GET['resumableChunkNumber']) && trim($_GET['resumableChunkNumber'])!='')){
            $_GET['resumableChunkNumber']='';
        }
        $chunk_file = $temp_dir.'/'.$_GET['resumableFilename'].'.part'.$_GET['resumableChunkNumber'];
        if (file_exists($chunk_file)) {
            header("HTTP/1.0 200 Ok");
        } else {
            header("HTTP/1.0 208 Already Reported");
        }

    }
}

else if ($_SERVER['REQUEST_METHOD'] === 'DELETE'){
    deleteFile();
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (isset($_GET['download'])){
        downloadFile();
    }
}

// loop through files and move the chunks to a temporarily created directory
if (!empty($_FILES)) foreach ($_FILES as $file) {

    // check the error status
    if ($file['error'] != 0) {
        _log('error '.$file['error'].' in file '.$_POST['resumableFilename']);
        continue;
    }

    // init the destination file (format <filename.ext>.part<#chunk>
    // the file is stored in a temporary directory
    if(isset($_POST['resumableIdentifier']) && trim($_POST['resumableIdentifier'])!=''){
        $temp_dir = $upload_dir.$_POST['resumableIdentifier'];
    }
    $dest_file = $temp_dir.'/'.$_POST['resumableFilename'].'.part'.$_POST['resumableChunkNumber'];

    // create the temporary directory
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0777, true);
    }

    // move the temporary file
    if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
        _log('Error saving (move_uploaded_file) chunk '.$_POST['resumableChunkNumber'].' for file '.$_POST['resumableFilename']);
    } else {
        // check if all the parts present, and create the final destination file
        createFileFromChunks($temp_dir, $_POST['resumableFilename'],$_POST['resumableChunkSize'], $_POST['resumableTotalSize'],$_POST['resumableTotalChunks']);
    }
}

?>