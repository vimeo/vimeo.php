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

$config = require(__DIR__ . '/init.php');

$lib = new Vimeo($config['client_id'], $config['client_secret']);

if (empty($config['access_token'])) {
	throw new Exception('You must be authenticated to upload images. Please set an access token in config.json');
}

$lib->setToken($config['access_token']);

// first arg is filename
array_shift($argv);

// second arg should be your resource uri
$resource_uri = array_shift($argv);
$image_path = array_shift($argv);

if (empty($resource_uri)) {
	throw new Exception('You must provide a resource uri as the first argument to this script');
}

if (empty($image_path)) {
	throw new Exception('You must provide the full path to an image as the second argument to this script');
}

// Find the pictures URI. This is also the URI that you can query to view all pictures associated with this resource.
$resource = $lib->request($resource_uri);
if ($resource['status'] != 200) {
	var_dump($resource);
	throw new Exception('Could not locate the requested resource uri [' . $resource_uri . ']');
}

if (empty($resource['body']['metadata']['connections']['pictures']['uri'])) {
	throw new Exception('The resource you loaded does not have a pictures connection. This most likely means that picture uploads are not supported for this resource');
}

// The third parameter dictates whether the picture should become the default, or just be part of the collection of pictures
$response = $lib->uploadImage($resource['body']['metadata']['connections']['pictures']['uri'], $image_path, true);
var_dump($response);
