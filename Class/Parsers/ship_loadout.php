<?php
require_once('../parser.class.php');

	Class SC_ship_loadout extends SC_Parser {
		
		private $file;
		private $XML_file;
		private $RELATIVE_PATH = "../";

		function __construct($file) {
			$this->file = $file;
			$this->XML_file = $this->Parse_Loadout(simplexml_load_file($file));
		}

		function Parse_Loadout($xml) {
			$xml = $this->Get_main_offs($xml);

			return $xml;
		}

		// Enlève la plupart des truc static inutiles.
		function Get_main_offs($xml) {
			unset($xml->Items->comment);

			return $xml;
		}

		function mu_print() {
			echo "<pre>";
			print_r($this->XML_file);
			echo "</pre>";
		}


	}
?>