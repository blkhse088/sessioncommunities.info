<?php
	/**
	 * \file
	 * Generate webpages using static site generation.
	 */

	require_once 'getenv.php';
	require_once 'utils/getopt.php';

	/**
	 * Return file names matching the glob pattern in all subdirectories.
	 * @param string $pattern Glob pattern.
	 * @param int $flags Glob flags.
	 * @author Tony Chen
	 * @see https://stackoverflow.com/a/17161106
	 * @return string[] Array of file names.
	 */
	function rglob($pattern, $flags = 0): array {
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
			$files = array_merge(
				[],
				...[$files, rglob($dir . "/" . basename($pattern), $flags)]
			);
		}
		return $files;
	}

	/**
	 * Produce shell code to set the environment variables given.
	 *
	 * @param string[] $env_vars Dictionary of environment variables.
	 */
	function serialize_shell_environment(array $env_vars): string {
		$env_assignments = array_map(function(string $key, string $value) {
			return "$key=".escapeshellarg($value);
		}, array_keys($env_vars), array_values($env_vars));
		return implode(' ', $env_assignments);
	}

	/**
	 * Generate files from PHP templates.
	 *
	 * Templates used are in the {@link $TEMPLATES_ROOT} directory.
	 * Generated files are places in the {@link $DOCUMENT_ROOT} directory.
	 */
	function generate_files() {
		global $LOGGING_VERBOSITY, $TEMPLATES_ROOT, $DOCUMENT_ROOT;

		$flags = LoggingVerbosity::getVerbosityFlags($LOGGING_VERBOSITY)[1];

		foreach (rglob("$TEMPLATES_ROOT/*.php") as $phppath) {
			// Do not render auxiliary PHP files.
			if (str_contains("$phppath", "/+") || $phppath[0] == "+")
				continue;

			$filename = basename($phppath);
			$docpath = str_replace($TEMPLATES_ROOT, $DOCUMENT_ROOT, $phppath);
			$relpath = str_replace($TEMPLATES_ROOT, "", $phppath);
			$dirname = dirname($relpath);
			if (preg_match("/[^.]+\\.\w+\\.php$/", $filename) == 1) {
				$docpath = str_replace(".php", "", $docpath);
			} else {
				$docpath = str_replace(".php", ".html", $docpath);
			}
			$reldocpath = str_replace($DOCUMENT_ROOT, "", $docpath);

			// We do this to isolate the environment and include-once triggers,
			// otherwise we could include the documents in an ob_* wrapper.

			mkdir("$DOCUMENT_ROOT/$dirname", recursive: true);

			log_info("Generating output for $relpath.");
			$output = [];

			$exit_code = 0;

			$env_vars = [
				'SSG_TARGET' => $reldocpath,
			];

			$environment = serialize_shell_environment($env_vars);

			exec("cd '$TEMPLATES_ROOT'; $environment php '$phppath' $flags", $output, $exit_code);

			if ($exit_code != 0 || empty($output)) {
				log_error("Site generation failed.");
				exit(255);
			}

			if (str_ends_with($docpath, ".html")) {
				$output = preg_replace("/^\\s+/", "", $output);
			}

			file_put_contents($docpath, join("\n", $output));
		}

		log_info("Done generating site.");
	}

	generate_files();
?>
