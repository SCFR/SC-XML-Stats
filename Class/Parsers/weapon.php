<?php
  require_once('item.php');

  Class SC_Weapon extends SC_Item {

    protected $path;
    private $XML;
    private $type = "weapon";

    function __construct($item) {
      parent::__construct($item);

      try {
        $this->getPath();
      }
      catch (Exception $e) {
        echo "no file.";
        return false;
      }

      $this->XML = simplexml_load_file($this->path);
      $this->get_stats($this->XML->params->param);
      $this->getPortMinMaxSize();

      if(isset($this->raw['Items'])) {
        foreach($this->raw['Items'] as $subitem) {
          $subitem = (array) $subitem;
          $sub = new SC_Weapon($subitem);
          $child[] = $sub->returnHardpoint($subitem['@attributes']['portName']);
        }

        if($child) $this->children = $child;
      }

    }

    function getPath() {
      if(!$this->mountPath() && !$this->misPath() && !$this->weaponPath() && !$this->ammoPath()) throw new Exception("Aucune arme correspondante !");
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
      if(preg_match("~(Class_[0-9]*|Fixed|Gimbal)_([A-Z]*)_(.*)$~", $this->itemName, $matchs)) {
          $this->type = "weaponMount";
          $this->mountType = $matchs[1];
          $this->constructor = $matchs[2];

      $this->path = $_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['weaponMount'].$this->mountType."/".$this->itemName.".xml";
      if(!file_exists($this->path)) $this->path = $_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['weaponMount'].$this->itemName.".xml";

      return $this->returnExist();

      }
      else return false;
    }

    function weaponPath() {
      global $_SETTINGS;
      $this->path = $_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['weapon'].$this->constructor."/".$this->itemName.".xml";

      return $this->returnExist();
    }


    function misPath() {
      global $_SETTINGS;
      $this->path = $_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['weaponMissile'].$this->constructor."/".$this->itemName.".xml";
      if($this->returnExist()) $this->type = "missile";
      return $this->returnExist();
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
