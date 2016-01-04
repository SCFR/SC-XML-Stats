<?php
	Class SC_Loadout implements SC_Parser {

		private $file;
		private $XML_load;
		private $XML_ship;
		private $error;
		private $sucess= true;

		private $loadout;
		private $RELATIVE_PATH = "../";

		function __construct($file) {


			try {
				$this->file = $file;
				$this->setFileName();
				$this->Parse_Loadout(simplexml_load_file($file));
			}
			catch(Exception $e) {
				$this->error = $e->getMessage();
			}

		}

		function Parse_Loadout($xml) {
			$this->XML_load = $this->get_main_offs($xml);
			$this->parse_equipment();
		}

		function setFileName() {
			$file = str_replace("\\","/", $this->file);
			$file = str_replace(".xml","", $file);
			$t = explode("/", $file);

			$this->itemName = $t[sizeof($t)-1];
		}

		function parse_equipment() {
			echo "<pre>";
			foreach($this->XML_load->Items->Item as $item) {
				$item = (array) $item;
				unset($item['Pipes']);

				switch($this->getItemType($item)) {
					case "engine":
						$e = new SC_Engine($item);
						$this->loadout['ENGINES'][] = $e->returnHardpoint($item["@attributes"]['portName']);
					break;
					case "weapon":
					$put = false;
						try {
						    $s = new SC_Weapon($item);
								$put =	$s->returnHardpoint($item["@attributes"]['portName']);
						} catch (Exception $e) {
								$this->error[] = "WEAPON : ".$e->getMessage();
						}

						$this->loadout['WEAPONS'][] = $put;

					break;
					default:
					break;
				}

			//	print_r($item);
			}
			echo "</pre>";
		}

		function getItemType($item) {
			if($item["@attributes"] && $item["@attributes"]['portName']) {
				$test = preg_match("~.*_(.*)(_|$)~mU", $item["@attributes"]['portName'],$match);
				if($test) {
					if 			($match[1] == "thruster" && strpos($item["@attributes"]['portName'], "engine")	 !== FALSE) return "engine";
					elseif 	(preg_match("~[cC]lass|[gG]un|[tT]urret|[mM]issilerack~mU", $match[1])) return "weapon";
					else return $match[1];
				}
				else return "misc";
			}
			else return false;
		}

		// Enlève la plupart des truc static inutiles.
		function get_main_offs($xml) {
			unset($xml->Items->comment);

			return $xml;
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


		function getData() {
			return $this->loadout;
		}


	}
?>
