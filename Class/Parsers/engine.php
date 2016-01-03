<?php
  Class SC_Engine extends SC_Item {

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
      $t = $this->findXML("engine", $this->itemName, "Interface");
        if($t) {
          $this->path = $t['file'];
        }
        else {
          $this->OK = false;
          throw new Exception("NoMatchingEngine : ".$this->itemName);
        }
    }

    function parseEngine() {

      // Parsing pipes
      foreach($this->XML->Pipes->Pipe as $pipe) {
        $pipe = (array) $pipe;
        if($pipe["@attributes"]["class"] != "Fuel") continue;

        // Fuel consuption (ENGINE RELATED, Not QF/JUMP)
        foreach($pipe["States"]->State as $state) {
          $state = (array) $state;
          $v = (array) $state[0];

          $this->params["fuelConso"][$state["@attributes"]["state"]] = $v["@attributes"]["value"];
        }
      }

      // Parsing thrust
    $thruster = (array) $this->XML->thrusters->thruster->{0};
    $this->params["maxThrust"] = $thruster["@attributes"]["maxThrust"]; 
    }


  }

?>
