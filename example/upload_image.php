<?php

use Vimeo\Vimeo;

/**
 *   Copyright 2013 Vimeo
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once('vendor/autoload.php');
$config = json_decode(file_get_contents('./config.json'), true);

$lib = new Vimeo($config['client_id'], $config['client_secret']);

if (empty($config['access_token'])) {
	throw new Exception('You must be authenticated to upload images. Please set an access token in config.json');
}

$lib->setToken($config['access_token']);

$video_uri = ; // Place your video uri here. It should be a video that you own, and should look like /videos/[video_id]
$video = $lib->request($video_uri);
if ($video['status'] != 200) {
	var_dump($video);
	die();
}

// we are setting activate to true so that our image gets activated as soon as it's uploaded
$response = $lib->uploadImage($video['body']['metadata']['connections']['pictures']['uri'], './test.png', true);
var_dump($response);
