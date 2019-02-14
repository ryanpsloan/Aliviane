<?php
session_start();
if (isset($_SESSION['fileName'])) {
    $file = $_SESSION['fileName'];
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        ignore_user_abort(true);
        unlink($file);
        exit;
    }
}
