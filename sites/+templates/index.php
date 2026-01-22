<?php
	/**
	 * \file
	 * Generate Community listing page with arbitrary list.
	 */

	require_once 'php/utils/utils.php';
	require_once 'php/utils/site-generation.php';
	require_once 'php/servers/servers-rooms.php';
	require_once '+components/tbl-communities.php';

	// Set the last-updated timestamp
	// to the time the server data file was last modified.

	/**
	 * @var int $time_modified
	 * Timestamp of last Community data fetch.
	 */
	$time_modified = filemtime($ROOMS_FILE);

	/**
	 * @var string $time_modified_str
	 * Timestamp of last Community data fetch.
	 */
	$time_modified_str = date("Y-m-d H:i:s", $time_modified);

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<?php include "+components/page-head.php" ?>

		<meta name="modified" content="<?=$time_modified_str?>">
		<meta name="timestamp" content="<?=$time_modified?>">
		<link rel="stylesheet" href="/index.css?<?=md5_file("$DOCUMENT_ROOT/index.css")?>">
		<link rel="stylesheet" href="/css/banner.css?<?=md5_file("$DOCUMENT_ROOT/css/banner.css")?>">
		<script type="module" src="/main.js?<?=md5_file("$DOCUMENT_ROOT/main.js")?>"></script>
		<link rel="modulepreload" href="/js/util.js">
		<link rel="preload" href="/servers.json" as="fetch" crossorigin="anonymous"/>
		<link rel="preload" href="/tags.json" as="fetch" crossorigin="anonymous"/>
		<link rel="help" href="/instructions/">

		<noscript>
			<style>
				.js-only {
					display: none;
				}
			</style>
		</noscript>

		<?php include "+components/communities-json-ld.php"; ?>
	</head>


	<body>
		<?php include "+components/index-header.php" ?>

		<a
			href="/"
			class="non-anchorstyle"
		><?php include SiteGeneration::getOwnSubDocumentPath('h1'); ?></a>

		<?php include "custom/site-components/issue-banner.php" ?>

		<?php include "+components/communities-search.php"; ?>

		<?php include "+components/qr-modals.php" ?>

		<?php renderCommunityRoomTable($rooms); ?>

		<gap></gap>

		<hr id="footer-divider">

		<aside id="summary" itemid="<?=$SITE_CANONICAL_URL?>" itemtype="https://schema.org/WebSite">
			<p id="server_summary">
				<?=count($room_database->rooms)?> unique Session Communities
				on <?=count($room_database->servers)?> servers have been found.

			</p>
			<p id="last_checked">
				Last checked <span id="last_checked_value" itemprop="dateModified" value="<?=$time_modified_str?>">
					<?=$time_modified_str?> (UTC)
				</span>.
			</p>
		</aside>
		<div class="info-section">
				<h2 class="section-title">What is Session Messenger?</h2>
				<div class="section-content">
					<p>
						<a href="https://getsession.org/" rel="external">Session</a>
						is a private messaging app that protects your meta-data,
						encrypts your communications, and makes sure your messaging activities
						leave no digital trail behind. <a href="/about/" title="About page">Read more.</a>
					</p>
				</div>
		</div>
		<div class="info-section">
				<h2 class="section-title">What are Session Communities?</h2>
				<div class="section-content">
					<p>
						Session Communities are public Session chat rooms accessible from within Session Messenger.
						This open source web project crawls known sources of Session Communities, and
						displays information about them as a static HTML page. <a href="/about/" title="About page">Read more.</a>
					</p>
				</div>
		</div>
		
		<div class="info-section">
				<h2 class="section-title">Disclaimer:</h2>
				<div class="section-content">
					<p>
					While reasonable attempts are made to monitor Communities and delist them where they are found to have
					content that violates Session General Terms of Service or other applicable laws,
					you should still proceed with caution. Operators of individual Communities bear all legal responsibility for hosting illegal content.
					</p>
				</div>
		</div>
		
		<?php include "+components/footer.php"; ?>

		<div id="copy-snackbar"></div>
		<div id="modal-backdrop"></div>
	</body>
</html>
