<?php
/**
 * Handles ammunition and Ammobox
 * @package SC-XML-Stats
 * @subpackage Items
 */
  Class SC_Ammo extends SC_Item {
    /** @var Boolean is the current object an Ammobox Or not */
    private $ammoBox = false;
    /** @var Boolean is the parsing done. */
    private $done = false;

    /**
     * Default Constructor for an SC_Ammo
     * @param SimpleXMLElement $item the Item.
     * @param Boolean $ammoBox is this an ammobox or not, default false.
     */
    function __construct($item,$ammoBox=false) {
      parent::__construct($item);
      $this->setPath("ammo","Interface");
      $this->ammoBox = $ammoBox;

      if($this->OK && $this->returnExist($this->path))  {
        $this->XML = simplexml_load_file($this->path);
        $this->setMainAmmo();

        if($this->ammoBox) $this->setAmmoOfBox();
        $this->done = true;
      }
    }

    /**
     * Parse an AmmoBox informations
     */
    function setAmmoOfBox() {
      if($this->XML->ammoBox) {
        foreach($this->XML->ammoBox->param as $param) {
          if($param['name'] == "max_ammo_count") $this->params['max_ammo_count'] = $param['value'];
          elseif($param['name'] == "ammo_name") {
            $arr['name'] = $param['value'];
            $ammo = new SC_Ammo($arr);
            $this->params["AMMO"][] = $ammo->getData();
          }
        }
      }
    }

    /**
     * Sets the main Ammo Stats
     */
    function setMainAmmo() {
      $this->setItemMainStats();
      $ar['itemName'] = $this->itemName;

      if(!$this->ammoBox) {
        foreach($this->XML->physics->param as $param) {
          $ar[(string) $param['name']] = (string) $param['value'];
        }

        foreach($this->XML->params->param as $param) {
          $ar[(string) $param['name']] = (string) $param['value'];
        }
      }

      if($this->params) $this->params += (array) $ar;
    }

    /**
     * Returns the done property
     * @return Boolean Whever or not the parsing is done.
     */
    function isDone() {
      return $this->done;
    }

}

?>
