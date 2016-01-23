<?php
/**
 * Parse the Loadout and base Stats/Equipements of a given ship
 * Is reponsible for calling all required items.
 * @package SC-XML-Stats
 * @subpackage classes
 */
	Class SC_Loadout implements SC_Parser {

		/**
		 * The file name.
		 * @var string
		 */
		private $file;
		/**
		 * The main XML of shipLoadout
		 * @var SimpleXMLElement
		 */
		private $XML;
		/**
		 * The main XML of ShipImplementation
		 * @var SimpleXMLElement
		 */
		private $XMLShip;
		/**
		 * An array containing the errors
		 * @var SimpleXMLElement
		 */
		private $error;
		/**
		 * If the parse went ok.
		 * @var Boolean
		 */
		private $sucess = true;
		/**
		 * The main array in which we store the loadout/stats of the ship.
		 * @var array
		*/
		private $loadout = array();
		/**
		 * False if is not a variant otherwise contains the name of the variant.
		 * @var mixed boolean|string
		*/
		private $variantName = false;

		/**
		 * Hashmap of parts ID to parts Name
		 * @var Array
		*/
		private $idToNames;

		/**
		 * If the ships has an external patchFile or not.
		 * @param String
		 */
		 private $patchFile;

		/**
		 * Item constructor
		 * @param string $file The path of the ShipLoadout to open.
		*/
		function __construct($file) {
			try {
				$this->file = $file;
				$this->setFileName();
				$this->setXml($file);
				$this->parseLoadout();
			}
			catch(Exception $e) {
				$this->error[] = 'SELF: '.$e->getMessage();
			}

		}

		/**
     * Get the XML of a Shiploadout
		 * @param string the file path and name.
     * @throws Exception If it can't get the Shiploadout
     */
		private function setXml($file) {
			if(file_exists($file)) $this->XML = simplexml_load_file($file);
			else throw new Exception("LadoutNotExist");
		}

		/**
     * Does the parsing of the ShipImplementation
     * @throws Exception If it can't get the ShipImplementation
     */
		private function parseShip() {
			$path = $this->getShipPath();
				if($path) {
					$this->XMLShip = simplexml_load_file($path);
					$this->getImplementation();
				}
				else throw new Exception("CantFindShipImplementation");
		}

		/**
     * Recurs on every HardPoints of the base ship
		 * to get its children, and puts every one of them in a 1D array.
     * @param SimpleXMLElement $xml XML of Hardpoint to parse
     * @param Array $raw the array in which to output
     */
		private function recurHp($xml,&$raw) {
			if(isset($xml->Parts)) {
				foreach($xml->Parts->Part as $part) {
					$raw[(string) $part["name"]] = $part;
					if(isset($part["id"])) $this->idToNames[$this->idMainPart((string) $part["id"])] = (string) $part["name"];
					$this->recurHp($part,$raw);
				}
			}
		}

		/**
     * Get the types and subtypes of a given HardPoint on a ship implementation
		 * Output as an array containing types and its subtypes, indexed by types by default
		 * Or by numerical indexes if strictArray is set to true
     * @param SimpleXMLElement $hp XML of Hardpoint
     * @param Boolean $strictArray type or int indexes
     */
		private function getHpTypes($hp,$strictArray=false) {
			$types = false;
			if($hp->ItemPort->Types) {
				foreach($hp->ItemPort->Types->Type as $type) {
					$types[(string) $type["type"]] = array("type" => (string) $type["type"],"subtypes" => explode(',', (string) $type["subtypes"]));
				}
				if($strictArray) $types = array_values($types);
			}
			return $types;
		}

		/**
     * Finds out if the hardpoint is of a given type or not
		 * @param array|string $types the type(s) to test for
     * @param SimpleXMLElement $hp XML of Hardpoint
	   * @return Boolean
     */
		private function hpisOfType($types, $hp) {
			$hpTypes = $this->getHpTypes($hp);
			if(!$hpTypes) return false;
			foreach((array) $types as $type) {
				if(isset($hpTypes[$type])) return true;
			}
			return false;
		}

		/**
     * Return the base informations in ShipImplementation of a given hardpoint
     * @param SimpleXMLElement $h XML of Hardpoint
	   * @return Array
     */
		private function hpReturnInfo($h) {
			return array(
				"name"					=> (string) $h["name"],
				"minSize"				=> (int) $h->ItemPort["minsize"],
				"maxSize"				=> (int) $h->ItemPort["maxsize"],
				"displayName"		=> (string) $h->ItemPort["display_name"],
				"flags"					=> (string) $h->ItemPort["flags"],
				"requiredTags"	=> (string) $h->ItemPort["requiredTags"],
				"types"					=> $this->getHpTypes($h,true),
			);
		}

		/**
     * I have no clue why but apparently RSI is  using 12yos to code.
	 	 * idMain_Part === idMainPart in their logics...
		 * So this function does the parsing of this part.
     * @param String $name an IdMainPart or IdMain_Part
	   * @return idMainPart
     */
		function idMainPart($name) {
			if($name == "idMainPart" || $name == "idMain_Part") return "SCFRPARSEMAINPART";
			else return $name;
		}

		/**
     * Handles the new/modified Elements of a Ship Modification (= Ship Variant)
	 	 * Takes the current mod XML and the raw hardpoints and changes the needed values
     * @param SimpleXMLElement &$mod The XML of the modification
	   * @param Array the array containing the raw hardpoints
     */
		private function handleModElems(&$mod, &$raw, &$mainVehicle) {
			if(isset($mod->Elems)) {
				foreach($mod->Elems->Elem as $elem) {
					// If we're in the main vehicle
					if($elem["idRef"] == $mainVehicle["id"]) {
						$mainVehicle[(string) $elem["name"]] = (string) $elem["value"];
						continue;
					}

					// If we're not we go check in which one we are
					$id = $this->idMainPart((string) $elem["idRef"]);
					$name = isset($this->idToNames[$id]) ? $this->idToNames[$id] : false;
					// Setting up our modifications
					$elemName = (string) $elem["name"];
					$elemValue = (string) $elem["value"];

					// We want to destroy that part/hardpoint.
					if($elemName == "part" &&  $elemValue == "0") unset($raw[$name]);
					elseif($name && $raw[$name]) {
						// Otherwise we wanna tweak something on the part/hardpoint
						$raw[$name][(string) $elem["name"]] = (string) $elem["value"];
					}
				}
			}
		}

		private function recurMerge(&$xml) {
			foreach($xml->children() as $child) {
				recurMerge($child);
			}
		}


		/**
     * Opens the XML of a modification, and merges the current ShipImplementation XML
		 * with that of the Modification XML if need be.
     * @param SimpleXMLElement &$mod the XML of the mod
		 * @return Boolean if it added anything or not.
     */
		private function addNewXml(&$mod) {
			global $_SETTINGS;
			if(!isset($mod["patchFile"])) return false;
			else {
				$file = $_SETTINGS['STARCITIZEN']['scripts']."\\".$_SETTINGS['STARCITIZEN']['version'].$_SETTINGS['STARCITIZEN']['PATHS']['ship'].$mod["patchFile"].".xml";
				if(file_exists($file)) {
					$tXML = simplexml_load_file($file);
					return $tXML;
				}
				else throw new Exception("ModificationPatchFileNotExist");
			}
		}
		/**
     * Main function to parse ShipImplementation
		 * Starts of by calling { @link recurHp()} to build the array of items,
		 * get main stats of the ship, then get worthwhile hardpoints into { @link loadout}
     * @param SimpleXMLElement $h XML of Hardpoint
	   * @return Array
     */
		function getImplementation() {
			if($this->variantName) {
				$found = false;
				if(isset($this->XMLShip->Modifications)) {
					foreach($this->XMLShip->Modifications->Modification as $mod) {
						if($mod["name"] != $this->variantName) continue;
						else {
							$found = $mod;
							$tXML = $this->addNewXml($mod);
							break;
						}
					}
				}
				if($found === false || !isset($this->XMLShip->Modifications)) throw new Exception("CouldNotFinModification");
			}

				// Setting up
			$raw = array();
			if(isset($found) && isset($tXML->Parts)) $this->recurHp($tXML, $raw);
			else $this->recurHp($this->XMLShip, $raw);
			//if(isset($found) && $found !== false) $this->recurHp($tXML, $raw);

			foreach($this->XMLShip->attributes() as $name=>$value) {
				$mainVehicle[$name] = (string) $value;
			}

			$mainPart = reset($raw);
				// Handle variant
			$this->handleModElems($found, $raw, $mainVehicle);


			$tMass = 0;
				// Get HardPoints that are worthwhile;
			foreach($raw as $hpname => $hp) {

					// Skip the parts we don't want.
				if(isset($hp["skipPart"]) && (integer) $hp["skipPart"] === 1) continue;

					// Set mass
			  if(isset($hp["mass"])) {$tMass += (integer) $hp["mass"];
				}

					// Get HardPointType
				if($this->hpisOfType(array("Turret", "WeaponGun", "WeaponMissile"),$hp)) $this->loadout["HARDPOINTS"]["WEAPONS"][] = $this->hpReturnInfo($hp);
				elseif($this->hpisOfType("MainThruster",$hp)) 			$this->loadout["HARDPOINTS"]["ENGINES"][] 	= $this->hpReturnInfo($hp);
				elseif($this->hpisOfType("ManneuverThruster",$hp)) $this->loadout["HARDPOINTS"]["THRUSTERS"][] 	= $this->hpReturnInfo($hp);
				elseif($this->hpisOfType("Shield",$hp))						 $this->loadout["HARDPOINTS"]["SHIELDS"][] 		= $this->hpReturnInfo($hp);
				elseif($this->hpisOfType("Radar",$hp))						 $this->loadout["HARDPOINTS"]["RADARS"][] 			= $this->hpReturnInfo($hp);
			}


				// Get mains stats
			$mainVehicle["mass"] = $tMass;
			$this->loadout += $mainVehicle;

		}

		/**
     * Finds and sets the path of the ShipImplementation
	   * @return String|Boolean the path or false
     */
		function getShipPath() {
			global $_SETTINGS;
			$base = $_SETTINGS['STARCITIZEN']['scripts']."\\".$_SETTINGS['STARCITIZEN']['version'].$_SETTINGS['STARCITIZEN']['PATHS']['ship'];
			$file = false;

				// The ship is a base one, or has, for some reason, a base implementation as a variant.
			if(file_exists($base.$this->itemName.".xml")) $file = $base.$this->itemName.".xml";
			else {
				// The ship is a variant
					// can take TWO forms:  CONST_BASESHIP_VARIANT : easy one.
					// OR : CONST_VARIANTNAMECLOSETOBASE. (eg : 300i vs 315p)
					$t = preg_match("~^(.*)_([^_]*)$~U", $this->itemName, $match);
					if($t) {
						$this->variantName = $match[2];
							// Easy form
						if(file_exists($base.$match[1].".xml"))	$file = $base.$match[1].".xml";
						else {
								// Or hard one
							for($i = strlen($match[2]); $i >= 0; $i--) {
								if($i > 0) $try = str_split($match[2], $i);
								else $try[0] = "";
								$files = glob($base.$match[1]."_".$try[0]."*");
								if($files && sizeof($files) == 1 && file_exists($files[0])) $file = $files[0];
							}
						}
					}
				}

			return $file;
		}

		/**
     * Main function to parse ShipImplementation
		 * Starts of by calling { @link recurHp()} to build the array of items,
		 * get main stats of the ship, then get worthwhile hardpoints into { @link loadout}
     */
		function parseLoadout() {
			$this->parseShip();
			$this->parseEquipment();
		}

		/**
     * Get the shipName from the shipLoadout
		 * @throws Exception if it cannot find the name.
     */
		function setFileName() {
			$match = preg_match("~Default_Loadout_(.*).xml$~U", $this->file,$try);
			if($match) $this->itemName = $try[1];
			else throw new Exception("CantExtractLoadoutName");
		}

		/**
     * Parse all the equipement from the given ship.
		 * Gets the items from ShipLoadout, then compares to the type given by ShipImplementation
		 * And creates sub-item of given type.
		 * @throws Exception if it cant find the name.
     */
		function parseEquipment() {

				// Get all the items from the loadout.
			foreach($this->XML->Items->Item as $item) {
				$equipements[(string) $item["portName"]] = $item;
			}

				// If we did get a parse on the implementation.
			if($this->loadout && isset($this->loadout["HARDPOINTS"]) && is_array($this->loadout["HARDPOINTS"])) {
					// For each hardpoint types we parsed.
				foreach($this->loadout["HARDPOINTS"] as $hpType => $hpList) {
						// For each hardpoint in that type.
					foreach($hpList as $i => $hp) {
						$put = false;
							// If we do have an item for this hardpoint, we assign it.
						if(isset($equipements[$hp["name"]])) {
							try	{
								switch($hpType) {
									case "ENGINES":
										$s = new SC_Engine($equipements[$hp["name"]]);
									break;
									case "WEAPONS":
										$s = new SC_Weapon($equipements[$hp["name"]]);
									break;
									case "SHIELDS":
										$s = new SC_Shield($equipements[$hp["name"]]);
									break;
									case "THRUSTERS":
										$s = new SC_Thruster($equipements[$hp["name"]]);
									break;
									case "RADARS":
										$s = new SC_Radar($equipements[$hp["name"]]);
									break;
								}
								// If it was an item we wished to parse, we return its info.
								if(isset($s))	$put =	$s->returnHardpoint((string) $equipements[$hp["name"]]['portName']);
							}
							catch(Exception $e) {
									$this->error[] = $hpType." : ".$e->getMessage();
							}

							if($put) $this->loadout["HARDPOINTS"][$hpType][$i] += $put;
						}
					}
				}
			}
		}


		/**
     * Saves the whole parsed ship (@link getData()) to a json file,
		 * Requirement of SC_Parser
		 * @param string $folder the name of the folder in which to save
     */
		function saveJson($folder) {
			global $_SETTINGS;
      $path = $_SETTINGS["SOFT"]["jsonPath"].$_SETTINGS["STARCITIZEN"]["version"]."\\".$folder;
      if(!is_dir($path)) mkdir($path, 0777, true);

			file_put_contents($path.$this->itemName.".json", json_encode($this->getData()));
		}

		/**
     * Returns the errors catched.
		 * Requirement of SC_Parser
     */
		function getError() {
			return $this->error;
		}

		/**
     * Returns whever or not we succeded.
		 * Requirement of SC_Parser
     */
		function getSucess() {
			return $this->sucess;
		}

		/**
     * Returns the parsed data
		 * Requirement of SC_Parser
     */
		function getData() {
			return $this->loadout;
		}


	}
?>
