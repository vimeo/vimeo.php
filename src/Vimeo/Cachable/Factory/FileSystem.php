<?php
  /**
   * Created by PhpStorm.
   * User: Carl
   * Date: 03/01/2015
   * Time: 03:20
   */

  namespace Vimeo\Cachable\Factory;

  use Vimeo;

  class FileSystem extends Vimeo\Cachable\AbstractInterface {

    var $cacheDirectory = './';

    function __construct($cacheDirectory = './') {
      $this->cacheDirectory = $cacheDirectory;
      if(!is_dir($this->cacheDirectory)) {
        throw new \Exception('Please create the requested cache directory and make sure it is writable.');
      }
    }

    function cachableInterfaceUniqueName($parts) {
      return $this->cacheDirectory . DIRECTORY_SEPARATOR . md5(implode('++', $parts)) . '.cache';
    }

    function cachableRequiresRefresh($ttl, $uniqueName) {
      if (file_exists($uniqueName)) {
        $t = filemtime($uniqueName);
        /** @noinspection PhpWrongStringConcatenationInspection */
        if ($t + $ttl < time()) {
          return TRUE;
        }

        return FALSE;
      }

      return TRUE;
    }

    function cachableSetData($uniqueName, $in, $newTime = NULL) {
      file_put_contents($uniqueName, serialize($in));
    }

    function cachableGetData($uniqueName) {
      return unserialize(file_get_contents($uniqueName));
    }
  }