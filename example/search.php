<?php

/**
 * Custom search for videos only from myusername using the Official PHP library for the Vimeo API
*/

require_once('../vimeo.php');

if (!function_exists('json_decode')) {
    throw new Exception('We could not find json_decode. json_decode is found in php 5.2 and up, but not found on many linux systems due to licensing conflicts. If you are running ubuntu try "sudo apt-get install php5-json".');
}

$config = json_decode(file_get_contents('./config.json'), true);

if (empty($config['access_token'])) {
    throw new Exception('You can not upload a file without an access token. You can find this token on your app page, or generate one using auth.php');
}

$lib = new Vimeo($config['client_id'], $config['client_secret'], $config['access_token']);
$search_results = $lib->request('/videos?per_page=50&query='.urlencode($_GET['query']));

$videos = $search_results['body']['data'];
$out=array_map(function($x) {return array('link' => $x['link'], 'name' => $x['name'], 'user' => $x['user']['name']);}, $videos);

class validItems extends FilterIterator
{
    public function accept()
    {
        $current = $this->current();
        if ($current['user'] == 'myusername') {
            return true;
        }
        return false;
    }
}

$available = new validItems(new ArrayIterator($out));
$out = array();
foreach ($available as $value) {
        array_push($out, $value);
}

echo json_encode($out);

?>
