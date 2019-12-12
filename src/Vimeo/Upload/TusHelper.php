<?php


namespace Vimeo\Upload;



class TusHelper extends \TusPhp\Tus\Client
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
}