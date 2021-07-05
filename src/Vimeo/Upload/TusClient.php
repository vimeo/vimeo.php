<?php

namespace Vimeo\Upload;

use TusPhp\Config;

class TusClient extends \TusPhp\Tus\Client
{
    /**
     * Sets the url for retrieving the TUS upload.
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Sets the FileStore configuration
     * @param string|array $config Configuration array, or a path to configuration file, e.g.: vendor/ankitpokhrel/tus-php/src/Config/client.php
     * @return $this
     */
    public function setConfig($config)
    {
        Config::set($config, true);
        $this->setCache('file');
        return $this;
    }
}