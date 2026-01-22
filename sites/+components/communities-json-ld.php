<?php
	/**
	 * \file
	 * Generate structured JSON-LD site data.
	 */

	require_once '+getenv.php';
	require_once 'php/assets/room-icons.php';

	/**
	 * @var CommunityRoom[] $rooms
	 */

	/**
	 * @var array $json_ld_data
	 * Associative data about the site in JSON-LD format.
	 */
	$json_ld_data = array(
		'@context' => 'https://schema.org/',
		'@id' => $SITE_CANONICAL_URL,
		'url' => $SITE_CANONICAL_URL,
		'description' => 'The ultimate list of public groups in Session Messenger.',
		'name' => 'Session Communities List',
		'@type' => 'WebSite',
		'additionalType' => 'Collection',
		'image' => "$SITE_CANONICAL_URL/favicon.svg",
		'isAccessibleForFree' => true,
		'maintainer' => array(
			'@type' => 'Person',
			'name' => 'blackhouse',
			'sameAs' => "https://euroexit.net",
		),
		'potentialAction' => array(
			'@type' => 'SearchAction',
			'target' => "$SITE_CANONICAL_URL/#q={search_term_string}",
			'query-input' => 'required name=search_term_string'
		),
	);
?>

<script type="application/ld+json">
	<?=json_encode($json_ld_data)?>

</script>
