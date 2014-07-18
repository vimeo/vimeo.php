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

// VOD film example

// Create a new vod page
$create_vod_film = $lib->request('/me/ondemand/pages', array('name' => 'myfilm', 'type' => 'film', 'content_rating' => 'safe', 'link' => 'myfilm', 'dommain_link' => 'myfilm', 'rent' => array('active' => true, 'price' => array('USD' => 5.0), 'period' => '24 hour'), 'buy' => array('active' => true, 'price' => array('USD' => 10.0))), 'POST');

// Set a Genre
$genre = $lib->request('/ondemand/pages/myfilm/genres/art', array(), 'PUT');

// Add a video
$uri = $lib->upload('myvideo.mp4');
$video_data = $lib->request($uri);
$film_video = $lib->request('/ondemand/pages/myfilm'.$video_data['body']['uri'], array('type' => 'main'), 'PUT');

// Add a trailer
$uri = $lib->upload('mytrailer.mp4');
$video_data = $lib->request($uri);
$film_trailer = $lib->request('/ondemand/pages/myfilm'.$video_data['body']['uri'], array('type' => 'trailer'), 'PUT');

// Check to make sure the new video and trailer has been added to the vod page properly
$check_video = $lib->request('/ondemand/pages/myfilm/videos', array('filter' => 'all', 'sort' => 'default'));
print_r($check_video);

// Add a picture to our vod page
$response = $lib->uploadImage('/ondemand/pages/myfilm/pictures', './test.png');
$poster = $lib->request($response, array('active' => true), 'PATCH');

// Publish our new vod page - You can only publish after you all videos for your film have finished transcoding
//$publish_video = $lib->request('/ondemand/pages/myfilm', array('publish' => array('active' => true)), 'PATCH');
//print_r($publish_video);

// VOD series example

// Create a new vod series
$create_vod_series = $lib->request('/me/ondemand/pages', array('name' => 'myseries', 'type' => 'series', 'content_rating' => 'safe', 'link' => 'myseries', 'dommain_link' => 'myseries', 'rent' => array('active' => true, 'price' => array('USD' => 5.0), 'period' => '24 hour'), 'buy' => array('active' => true, 'price' => array('USD' => 10.0)), 'episodes' => array('rent' => array('active' => true, 'price' => array('USD' => 1.0), 'period' => '48 hour'), 'buy' => array('active' => true, 'price' => array('USD' => 2.0)))), 'POST');

// Set a Genre
$genre = $lib->request('/ondemand/pages/myseries/genres/art', array(), 'PUT');

// Add some videos
$uri = $lib->upload('myvideo1.mp4');
$video_data = $lib->request($uri);
$series_video_1 = $lib->request('/ondemand/pages/myseries'.$video_data['body']['uri'], array('type' => 'main'), 'PUT');
$uri = $lib->upload('myvideos2.mp4');
$video_data = $lib->request($uri);
$series_video_2 = $lib->request('/ondemand/pages/myseries'.$video_data['body']['uri'], array('type' => 'main', 'rent' => array('active' => true, 'price' => array('USD' => 3)), 'buy' => array('active' => true, 'price' => array('USD' => 4))), 'PUT');

// Add a trailer
$uri = $lib->upload('mytrailer.mp4');
$video_data = $lib->request($uri);
$series_trailer = $lib->request('/ondemand/pages/myseries/'.$video_data['body']['uri'], array('type' => 'trailer'), 'PUT');

// Check to make sure the new video and trailer has been added to the vod page properly
$check_series_video = $lib->request('/ondemand/pages/myseries/videos', array('filter' => 'all', 'sort' => 'default'));
print_r($check_series_video);

// Add a picture to our vod page
$response = $lib->uploadImage('/ondemand/pages/myseries/pictures', './test.png');
$poster = $lib->request($response, array('active' => true), 'PATCH');

// Publish our new vod page - You can only publish after you all videos for your series have finished transcoding
//$publish_video = $lib->request('/ondemand/pages/myseries', array('publish' => array('active' => true)), 'PATCH');
//print_r($publish_video);

?>
