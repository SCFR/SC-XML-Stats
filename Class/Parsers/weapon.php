<?php
  require_once('item.php');
  require_once('ammo.php');

  Class SC_Weapon extends SC_Item {

    protected $path;
    private $XML;
    private $type = "weapon";
    private $ammo = false;

    function __construct($item) {
      parent::__construct($item);

      $this->getPath();

      if($this->OK) {
        $this->XML = simplexml_load_file($this->path);
        $this->get_stats($this->XML->params->param);
        $this->getPortMinMaxSize();

        $this->getSubItems();
        $this->getAmmos();
      }
    }


    function getSubItems() {
      if(isset($this->raw['Items'])) {
        foreach($this->raw['Items'] as $subitem) {
          $subitem = (array) $subitem;
          $sub = new SC_Weapon($subitem);
          $child[] = $sub->returnHardpoint($subitem['@attributes']['portName']);
        }

        if($child) $this->children = $child;
      }
    }

    function getAmmos() {
      if(isset($this->XML->ammos)) {
        foreach((array) $this->XML->ammos as $ammo) {
          $ammo = (array) $ammo;
          $sub = new SC_Ammo($ammo);

          $ammos[] = $sub->getInfos();
        }
        if($ammo) $this->ammo = $ammos;
      }
    }

    function returnHardpoint($portName) {
      $ar = parent::returnHardpoint($portName);

      if($this->ammo) $ar['DEFAULT']['AMMO'] = $this->ammo;

      return $ar;
    }

    function getPath() {
      if(!$this->mountPath() && !$this->misPath() && !$this->weaponPath() && !$this->ammoPath()) {
        $this->OK = false;
        throw new Exception("NoMatchingWeapon");
      }
    }



    function returnExist() {
      if(!file_exists($this->path)) return false;
      else return true;
    }

    function ammoPath() {
      global $_SETTINGS;
      $t = $this->rsearch($_SETTINGS['STARCITIZEN']['PATHS']['ammo'], "~".$this->itemName."~");
        if($t) {
          $this->path = $t['file'];
          return true;
        }
        else return false;
    }

    function mountPath() {
        global $_SETTINGS;
        $t = $this->rsearch($_SETTINGS['STARCITIZEN']['PATHS']['weaponMount'], "~".$this->itemName."~");
          if($t) {
            $this->path = $t['file'];
            $this->mountType = "test";
            $this->constructor = "test";
            return true;
          }
          else return false;
    }

    function weaponPath() {
      global $_SETTINGS;
      $t = $this->rsearch($_SETTINGS['STARCITIZEN']['PATHS']['weapon'], "~".$this->itemName."~");
        if($t) {
          $this->path = $t['file'];
          return true;
        }
        else return false;
    }


    function misPath() {
      global $_SETTINGS;
      $t = $this->rsearch($_SETTINGS['STARCITIZEN']['PATHS']['weaponMissile'], "~".$this->itemName."~");
      if($t) {
        $this->path = $t['file'];
        return true;
      }
      else return false;
    }

    function getPortMinMaxSize() {
      if($this->XML->portParams && $this->XML->portParams->ports->ItemPort) {
        $port = (array) $this->XML->portParams->ports->ItemPort;
        $this->minSize = $port['@attributes']['minsize'];
        $this->maxSize = $port['@attributes']['maxsize'];
      }
    }



  }
?>
