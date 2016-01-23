<?php
/**
 * Parent class of all Ship Items
 * @package SC-XML-Stats
 * @subpackage classes
 */

  Class SC_Item implements SC_Parser {

    /** @var String the name of the current Item */
    protected $itemName;
    /** @var String The name of the constructor (usually 4 letters AEGS|ANVL...) */
    protected $constructor;
    /** @var Array The main array containing the parameters of the current Item */
    protected $params;
    /** @var Array An Array of params from the children of current Item */
    protected $children;
    /** @var SimpleXMLElement The main XML for this Item */
    protected $XML;
    /** @var Int the minSize of the harpoint for this Item */
    protected $minSize = 0;
    /** @var false|String the Path to the XML file */
    protected $path = false;
    /** @var Boolean Whever the parsing is okay or not */
    protected $OK = true;
    /** @var false|array An array containing parsing errors, or false. */
    private $error;

    /**
     * Main constructor, takes an item, sets his name and constructor
     * @param SimpleXMLElement $item the item to parse.
     */
    function __construct($item) {
      $this->raw = $item;

        if(isset($item["itemName"])) $this->itemName = (string) $item["itemName"];
        elseif(isset($item["name"])) $this->itemName = (string) $item["name"];
        else throw new Exception("NoNameFound!");

      $this->set_constructor();
    }

    /**
     * Sets the constructor name from the file name.
     */
    protected function set_constructor() {
      $t = preg_match("~^(.*)_~U", $this->itemName, $match);
      if($t) $this->constructor = $match[1];
    }

    /**
     * Main method to get the bases stats of any item.
     * Parses xml->params into (@link $this->params)
     */
    protected function setItemMainStats() {
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
    public function returnHardpoint($portName) {
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
    public function get_size() {
      return $this->params['itemSize'];
    }

    /**
     * Returns main informations about the item
     * Mainly (@link $this->params) with a few tweaks.
     * @return array the informations.
     */
    public function get_infos() {
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
    private function rsearch($folder, $pattern, $not=false) {
      global $_SETTINGS;
      $fileInfo = false;
      $folder = $_SETTINGS['STARCITIZEN']['scripts']."\\".$_SETTINGS['STARCITIZEN']['version'].$folder;
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
    protected function findXML($folderName, $itemName, $not=false) {
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
    protected function setPath($type, $not=false) {
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
     * Check Wheter the current path exists or not
     * @return boolean true if exists, false otherwise.
     */
    protected function returnExist() {
      if(!$this->path || !file_exists($this->path)) return false;
      else return true;
    }

    /**
     * Check Wheter a file exist and opens it.
		 * @param string $file the path of the file
     * @return boolean|SimpleXMLElement false or the parsed XML file.
     */
    protected function XML_OPEN($file) {
      if(file_exists($file)) return simplexml_load_file($file);
      else return false;
    }

    /**
     * Returns the parsed data
		 * Requirement of SC_Parser
     */
    public function getData() {
      return $this->params;
    }

    /**
     * Saves (@link getData()) to a json file.
		 * Requirement of SC_Parser
     * @param string $folder the folder name.
     */
    public function saveJson($folder) {
			global $_SETTINGS;

      $path = $_SETTINGS["SOFT"]["jsonPath"].$_SETTINGS["STARCITIZEN"]["version"]."\\".$folder;
      if(!is_dir($path)) mkdir($path, 0777, true);

			file_put_contents($path.$this->itemName.".json", json_encode($this->getData()));
		}

    /**
     * Returns the errors
		 * Requirement of SC_Parser
     * @return mixed boolean|array false or the array of errors.
     */
    public function getError() {
    	return $this->error;
    }

    /**
     * Returns the sucess
     * Requirement of SC_Parser
     * @return boolean sucess or not in parsing.
     */
    public function getSucess() {
    	return $this->sucess;
  	}

  }

?>
