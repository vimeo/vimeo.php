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

// Search example using the official PHP library for the Vimeo API.
$config = require(__DIR__ . '/init.php');

if (empty($config['access_token'])) {
    throw new Exception(
        'You can not search without an access token. You can find this token on your app page, or generate one ' .
        'using auth.php'
    );
}

$lib = new Vimeo($config['client_id'], $config['client_secret'], $config['access_token']);

// Show first page of results, set the number of items to show on each page to 10, sort by relevance, show results in
// descending order, and filter only Creative Commons license videos.
$search_results = $lib->request('/videos', array(
    'page' => 1,
    'per_page' => 10,
    'query' => 'vimeo staff',
    'sort' => 'relevant',
    'direction' => 'desc',
    'filter' => 'CC'
));

print_r($search_results);
