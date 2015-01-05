<?php

  namespace Vimeo;

  class VimeoCachable extends Vimeo {

    //Magic numbers...
    const CACHE_1_MINUTE = 60;
    const CACHE_1_HOUR = 3600;
    const CACHE_1_DAY = 86400;
    const CACHE_15_DAYS = 1296000;
    const CACHE_30_DAYS = 2592000;
    const CACHE_45_DAYS = 3888000;
    const CACHE_60_DAYS = 5184000;
    const CACHE_120_DAYS = 10368000;
    const CACHE_365_DAYS = 31536000;

    /** @var Cachable\AbstractInterface */
    private $cachableInterface = NULL;

    //Default to 60 seconds
    private $cachableDefaultTTL = 60;

    /**
     * @param int $cachableDefaultTTL
     */
    public function setDefaultTTL($cachableDefaultTTL) {
      $this->cachableDefaultTTL = $cachableDefaultTTL;
    }

    public function setCachableInterface(Cachable\AbstractInterface $instance) {
      $this->cachableInterface = $instance;
    }

    public function requestWithTTL($ttl, $url, $params = array(), $method = 'GET', $json_body = TRUE) {
      $chain = array();
      $chain[] = serialize($this->buildAuthorizationEndpoint(''));
      $chain[] = serialize($this->getToken());
      $chain[] = serialize($url);
      $chain[] = serialize($params);
      $chain[] = serialize($method);
      $chain[] = serialize($json_body);

      //Fallback to non-cachable when not assigned to an instance to use.
      if ($this->cachableInterface === NULL) {
        return parent::request($url, $params, $method, $json_body);
      }

      $uniqueNameFromCachableInterface = $this->cachableInterface->cachableInterfaceUniqueName($chain);
      if ($this->cachableInterface->cachableRequiresRefresh($ttl, $uniqueNameFromCachableInterface)) {
        $result = parent::request($url, $params, $method, $json_body);
        $this->cachableInterface->cachableSetData($uniqueNameFromCachableInterface, $result);

        return $result;
      } else {
        return $this->cachableInterface->cachableGetData($uniqueNameFromCachableInterface);
      }
    }

    public function request($url, $params = array(), $method = 'GET', $json_body = TRUE) {
      //Use the new function, with the default TTL given
      return $this->requestWithTTL($this->cachableDefaultTTL, $url, $params, $method, $json_body);
    }
  }