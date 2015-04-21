<?php
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

// Show all errors and warnings
ini_set('display_errors', 'On');
error_reporting(E_ALL);

// Load the autoloader
if (file_exists('vendor/autoload.php')) {
	// Composer
	require_once('vendor/autoload.php');
} else {
	// Custom
	require_once(__DIR__ . '/../autoload.php');
}

// Load the configuration file.
if (!function_exists('json_decode')) {
    throw new Exception('We could not find json_decode. json_decode is found in php 5.2 and up, but not found on many linux systems due to licensing conflicts. If you are running ubuntu try "sudo apt-get install php5-json".');
}
$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);

if (empty($config['client_id']) || empty($config['client_secret'])) {
	throw new Exception('We could not locate your client id or client secret in "' . __DIR__ . '/config.json". Please create one, and reference config.json.example');
}

return $config;
