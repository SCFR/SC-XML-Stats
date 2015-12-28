<?php
require_once('../parser.class.php');
require_once("engine.php");

	Class SC_ship_loadout extends SC_Parser {

		private $file;
		private $XML_file;
		private $loadout;
		private $RELATIVE_PATH = "../";

		function __construct($file) {
			$this->file = $file;
			$this->Parse_Loadout(simplexml_load_file($file));
		}

		function Parse_Loadout($xml) {
			$this->XML_file = $this->get_main_offs($xml);
			$this->parse_equipment();
		}

		function parse_equipment() {
			echo "<pre>";
			foreach($this->XML_file->Items->Item as $item) {
				$item = (array) $item;

				switch($this->getItemType($item)) {
					case "engine":
						$e = new SC_Engine($item["@attributes"]["itemName"]);
						$base_infos = array(
							"hardpoint" => $item["@attributes"]["portName"],
							"size"			=> $e->get_size(),
							"DEFAULT" 	=> $e->get_infos()
						);
						$this->loadout['ENGINES'][] = $base_infos;
					break;
				}

			//	print_r($item);
			}
			echo "</pre>";
		}

		function getItemType($item) {
			if($item["@attributes"] && $item["@attributes"]['portName']) {
				$test = preg_match("~hardpoint_(.*)(_|$)~mU", $item["@attributes"]['portName'],$match);

				if($test) return $match[1];
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

	$t = new SC_ship_loadout($_SETTINGS['STARCITIZEN']['scripts']."\Loadouts\Vehicles\Default_Loadout_AEGS_Gladius.xml");

	$t->mu_print();
	//
?>
