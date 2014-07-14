<?php

/**
 * VOD example using the Official PHP library for the Vimeo API
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

// Create a new vod page
$create_vod_page = $lib->request('/me/ondemand/pages', array('name' => 'ohhai', 'type' => 'film', 'content_rating' => 'safe', 'link' => 'ohhai', 'dommain_link' => 'ohhai', 'rent.active' => true, 'rent.price.USD' => 1.0, 'rent.period' => 'week', 'buy.active' => true, 'episodes.rent.active' => true, 'episodes.rent.price.USD' => 1.0, 'episode.rent.period' => 'week', 'episodes.buy.active' => true, 'episodes.buy.price.USD' => 1.0), 'POST');

print_r($create_vod_page);

// Add a video to the newly created vod page
$add_video = $lib->request('/ondemand/pages/ohhai/videos/27687226', array('type' => 'main', 'rent.active' => true, 'rent.price.USD' => 1.0, 'buy.active' => true, 'buy.price.USD' => 2.0), 'PUT');

print_r($add_video);

// Check to make sure the new video has been added to the vod page properly
$check_video = $lib->request('/ondemand/pages/ohhai/videos', array('filter' => 'all', 'sort' => 'default'));

print_r($check_video);

?>
