<?php
	/**
	 * \file
	 * Generate about page.
	 *
	 * If you're hosting your own version of site, please make sure this file reflects any customization you've made!
	 */
	// prerequisite include for sites
	require_once '+getenv.php';
	require_once 'php/utils/site-generation.php';
		/**
	 * @var string[] $HIGHLIGHTED_FIELDS
	 * List of interactive server log entries.
	 */
	$HIGHLIGHTED_FIELDS = ["ip", "datetime", "resource", "status", "bytes", "referer", "user-agent"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include('+components/page-head.php'); ?>
	
	<meta name="description" content="Learn more about sessioncommunities.info - how it works and maintains your privacy. Learn how to get your Session Community listed and how to contact site owner.">
	<title>About — sessioncommunities.info</title>
	<meta property="og:title" content="About — sessioncommunities.info">
	<meta property="og:description" content="Session Communities are public chatrooms accessible from within Session Messenger. <?php
		?>This website provides an up-to-date list of known Session Communities, and <?php
		?>displays information about them as a static HTML page.">

	<meta property="og:type" content="article">
	<link rel="stylesheet" href="/index.css">
	<link rel="stylesheet" href="/css/common.css">
	
	<style>
		label, label a { text-decoration: underline dotted white 1px; text-underline-offset: 0.2em; }
		<?php foreach ($HIGHLIGHTED_FIELDS as $field): ?>
			#show-<?=$field?>:hover ~ :is(p, pre) :is(label[for="show-<?=$field?>"], label[for="show-<?=$field?>"] *),
		<?php endforeach; ?>
			:not(*) { color: red; }
	</style>
	
</head>
<body>
<?php include "+components/index-header.php" ?>

<a href="/" class="non-anchorstyle">
    <?php include '+index.h1.php'; ?>
</a>

<div class="info-section">
	<h2 class="section-title">What is Session and What are Session Communities?</h2>
	<div class="section-content">
		<p><a href="https://getsession.org/">Session</a> is a private, end-to-end encrypted messaging app that protects your meta-data, encrypts your communications, and makes sure your messaging activities leave no digital trail behind.</p>
		<p>Session Communities are public chatrooms accessible from within Session - Private Messenger App.</p>
	</div>
</div>

<div class="info-section">
	<h2 class="section-title">What does this site do?</h2>
	<div class="section-content">
		<p>This website routinely crawls known sources of Session Communities, and displays information about them as a static HTML page, enabling you to search, filter and obtain joining information.</p>
		<p>This project is a fork of sessioncommunities.online (developed by https://codeberg.org/gravel) as the original website is no longer available in open source format and is also known for applying extensive filtering and censorship of certain community hosts.</p>
		<p><a href="https://sessioncommunities.info">Sessioncommunities.info</a> strives to maintain an unbiased, unfiltered and uncensored list of all known Session open communities that adhere to the Content Policy laid out in the <a href="https://getsession.org/terms-of-service">Session Terms of Service (Session TOS)</a>.</p>
		<p>Sessioncommunities.info is fully open-source and available for scrutiny on <a href="https://github.com/blkhse088/sessioncommunities.info"> GitHub</a>.</p>
		<p>You are more than welcome to report bugs and suggest improvements for this site via GitHub or by contacting site owner via Session Messenger (contact details below). Also feel free to fork and run your own version of this project, helping grow Session userbase and censorship resilience.</p>
	</div>
</div>

<div class="info-section">
	<h2 class="section-title">Privacy Policy</h2>
	<div class="section-content">
		<p>This website does not use any cookies. No other website visitor data is being collected apart from automated server logs which are kept for 3 days, including a full visitor IP address.</p>

		<p>Server logs look like this: (<em>Hover for details</em>)</p>

		<?php foreach ($HIGHLIGHTED_FIELDS as $field): ?>
			<input type="checkbox" class="hidden" id="show-<?=$field?>">
		<?php endforeach; ?>

			<pre><label for="show-ip" title="De-identified IP address">155.71.106.0</label>- <label for="show-datetime" title="Time of visit">[27/Jan/2041:14:05:22 +0000]</label> <label for="show-resource" title="Requested resource and method">"GET / HTTP/2.0"</label> <br> <label for="show-status" title="Status returned by the server">200</label> <label for="show-bytes" title="Size of server response">41322</label> <label for="show-referer" title="Site which referred the user">"https://duckduckgo.com/"</label> <label for="show-user-agent" title="User Agent Header">"Mozilla/5.0 (Windows NT <br>10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) <br>Chrome/115.0.0.0 Safari/537.36"</label></pre>

		<p>In other words, they contain the visiting
				<label for="show-ip"><a target="_blank" href="https://en.wikipedia.org/wiki/IP_address">IP address</a></label>
				(semi-deidentified by setting the last octet to zero), 
				<label for="show-datetime">time of visit</label>,
				<label for="show-resource">resource requested</label> (<span class="code">/</span> stands for "main page"),
				<label for="show-status"><a target="_blank" href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes">HTTP status code</a></label>,
				<label for="show-bytes">amount of bytes transferred to the user</label>,
				<label for="show-referer"><a target="_blank" href="https://en.wikipedia.org/wiki/HTTP_referer">Referer</a></label>
				(usually the site you came from),
				and <label for="show-user-agent">
				<a target="_blank" target="_blank" href="https://en.wikipedia.org/wiki/User-Agent_header#Use_in_client_requests">User Agent</a>
				</label> (how your browser presents itself). Click on the embedded links to Wikipedia to learn more about these elements.
		</p>
	</div>
</div>

<div class="info-section">
	<h2 class="section-title">Who has access to your data</h2>
	<div class="section-content">
		<p>Only the operator of this website (Session Account ID BlackHouse) and website hosting provider <a target="_blank" href="https://liteserver.nl/general-terms-and-conditions-of-liteserver-b-v/">LiteServer B.V.</a>  have access to the data stored on the server used for running this project.</p>
	</div>
</div>

<div class="info-section">
	<h2 class="section-title">How do I get my Community listed here?</h2>
	<div class="section-content">
		<p>Submit your community to the owner of this website by reaching out to BlackHouse (Session Account ID) using Session - Private Messenger App. Your community will be reviewed and listed if it meets the below requirements:</p>

		<ul>
			<li>Communities are required to have adequate moderation ensuring their adherence to Content Policy laid out in Session TOS. A minimum of once-daily review and cleanup of every listed community is expected. Trigger templates and other tools are available to help you manage your communities against spam and other forms of misuse.</li>
			<li>Additionally, only Communities with registered DNS hostname are normally considered for listing. While it is possible to use bare IP for registering Community URL, this is often a vector for abuse and offers no protection from blocking and cyber attacks. Using a DNS hostname means that your SOGS site can be moved to a different server or ISP in the future: hostnames are easily updated to point to a new location, IP addresses are not.</li>
		</ul>

		<p>If an abuse report is received about possible breach of Session Terms of Service on a particular Community, it will be investigated and where confirmed, the affected Community will be temporarily delisted allowing a certain period for their operator to ensure adequate moderation. In cases of repeat or gross violation, permanent listing ban will be applied to the affected operator.</p>
	</div>
</div>

<div class="info-section">
	<h2 class="section-title">Contact Details</h2>
	<div class="section-content">
		<p>Please use Session - Private Messenger App to contact BlackHouse (Session Account ID, case insensitive) for listing applications, to report abuse of Session Terms of Service in any of the listed Communities, or with any other questions.</p>
	</div>
</div>

<?php include "+components/footer.php"; ?>

</body>
</html>
