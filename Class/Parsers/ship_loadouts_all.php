<?php
	require_once("ship_loadout.php");

	error_reporting(-1);

	Class SC_ship_loadout_all extends SC_Parser {

		protected $RAW_XML_SHIPS ="a";


		function __construct() {
			$raw_loadouts = $this->getAllFiles("../../raw/Scripts/Loadouts/Vehicles/",array("xml","Loadout"));

			$this->get_alls($raw_loadouts);

			echo "<pre>";
			print_r($this->RAW_XML_SHIPS);
			echo "</pre>";

		}

		function get_alls($files) {
			foreach($files as $file) {
				$ships[] = new SC_ship_loadout("../../raw/Scripts/Loadouts/Vehicles/".$file);
			}

			$this->RAW_XML_SHIPS = $ships;
		}

	}


	$test = new SC_ship_loadout_all();
?>