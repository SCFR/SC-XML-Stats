<?php
  Class SC_Thruster extends SC_Item {
    protected $path;
    private $XML;

    function __construct($item) {
      parent::__construct($item);

      $this->setPath();

      if($this->OK && $this->returnExist($this->XML)) {
        $this->XML = simplexml_load_file($this->path);
        $this->get_stats($this->XML->params->param);
        $this->parseEngine();

        $this->saveJson("Engines/");
      }
    }

    function setPath() {
      $t = $this->findXML("thruster", $this->itemName, "Interface");
        if($t) {
          $this->path = $t['file'];
        }
        else {
          $this->OK = false;
          throw new Exception("NoMatchingThruster : ".$this->itemName);
        }
    }

  }

?>
