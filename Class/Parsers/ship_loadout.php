<?php
require_once('../parser.class.php');
require_once("engine.php");
require_once("weapon.php");

	Class SC_ship_loadout extends SC_Parser {

		private $file;
		private $XML_load;
		private $XML_ship;

		private $loadout;
		private $RELATIVE_PATH = "../";

		function __construct($file) {
			$this->file = $file;
			$this->Parse_Loadout(simplexml_load_file($file));

		}

		function Parse_Loadout($xml) {
			$this->XML_load = $this->get_main_offs($xml);
			$this->parse_equipment();
		}

		function get_main_hardpoints() {

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
					case "weaponMount":
					case "weaponMissile":
						try {
						    $s = new SC_Weapon($item);
							  $put =	$s->returnHardpoint($item["@attributes"]['portName']);
						} catch (Exception $e) {
						    $put = "ERROR : ".$e->getMessage();
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
				$test = preg_match("~hardpoint_(.*)(_|$)~mU", $item["@attributes"]['portName'],$match);
				if($test) {
					if 			($match[1] == "thruster" && strpos($item["@attributes"]['portName'], "engine")	 !== FALSE) return "engine";
					elseif 	($match[1] == "weapon"	 && strpos($item["@attributes"]['portName'], "missilerack")	 !== FALSE) return "weaponMissile";
					elseif 	($match[1] == "class") return "weapon";
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

		function mu_print() {
			echo "<pre>";
			print_r($this->loadout);
			echo "</pre>";
		}
	}

	$t = new SC_ship_loadout($_SETTINGS['STARCITIZEN']['scripts']."\Loadouts\Vehicles\Default_Loadout_ANVL_Hornet_F7C.xml");

	$t->mu_print();
	//
?>
