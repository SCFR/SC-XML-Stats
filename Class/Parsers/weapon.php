<?php
  require_once('item.php');

  Class SC_Weapon extends SC_Item {

    protected $path;
    private $XML;
    private $type = "weapon";

    function __construct($name,$type) {
      parent::__construct($name);
      $this->type = $type;

      if($this->isMount()) $this->mountPath();
      else if($type == "weaponMissile") $this->misPath();
      else $this->weaponPath();

      $this->XML = simplexml_load_file($this->path);
      $this->get_stats($this->XML->params->param);
    }

    function isMount() {
      if(preg_match("~(Class_[0-9]*|Fixed|Gimbal)_([A-Z]*)_(.*)$~", $this->itemName, $matchs)) {
        $this->type = "weaponMount";
        $this->mountType = $matchs[1];
        $this->constructor = $matchs[2];

        return true;
      }
      else return false;
    }

    function mountPath() {
      global $_SETTINGS;

      $this->path = $_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['weaponMount'].$this->mountType."/".$this->itemName.".xml";
      if(!file_exists($this->path)) $this->path = $_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['weaponMount'].$this->itemName.".xml";
    }

    function weaponPath() {
      global $_SETTINGS;
      $this->path = $_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['weapon'].$this->constructor."/".$this->itemName.".xml";
    }

    function misPath() {
      global $_SETTINGS;
      $this->path = $_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['weaponMissile'].$this->constructor."/".$this->itemName.".xml";
    }




  }
?>
