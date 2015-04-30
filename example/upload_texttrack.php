<?php

use Vimeo\Vimeo;

/**
 *   Copyright 2014 Vimeo
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
error_reporting(E_ALL);

$config = require(__DIR__ . '/init.php');

$lib = new Vimeo($config['client_id'], $config['client_secret']);

if (empty($config['access_token'])) {
	throw new Exception('You must be authenticated to upload text tracks. Please set an access token in config.json');
}

$lib->setToken($config['access_token']);

// first arg is filename
array_shift($argv);

// second arg should be your resource uri
$resource_uri = array_shift($argv);
$texttrack_path = array_shift($argv);

if (empty($resource_uri)) {
	throw new Exception('You must provide a resource uri as the first argument to this script');
}

if (empty($texttrack_path)) {
	throw new Exception('You must provide the full path to a text track as the second argument to this script');
}

// Find the text track URI. This is also the URI that you can query to view all text tracks associated with this resource.
$resource = $lib->request($resource_uri);
if ($resource['status'] != 200) {
	var_dump($resource);
	throw new Exception('Could not locate the requested resource uri [' . $resource_uri . ']');
}

if (empty($resource['body']['metadata']['connections']['texttracks']['uri'])) {
	throw new Exception('The resource you loaded does not have a text track connection. This most likely means that text track uploads are not supported for this resource');
}

// You are always required to set a text track type and language as the 3rd and 4th parameters respectively.
var_dump($response);
$response = $lib->uploadTexttrack($resource['body']['metadata']['connections']['texttracks']['uri'], $texttrack_path, "captions", "en-US");