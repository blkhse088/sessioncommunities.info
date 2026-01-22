<?xml version="1.0" encoding="UTF-8"?>
<?php
	/**
	 * \file
	 * Generate domain sitemap.
	 */
	require_once '+getenv.php';

	/**
	 * Generate sitemap fragment containing page location and last modified time.
	 *
	 * Only works for pages named "index.php".
	 *
	 * @param string $rel_loc Canonical webpage location relative to webroot.
	 * @param string $changes_under_root The directory to check (source or output) to infer file modification time.
	 * Typically {@link $DOCUMENT_ROOT} to detect new versions of files with updating content
	 * and {@link $TEMPLATES_ROOT} for articles which only substantially change on source change.
	 *
	 * @return void
	 */
	function loc_lastmod(string $rel_loc, ?string $changes_under_root = null) {
		global $SITE_CANONICAL_URL, $TEMPLATES_ROOT;
		$root = $changes_under_root ?? $TEMPLATES_ROOT;
		$ext = ($root == $TEMPLATES_ROOT) ? "php" : "html";
?>
		<loc><?=$SITE_CANONICAL_URL . $rel_loc?></loc>
		<lastmod><?=date('c', filemtime("$root$rel_loc/index.$ext"))?></lastmod>
<?php
	}
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
<?=loc_lastmod("/", changes_under_root: $DOCUMENT_ROOT)?>
		<changefreq>weekly</changefreq>
		<priority>1.0</priority>
	</url>
	<url>
<?=loc_lastmod("/about/")?>
		<changefreq>monthly</changefreq>
		<priority>0.8</priority>
	</url>

	<url>
<?=loc_lastmod("/instructions/")?>
		<changefreq>monthly</changefreq>
		<priority>0.7</priority>
	</url>
	<url>
		<loc>https://lp.sessioncommunities.info/</loc>
		<changefreq>yearly</changefreq>
		<priority>0.0</priority>
	</url>
</urlset>
