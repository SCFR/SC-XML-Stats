<?php
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

    function __construct($item) {
      $this->raw = $item;
      $this->itemName = (string) $item["itemName"];

    //  if(!$this->itemName) throw new Exception("NoObjectName");
     $this->set_constructor();
    }

    function set_constructor() {
      $t = preg_match("~^(.*)_~U", $this->itemName, $match);
      if($t) $this->constructor = $match[1];
    }

    function setItemMainStats() {
      foreach($this->XML->params->param as $value) {
        $this->params[(string) $value["name"]] = (string) $value["value"];
      }
    }

    function returnHardpoint($portName) {
      $ar = array(
          "hasChild"  => "false",
          "DEFAULT" 	=> $this->get_infos(),
      );

      if(is_array($this->children)) {$ar['hasChild'] = true; $ar['CHILDREN'] = $this->children;}
      return $ar;
    }

    function get_size() {
      return $this->params['itemSize'];
    }

    function get_infos() {
      if($this->minSize > 0 && $this->maxSize > 0) {
        $this->params['minSize'] = $this->minSize;
        $this->params['maxSize'] = $this->maxSize;
      }
      return $this->params;
    }

    function returnExist() {
      if(!$this->path || !file_exists($this->path)) return false;
      else return true;
    }

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

    function findXML($folderName, $itemName, $not=false) {
    global $_SETTINGS;
      $t = $this->rsearch($_SETTINGS['STARCITIZEN']['PATHS'][$folderName], "~".$itemName."~", $not);
      if($t) return $t;
      else return false;
    }

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

    function getData() {
      return $this->params;
    }

    function XML_OPEN($file) {
      if(file_exists($file)) return simplexml_load_file($file);
      else return false;
    }

    function saveJson($folder) {
			global $_SETTINGS;

      $path = $_SETTINGS["SOFT"]["jsonPath"].$folder;
      if(!is_dir($path)) mkdir($path, 0777, true);

			file_put_contents($path.$this->itemName.".json", json_encode($this->getData()));
		}

    function getError() {
    	return $this->error;
    }

    function getSucess() {
    	return $this->sucess;
  	}

  }

?>
