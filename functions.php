<?php

define('WEBSITE_URL', 'https://www.rxakademie.cz');
define('WEBSITE_HLS', 'https://hls-c.udemycdn.com');
define('WEBSITE_DASH', 'https://dash-enc-c.udemycdn.com');
define('WEBSITE_CLOUDSOLUTIONS', 'https://app.grammarly.com');
define('COOKIE_FILE', __DIR__ . '/cookiehegwew.txt');

function initRequest($url, $cookie){
    $response = makeRequest($url, $cookie);
    $responseBody = $response["body"];
    $responseInfo = $response["responseInfo"];
    $contentType = isset($responseInfo["content_type"]) ? $responseInfo["content_type"] : 'text/html';

    // Allow from any origin
    header("Access-Control-Allow-Origin: *");

    // Allow specific HTTP methods
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    // Allow specific headers
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(204); // No content
        exit;
    }

    header("Content-Type: " . $contentType);  
    if (stripos($contentType, "text/html") !== false) {
        header("Content-Type: text/html");
        echo proxify($responseBody);
    } else if (stripos($contentType, "text/css") !== false) {
        header("Content-Type: " . 'text/css');
        echo proxify($responseBody);
    } else if (stripos($contentType, "*/*") !== false) {
        header("Content-Type: " . 'application/javascript');
        echo proxify($responseBody);
    } else {
        header("Content-Type: " . $contentType);
        //header("Content-Length: " . strlen($responseBody));
        echo proxify($responseBody);
    }
}
function makeRequest($url, $cookie)
{
    $browserRequestHeaders = mgetallheaders();    
    unset($browserRequestHeaders["Host"]);
    unset($browserRequestHeaders["Content-Length"]);
    // unset($browserRequestHeaders["Accept-Encoding"]);
    unset($browserRequestHeaders["Pragma"]);
    unset($browserRequestHeaders["Connection"]);
    unset($browserRequestHeaders['Cookie']);
    $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36';
    $referer = 'https://www.rxakademie.cz';
    $browserRequestHeaders['User-Agent'] = $agent;
    $browserRequestHeaders['Origin'] = WEBSITE_URL;
    $browserRequestHeaders['Referer'] = $referer;
    $browserRequestHeaders['Cookie'] = $cookie;
    /*if(preg_match('#'.preg_quote(WEBSITE_URL_MARKETPLACE).'#', $url)){
        $browserRequestHeaders['Sec-Fetch-Site'] = 'cross-site';
    }*/
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60 * 60 * 24,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $agent,
        // CURLOPT_COOKIEFILE => COOKIE_FILE,
        CURLOPT_REFERER => $referer,
    ));
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            break;
        case "POST":
            $browserRequestHeaders['x-kl-ajax-request'] = 'Ajax_Request';
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
            break;
        case "PUT":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_INFILE, fopen("php://input"));
            break;
        case "OPTIONS":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_NOBODY , true);
            break;
    }
    $curlRequestHeaders = array();
    foreach ($browserRequestHeaders as $name => $value) {
        $curlRequestHeaders[] = $name . ": " . $value;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlRequestHeaders);
    $err = curl_error($ch);
    $response = curl_exec($ch);
    $response = str_replace('Výsledek', '', $response);
    $response = str_replace('role="banner"', 'role="banner" style="display: none;"', $response);
    $responseInfo = curl_getinfo($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $infos = curl_getinfo($ch);
    $responseInfo['content_type'] = $browserRequestHeaders['HTTP_ACCEPT'];
    curl_close($ch);
    $responseHeaders = substr($response, 0, $headerSize);
    return array("headers" => $responseHeaders, "body" => $response, "responseInfo" => $responseInfo, 'infos'=>$infos);
}
function proxify($result)
{
    $parse = parse_url(WEBSITE_URL);
    $parseHls = parse_url(WEBSITE_HLS);
    $parseDash = parse_url(WEBSITE_DASH);
    $parseCloud = parse_url(WEBSITE_CLOUDSOLUTIONS);
    $result = str_replace(
        [
            '\/'.$parseHls['host'] . '\/',
            '/'.$parseHls['host'],
            '\/'.$parseDash['host'] . '\/',
            '/'.$parseDash['host'],
            '\/'.$parseCloud['host'] . '\/',
            '/'.$parseCloud['host'],
            '\/'.$parse['host'] . '\/',
            '/'.$parse['host'],
        ],
        [
            '\/'.$_SERVER['HTTP_HOST'].'\/udemy_hls\/',
            '/'.$_SERVER['HTTP_HOST'].'/udemy_hls',
            '\/'.$_SERVER['HTTP_HOST'].'\/udemy_dash\/',
            '/'.$_SERVER['HTTP_HOST'].'/udemy_dash',
            '\/'.$_SERVER['HTTP_HOST'].'\/udemy_cloud\/',
            '/'.$_SERVER['HTTP_HOST'].'/udemy_cloud',
            '\/'.$_SERVER['HTTP_HOST'].'\/',
            '/'.$_SERVER['HTTP_HOST'],
        ],
        $result
    );
    if(isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'http' && $parse['scheme'] == 'https'){
        $result = str_replace('https://', 'http://', $result);
    }
    return $result;
}

if (!function_exists("mgetallheaders")) {
    function mgetallheaders()
    {
        $result = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 500) == "HTTP_") {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 500)))));
                $result[$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}