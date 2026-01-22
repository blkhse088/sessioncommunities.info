<?php

	/**
	 * Provides site generation context variables.
	 */
	class SiteGeneration {
		/**
		 * Get the absolute web path to the current document, omitting the final 'index.html'.
		 *
		 * @return string
		 */
		public static function getCanonicalPageURL(): string {
			global $SITE_CANONICAL_URL;

			return dirname($SITE_CANONICAL_URL.getenv('SSG_TARGET')) . '/';
		}

		/**
		 * Get the absolute source path of the current document.
		 *
		 * @return string
		 */
		public static function getAbsoluteSourceDocumentPath(): string {
			return $_SERVER['SCRIPT_NAME'];
		}

		/**
		 * Get the relative web path of the current document.
		 *
		 * @return string
		 */
		public static function getTargetDocumentPath(): string {
			return getenv('SSG_TARGET');
		}

		/**
		 * Get the directory above the current document's web location.
		 *
		 * Returns the path to the directory in which the current document
		 * will be served, relative to the webroot.
		 *
		 * Usage:
		 * ```php
		 * // Generating /index.php
		 * SiteGeneration::getTargetDocumentRoute() // -> '/'
		 *
		 * // Generating /privacy/index.php
		 * SiteGeneration::getTargetDocumentRoute() // -> '/privacy'
		 * ```
		 *
		 * @return string Path to the directory serving the current document.
		 */
		public static function getTargetDocumentRoute(): string {
			return dirname(SiteGeneration::getTargetDocumentPath());
		}

		/**
		 * Return the path of a subdocument of the current document.
		 *
		 * When generating "index.php", this function will return
		 * "+index.head.php" when given a subdocument identifier of "head".;
		 *
		 * @param string $subdocument Subdocument identifier.
		 * @return string Absolute source path of subdocument "+current.subdocument.php"
		 */
		public static function getOwnSubDocumentPath(string $subdocument) {
			$page = SiteGeneration::getAbsoluteSourceDocumentPath();
			$sub_document = dirname($page) . '/+' . preg_replace('/[.]php$/', ".$subdocument.php", basename($page));
			return $sub_document;
		}
	}

?>
