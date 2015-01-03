<?php
  /**
   * Created by PhpStorm.
   * User: Carl
   * Date: 03/01/2015
   * Time: 03:20
   */

  namespace Vimeo\Cachable\Factory;

  use Vimeo;

  class FileSystemAndSessions extends Vimeo\Cachable\AbstractInterface {

    var $fileSystem = NULL;
    var $session = NULL;

    function __construct($cachableDirectory = './') {
      $this->fileSystem = new FileSystem($cachableDirectory);
      $this->session = new Sessions();
    }

    function translateToSession($in) {
      return 'fs2s' . md5($in);
    }

    function cachableInterfaceUniqueName($parts) {
      return $this->fileSystem->cachableInterfaceUniqueName($parts);
    }

    function cachableRequiresRefresh($ttl, $uniqueName) {
      //Check session first, return FALSE if session is has not expired TTL
      $r1 = $this->session->cachableRequiresRefresh($ttl, $this->translateToSession($uniqueName));
      if ($r1 === FALSE) {
        return FALSE;
      }

      //Session cache apparently failed, but does the filesystem have it?
      $r2 = $this->fileSystem->cachableRequiresRefresh($ttl, $uniqueName);
      if ($r2 === FALSE) {
        //The file system is Ok, copy the data back into session
        $this->session->cachableSetData($this->translateToSession($uniqueName), $this->fileSystem->cachableGetData($uniqueName), filemtime($uniqueName));

        return FALSE;
      }

      return TRUE;
    }

    function cachableSetData($uniqueName, $in, $newTime = NULL) {
      $this->fileSystem->cachableSetData($uniqueName, $in);
      $this->session->cachableSetData($this->translateToSession($uniqueName), $in);
    }

    function cachableGetData($uniqueName) {
      return $this->session->cachableGetData($this->translateToSession($uniqueName));
    }

  }