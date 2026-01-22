<?php
	/**
	 * \file
	 * Import project PHP environment variables.
	 */

	/**
	 * @var $PROJECT_ROOT
	 * Root directory of the project.
	 */
	$PROJECT_ROOT = dirname(__FILE__);

	/**
	 * @var string $PHPENV_FILENAME
	 * File comtaining PHP environment variables. Marks root folder.
	 */
	$PHPENV_FILENAME = ".phpenv.php";

	(function(){
		global $PROJECT_ROOT, $PHPENV_FILENAME;

		$root_previous = "";

		while (!file_exists("$PROJECT_ROOT/$PHPENV_FILENAME")) {
			if (
				$PROJECT_ROOT == "/" ||
				$PROJECT_ROOT == "" ||
				$PROJECT_ROOT == $root_previous
			)
				throw new RuntimeException("Could not find $PHPENV_FILENAME file.");
			$root_previous = $PROJECT_ROOT;
			$PROJECT_ROOT = dirname($PROJECT_ROOT);
		}
	})();

	require_once "$PROJECT_ROOT/$PHPENV_FILENAME";

	// set_include_path(get_include_path() . PATH_SEPARATOR . $PROJECT_ROOT);
?>
