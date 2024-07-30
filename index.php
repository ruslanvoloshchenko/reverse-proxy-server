<?php
require_once __DIR__ . '/functions.php';
$url = WEBSITE_URL . $_SERVER['REQUEST_URI'];
$filename = "cookie.txt";

// Open the file for reading
$file = fopen($filename, "r");

// Check if the file is opened successfully
if ($file) {
    // Read the file content
    $filesize = filesize($filename);
    $data = fread($file, $filesize);

    fclose($file);
    print_r(isset($_COOKIE['user']) ? "Hello" : "fail");

    if(isset($_COOKIE['user'])) {
		initRequest($url, $data);
    } else {
    	header('Location: https://backend-drpozd.eu1.pitunnel.com?referer='.urlencode('https://test-drpozd.eu1.pitunnel.com/'));
    	exit;
    }
} else {
    echo "Unable to open the file.";
}

?>

