<?php

/*
 * This file is part of the Vimeo API.
 *
 * (c) Vimeo <support@vimeo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

spl_autoload_register(function ($class) {
	// Make sure that the class being loaded is in the vimeo namespace
    if (substr(strtolower($class), 0, 6) !== 'vimeo\\') {
        return;
    }

    // Locate and load the file that contains the class
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
    	require($path);
    }
});
