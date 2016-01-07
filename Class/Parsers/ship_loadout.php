<?php
	Class SC_Loadout implements SC_Parser {

		private $file;
		private $XML;
		private $XMLShip;
		private $error;
		private $sucess= true;
		private $hardpoints;

		private $loadout;
		private $RELATIVE_PATH = "../";

		function __construct($file) {
			try {
				$this->file = $file;
				$this->setFileName();
				$this->parseLoadout(simplexml_load_file($file));
			}
			catch(Exception $e) {
				$this->error = $e->getMessage();
			}

		}

		function parseShip() {
			$path = $this->getShipPath();
				if($path) {
					$this->XMLShip = simplexml_load_file($path);
					$this->getHardPointsInfo();
				}
				else throw new Exception("CantGetBaseShip");
		}



		function getHardPointsInfo() {
				// Setting up
			$raw = array();
			function recurHp($xml,&$raw) {
				if($xml->Parts) {
					foreach($xml->Parts->Part as $part) {
						$raw[(string) $part["name"]] = $part;
						recurHp($part,$raw);
					}
				}
			}

			recurHp($this->XMLShip, $raw);
			foreach($this->XMLShip->attributes() as $name=>$value) {
				$mainVehicle[$name] = (string) $value;
			}
			$this->loadout += $mainVehicle;

				// Get mains stats
			$mainPart = reset($raw);

			function issetHard($value) {
				return isset($value) === true ? $value : "";
			}
				// Get every hardpoints
			foreach($this->loadout['HARDPOINTS'] as $t=>$type) {
				foreach($type as $i=>$hardpoint) {
					if(isset($raw[$hardpoint["hardpoint"]])) {
						$h = $raw[$hardpoint["hardpoint"]];


						$this->loadout['HARDPOINTS'][$t][$i] += array(
							"minSize"				=> issetHard((int) $h->ItemPort["minsize"]["minsize"]),
							"maxSize"				=> issetHard((int) $h->ItemPort["maxsize"]),
							"displayName"		=> issetHard((string) $h->ItemPort["display_name"]),
							"flags"					=> issetHard((string) $h->ItemPort["flags"]),
							"requiredTags"	=> issetHard((string) $h->ItemPort["requiredTags"]),
							"type"					=> explode(',', (string) $h->ItemPort->Types->Type["type"]),
							"subtypes"			=> explode(',', (string) $h->ItemPort->Types->Type["subtypes"]),
						);
					}
					else throw new Exception("NoMatchingHardPoint");
				}
			}
		}

		function getShipPath() {
			global $_SETTINGS;
			$base = $_SETTINGS['STARCITIZEN']['scripts'].$_SETTINGS['STARCITIZEN']['PATHS']['ship'];
			$file = false;

				// The ship is a base one, or has, for some reason, a base implementation as a variant.
			if(file_exists($base.$this->itemName.".xml")) $file = $base.$this->itemName.".xml";
			else {
				// The ship is a variant
					// can take TWO forms:  CONST_BASESHIP_VARIANT : easy one.
					// OR : CONST_VARIANTNAMECLOSETOBASE. (eg : 300i vs 315p)
					$t = preg_match("~^(.*)_([^_]*)$~U", $this->itemName, $match);
					if($t) {
							// Easy form
						if(file_exists($base.$match[1].".xml")) $file = $base.$match[1].".xml";
						else {
								// Or hard one
							for($i = strlen($match[2]); $i > 0; $i--) {
								$try = str_split($match[2], $i);
								$files = glob($base.$match[1]."_".$try[0]."*");
								if($files && sizeof($files) == 1 && file_exists($files[0])) $file = $files[0];
							}
						}
					}
				}

			return $file;
		}

		function parseLoadout($xml) {
			$this->XML = $this->get_main_offs($xml);

			$this->parseEquipment();
			$this->parseShip();
		}

		function setFileName() {
			$match = preg_match("~Default_Loadout_(.*).xml$~U", $this->file,$try);
			if($match) $this->itemName = $try[1];
			else throw new Exception("CantExtractLoadoutName");
		}

		function parseEquipment() {
			echo "<pre>";
			foreach($this->XML->Items->Item as $item) {
				$item = (array) $item;

				switch($this->getItemType($item)) {
					case "engine":
						$e = new SC_Engine($item);
						$this->loadout['HARDPOINTS']['ENGINES'][] = $e->returnHardpoint($item["@attributes"]['portName']);
						$this->hardpoints[] = $item["@attributes"]['portName'];
					break;
					case "weapon":
					$put = false;
						try {
						    $s = new SC_Weapon($item);
								$put =	$s->returnHardpoint($item["@attributes"]['portName']);
						} catch (Exception $e) {
								$this->error[] = "WEAPON : ".$e->getMessage();
						}
						$this->loadout['HARDPOINTS']['WEAPONS'][] = $put;
						$this->hardpoints[] = $item["@attributes"]['portName'];
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
			$needle =  $item["@attributes"]['portName'];
			if (preg_match("~[cC]lass|[gG]un|[tT]urret|[mM]issilerack~mU", $needle)) return "weapon";
			elseif(preg_match("~[eE]ngine~U", $needle)) return "engine";
			elseif(preg_match("~[Tt]hruster~U", $needle)) return "thruster";
			else return "misc";
			}
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
