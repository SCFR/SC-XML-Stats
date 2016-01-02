<?php

  Class SC_Ammo extends SC_Item {
    function __construct($item) {
      $item = (array) $item;
      $this->itemName = $item['@attributes']['name'];
      $this->set_constructor();

      try {
        $this->getPath();
      }
      catch (Exception $e) {
        return false;
      }

      $this->XML = simplexml_load_file($this->path);
      $this->setInfos();

    }

    function getPath() {
      global $_SETTINGS;
      $t = $this->rsearch($_SETTINGS['STARCITIZEN']['PATHS']['ammo'], "~".$this->itemName."~");
        if($t) {
          $this->path = $t['file'];
          return true;
        }
        else return false;
    }

    function setInfos() {
      $ar['name'] = $this->itemName;
      foreach($this->XML->physics->param as $param) {
        $param = (array) $param;
        $ar[$param['@attributes']['name']] = $param['@attributes']['value'];
      }

      foreach($this->XML->params->param as $param) {
        $param = (array) $param;
        $ar[$param['@attributes']['name']] = $param['@attributes']['value'];
      }
      $this->params = $ar;
    }

    function getInfos() {
      return $this->params;
    }

}

?>
