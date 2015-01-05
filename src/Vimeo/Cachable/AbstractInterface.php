<?php

namespace Vimeo\Cachable;

abstract class AbstractInterface {
  abstract function cachableInterfaceUniqueName($parts);
  abstract function cachableRequiresRefresh($ttl, $uniqueName);
  abstract function cachableSetData($uniqueName, $in, $newTime=NULL);
  abstract function cachableGetData($uniqueName);
}