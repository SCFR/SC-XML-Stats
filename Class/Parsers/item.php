<?php
/**
 * Parent class of all Ship Items
 * @package SC-XML-Stats
 * @subpackage classes
 */

  Class SC_Item implements SC_Parser {

    protected $itemName;
    protected $constructor;
    protected $params;
    protected $children;
    protected $XML;
    protected $minSize = 0;
    protected $path = false;
    protected $OK = true;
    private $error;

    /**
     * Main constructor, takes an item, sets his name and constructor
     * @param SimpleXMLElement $item the item to parse.
     */
    function __construct($item) {
      $this->raw = $item;
      $this->itemName = (string) $item["itemName"];
      $this->set_constructor();
    }

    /**
     * Sets the constructor name from the file name.
     */
    function set_constructor() {
      $t = preg_match("~^(.*)_~U", $this->itemName, $match);
      if($t) $this->constructor = $match[1];
    }

    /**
     * Main method to get the bases stats of any item.
     * Parses xml->params into (@link $this->params)
     */
    function setItemMainStats() {
      if($this->XML && $this->XML->params) {
        foreach($this->XML->params->param as $value) {
          $this->params[(string) $value["name"]] = (string) $value["value"];
        }
      }
    }

    /**
     * Main method to return the given item inside an hardpoint
     * @param string $portName the name of the hardpoint (not item)
     * @return an array contening the default item and its children if any.
     */
    function returnHardpoint($portName) {
      $ar = array(
          "hasChild"  => "false",
          "DEFAULT" 	=> $this->get_infos(),
      );

      if(is_array($this->children)) {$ar['hasChild'] = true; $ar['CHILDREN'] = $this->children;}
      return $ar;
    }

    /**
     * Returns the size of the item.
     * @return int the size of the item.
     */
    function get_size() {
      return $this->params['itemSize'];
    }

    /**
     * Returns main informations about the item
     * Mainly (@link $this->params) with a few tweaks.
     * @return array the informations.
     */
    function get_infos() {
      if($this->minSize > 0 && $this->maxSize > 0) {
        $this->params['minSize'] = $this->minSize;
        $this->params['maxSize'] = $this->maxSize;
      }
      return $this->params;
    }

    /**
     * Main function for deep searching of a file.
     * Will check in every subfolder of the given $folder, to find a $pattern
     * Will exclude files containing $not.
     * @param string $folder the name of the folder
     * @param string $pattern the regex for the pattern
     * @param mixed Boolean|string $not (default false) whever to ignore certain files
     * @return mixed false|array the infos of the file(s) if found or false.
     */
    function rsearch($folder, $pattern, $not=false) {
      global $_SETTINGS;
      $fileInfo = false;
      $folder = $_SETTINGS['STARCITIZEN']['scripts'].$folder;
      $dir = new RecursiveDirectoryIterator($folder);
      $ite = new RecursiveIteratorIterator($dir);
      $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
      $fileList = array();
      foreach($files as $file) {
        if($not && (strpos($file[0], $not) !== FALSE || strpos($ite->getSubPath(), $not) !== FALSE)) continue;
        $fileInfo = array();
          $fileInfo["fileName"] = $file[0].".xml";
          $fileInfo['sub'] = $ite->getSubPath().'/';
          $fileInfo['file'] = $folder.$fileInfo['sub'].$fileInfo['fileName'];
      }
      return $fileInfo;
    }

    /**
     * Prepares and excecute an xml file search.
     * Will call (@link rsearch) with specifics for an xml search
     * @param string $folderName the name of the folder/itemType
     * @param string $itemName the name of the item we're looking for
     * @param mixed Boolean|string $not (default false) whever to ignore certain files
     * @return mixed false|array the infos of the file(s) if found or false.
     */
    function findXML($folderName, $itemName, $not=false) {
    global $_SETTINGS;
      $t = $this->rsearch($_SETTINGS['STARCITIZEN']['PATHS'][$folderName], "~".$itemName."~", $not);
      if($t) return $t;
      else return false;
    }

    /**
     * Sets the path for this item.
     * Will call (@link findXML) which will in turn calls (@link rsearch)
     * Sets the (@link $this->path) if a file is found.
     * throws an exception and sets (@link $this->OK) to false if not.
     * @param string $type the name of the folder/itemType
     * @param mixed Boolean|string $not (default false) whever to ignore certain files
     * @throws Exception NoMatching$type if no file is found.
     */
    function setPath($type, $not=false) {
      $t = $this->findXML($type, $this->itemName, $not);
        if($t) {
          $this->path = $t['file'];
        }
        else {
          $this->OK = false;
          throw new Exception("NoMatching.".ucfirst($type)." : ".$this->itemName);
        }
    }

    /**
     * Returns the parsed data
		 * Requirement of SC_Parser
     */
    function getData() {
      return $this->params;
    }

    /**
     * Saves (@link getData()) to a json file.
		 * Requirement of SC_Parser
     * @param string $folder the folder name.
     */
    function saveJson($folder) {
			global $_SETTINGS;

      $path = $_SETTINGS["SOFT"]["jsonPath"].$folder;
      if(!is_dir($path)) mkdir($path, 0777, true);

			file_put_contents($path.$this->itemName.".json", json_encode($this->getData()));
		}

    /**
     * Returns the errors
		 * Requirement of SC_Parser
     * @return mixed boolean|array false or the array of errors. 
     */
    function getError() {
    	return $this->error;
    }

    /**
     * Returns the sucess
     * Requirement of SC_Parser
     * @return boolean sucess or not in parsing.
     */
    function getSucess() {
    	return $this->sucess;
  	}

  }

?>
