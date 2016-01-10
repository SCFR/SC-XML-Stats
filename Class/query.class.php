<?php
require_once("settings.php");
require_once("Parsers/Parser.php");
require_once('Parsers/Item.php');
require_once('Parsers/Weapon.php');
require_once('Parsers/Ammo.php');
require_once('Parsers/Engine.php');
require_once('Parsers/Ship_loadout.php');
require_once('Parsers/Shield.php');

Class SC_Query {

  private $error;
  private $data;
  private $sucess;

  function __construct($args) {
  	$this->args = explode("/", $args);
    try {
      if($this->args[0] == "Parse") $this->parse();
      else $this->query();
    }
    catch(Exception $e) {
      $this->error = $e->getMessage();
      $this->sucess = false;
    }

    print_r(json_encode($this->result(), JSON_PRETTY_PRINT));
	}


  function result() {
    $r = array(
      "error"    => $this->error,
      "sucess"   => $this->sucess,
      "data"     => $this->data,
    );

    return $r;
  }



  function parse() {
    global $_SETTINGS;
    if(!$this->args[1]) throw new Exception("MustSpecifyType");
    elseif(!$this->args[2]) throw new Exception("MustSpecifyTarget");
    else {
      switch($this->args[1]) {
        case "Ship":
          if($this->args[2] == "*") {
            foreach (new DirectoryIterator($_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['shipLoadout']) as $file) {
              if($file->isDot() || $file->isDir()) continue;
              $s = new SC_Loadout($file->getPathname());
              $s->saveJson("ShipsLoadouts/");

              if($s->getError()) $this->error[$file->getFilename()] = $s->getError();
              $this->data[$file->getFilename()]  = $s->getData();
              $this->sucess[$file->getFilename()]  = $s->getSucess();
            }
          }
          else {
            $s = new SC_Loadout($_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['shipLoadout'].$this->args[2].".xml");
            $s->saveJson("ShipsLoadouts/");

            $this->error  = $s->getError();
            $this->data   = $s->getData();
            $this->sucess = $s->getSucess();
          }
        break;
      }
    }
  }

  function query() {
    if(!$this->args[0]) throw new Exception("MustSpecifyType");
    elseif(!$this->args[1]) throw new Exception("MustSpecifyTarget");
  }

}

?>
