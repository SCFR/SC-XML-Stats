<?php
  require_once('../parser.class.php');

  Class SC_Item extends SC_Parser {

    protected $itemName;
    protected $constructor;
    protected $params;

    function __construct($name) {
      $this->itemName = $name;
      $this->set_constructor();
    }

    function set_constructor() {
      $t = preg_match("~^(.*)_~U", $this->itemName, $match);
      if($t) $this->constructor = $match[1];
    }

    function get_stats($params) {
      foreach($params as $param) {
        $param = ((array) $param);
        $param = $param['@attributes'];
        $this->params[$param['name']] = $param['value'];
      }
    }

    function get_size() {
      return $this->params['itemSize'];
    }


  }

?>
