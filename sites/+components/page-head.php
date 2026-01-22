<?php
	/**
	 * \file
	 * Generate preamble for current webpage.
	 */
	require_once 'php/utils/site-generation.php';
?>
<meta charset="UTF-8">
<link rel="canonical" href="<?=SiteGeneration::getCanonicalPageURL()?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/svg+xml" href="/favicon.svg" sizes="any">
<link rel="icon" type="image/ico" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" href="/apple-touch-icon.png">
<link rel="preload" href="/css/footer.css" as="style"/>
<link rel="preload" href="/css/common.css" as="style"/>
<meta
	http-equiv="Content-Security-Policy"
	content="<?php
		?>script-src 'self'; img-src 'self' data:; <?php
		?>connect-src 'self'; font-src 'none'; <?php
		?>object-src 'none'; media-src 'none'; <?php
		?>form-action 'none'; base-uri 'self'; <?php
	?>"
>
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="robots" content="index, follow">
<meta name="author" content="BlackHouse">
	<link rel="author" href="https://github.com/blkhse088">
<meta property="og:image" content="/assets/og-image.webp"/>
<meta property="og:image:width" content="1200"/>
<meta property="og:image:height" content="630"/>
<meta property="og:url" content="<?=SiteGeneration::getCanonicalPageURL()?>">
<?php
	include SiteGeneration::getOwnSubDocumentPath('head');
?>
