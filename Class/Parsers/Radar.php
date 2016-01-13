<?php
  Class SC_Radar extends SC_Item {
    protected $path;

    function __construct($item) {
      parent::__construct($item);

      $this->setPath("radar","Interface");

      if($this->OK && $this->returnExist($this->path)) {
        $this->XML = simplexml_load_file($this->path);
        $this->setItemMainStats();
        $this->parseRadar();

        $this->saveJson("Radars/");
      }
    }

    function parseRadar() {
      // To do
      foreach($this->XML->radar->param as $param) {
        $this->params[(string) $param["name"]] = (string) $param["value"];
      }
    }

  }

?>
