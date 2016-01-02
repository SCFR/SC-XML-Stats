<?php

  Class SC_Ammo extends SC_Item {
    private $ammoBox = false;

    function __construct($item,$ammoBox=false) {
      $item = (array) $item;
      $this->ammoBox = $ammoBox;
      if($this->ammoBox) $this->itemName = $item['@attributes']['itemName'];
      else $this->itemName = $item['@attributes']['name'];

      $this->set_constructor();

      if($this->getPath()) {
        $this->XML = simplexml_load_file($this->path);
        $this->setInfos();

        if($this->ammoBox) $this->getAmmoOfBox();
      }
      else throw new Exception("NoMatchingAmmo");
    }

    function getPath() {
      global $_SETTINGS;
      $t = $this->rsearch($_SETTINGS['STARCITIZEN']['PATHS']['ammo'], "~".$this->itemName."~", "Interface");
        if($t) {
          $this->path = $t['file'];
          return true;
        }
        else return false;
    }

    function getAmmoOfBox() {
      if($this->XML->ammoBox) {
        foreach($this->XML->ammoBox->param as $param) {
          $param = (array) $param;

          if($param['@attributes']['name'] == "max_ammo_count") $this->params['max_ammo_count'] = $param['@attributes']['value'];
          elseif($param['@attributes']['name'] == "ammo_name") {
            $arr['@attributes']['name'] = $param['@attributes']['value'];
            $ammo = new SC_Ammo($arr);
            $this->params["AMMO"][] = $ammo->getInfos();
          }
        }
      }
    }

    function setInfos() {
      $this->get_stats($this->XML->params->param);
      $ar['name'] = $this->itemName;

      if(!$this->ammoBox) {
        foreach($this->XML->physics->param as $param) {
          $param = (array) $param;
          $ar[$param['@attributes']['name']] = $param['@attributes']['value'];
        }

        foreach($this->XML->params->param as $param) {
          $param = (array) $param;
          $ar[$param['@attributes']['name']] = $param['@attributes']['value'];
        }
      }

      $this->params += $ar;
    }

    function getInfos() {
      return $this->params;
    }

}

?>
