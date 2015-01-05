<?php
  /**
   * Created by PhpStorm.
   * User: Carl
   * Date: 03/01/2015
   * Time: 03:20
   */

  namespace Vimeo\Cachable\Factory;

  use Vimeo;

  class Sessions extends Vimeo\Cachable\AbstractInterface {

    const SESSION_KEY_CORE = 'vimeoCachableData';
    const SESSION_KEY_LAST_SAVED = 'vimeoLastSaved';
    const SESSION_KEY_STORAGE = 'vimeoStorage';

    function __construct() {
      //Sanity Checks
      if (!isset($_SESSION[static::SESSION_KEY_CORE])) {
        $_SESSION[static::SESSION_KEY_CORE] = array(static::SESSION_KEY_LAST_SAVED => array(), static::SESSION_KEY_STORAGE => array());
      }
      if (!is_array($_SESSION[static::SESSION_KEY_CORE])) {
        $_SESSION[static::SESSION_KEY_CORE] = array(static::SESSION_KEY_LAST_SAVED => array(), static::SESSION_KEY_STORAGE => array());
      }
      if (!isset($_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_LAST_SAVED])) {
        $_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_LAST_SAVED] = array();
      }
      if (!isset($_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_STORAGE])) {
        $_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_STORAGE] = array();
      }
    }

    function cachableInterfaceUniqueName($parts) {
      return 'vimeo' . md5(implode('++', $parts));
    }

    function cachableRequiresRefresh($ttl, $uniqueName) {
      if (!isset($_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_STORAGE][$uniqueName])) return TRUE;
      if (!isset($_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_LAST_SAVED][$uniqueName])) return TRUE;
      if ($_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_LAST_SAVED][$uniqueName] + $ttl < time()) return TRUE;

      return FALSE;
    }

    function cachableSetData($uniqueName, $in, $newTime = NULL) {
      $_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_STORAGE][$uniqueName] = $in;
      $_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_LAST_SAVED][$uniqueName] = ($newTime === NULL) ? time() : $newTime;
    }

    function cachableGetData($uniqueName) {
      return $_SESSION[static::SESSION_KEY_CORE][static::SESSION_KEY_STORAGE][$uniqueName];
    }
  }