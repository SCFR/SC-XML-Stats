<?php
Class SC_Parser {
	protected $SCRIPT_FOLDER =  "../raw/Scripts/";
	
	function __construct() {
	}

	function getAllFiles($directory,$conf=array("xml")) {
		$files = scandir($directory);

			foreach($files as $key => $file) {

				$failsafe = false;

				// Verification de la configuration perso
				foreach($conf as $needle) {

					// Si on a pas le needle perso, on coupe.
					if((strpos($file, $needle)) === FALSE) {
						unset($files[$key]);
						$failsafe = true;
					}
				}

				// Si on a pas déjà coupé, on verifie notre main.
				if(!$failsafe) {
					if(is_dir($file)) unset($files[$key]);
				}
			}

		return array_values($files);
	}
	
}
?>