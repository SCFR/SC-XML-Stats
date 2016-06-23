<?php
/**
 * Handles Engines and exclusively Engines,
 * see SC_Thruster for Thrusters
 * @package SC-XML-Stats
 * @see SC_Thruster
 * @subpackage Items
 */
  Class SC_Engine extends SC_Item {

    /**
     * Default Constructor for an SC_Engine
     * @param SimpleXMLElement $item the Item.
     */
    function __construct($item) {
      parent::__construct($item);

      $this->setPath("engine","Interface");

      if($this->OK && $this->returnExist($this->XML)) {
        $this->XML = simplexml_load_file($this->path);
        $this->declareMain();
        $this->setItemMainStats();
        $this->parseEngine();

        $this->saveJson("Engines/");
      }
    }

    /**
     * Sets defaults properties
     */
    private function declareMain() {
      $defaults = array(
        "totalMaxThrust" => 0,
      );

      $this->params = $defaults;
    }

    /**
     * Gets custom property of engines
     */
    private function parseEngine() {

      // Parsing pipes
      foreach($this->XML->Pipes->Pipe as $pipe) {
        $pipe = (array) $pipe;
        if($pipe["@attributes"]["class"] != "Fuel") continue;

        // Fuel consuption (ENGINE RELATED, Not QF/JUMP)
        foreach($pipe["States"]->State as $state) {
          $state = (array) $state;
          if(isset($state[0])) {
            $v = (array) $state[0];
            $this->params["fuelConso"][$state["@attributes"]["state"]] = $v["@attributes"]["value"];
          }
        }
      }

      // Parsing thrust
      if(isset($this->XML->thrusters)) {
        $thrusters = $this->XML->thrusters;
        foreach($thrusters as $thruster) {
          $thruster = (array) $thruster->thruster;
          $this->params["totalMaxThrust"] += (integer) $thruster["@attributes"]["maxThrust"];
          $this->params["thrusters"][] = (array) $thruster["@attributes"];
        }
      }
    }
  }
?>
