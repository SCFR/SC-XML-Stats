<?php
/**
 * Default Template for a child class of SC_Item
 * @package SC-XML-Stats
 * @subpackage Templates
 */
  Class SC_xxx extends SC_Item {
    /**
     * @var string the path of the XML file for this Item.
     */
    protected $path;

    /**
     * Default constructor, calls the parent then handles loading,
     * Path finding,
     * Main Stats if needed, then custom things.
     * @param SimpleXMLElement $item the item.
     */
    function __construct($item) {
      parent::__construct($item);

      $this->setPath("xxx","Interface");

      if($this->OK && $this->returnExist($this->path)) {
        $this->XML = simplexml_load_file($this->path);
        $this->setItemMainStats();
        $this->parsexxx();

        $this->saveJson("xxx/");
      }
    }

    /**
     * A parsing method
     */
    function parsexxx() {
      // To do
    }

  }

?>
