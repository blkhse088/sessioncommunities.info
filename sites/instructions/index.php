<?php
	/**
	 * \file
	 * Generate instructions page.
	 */

	require_once '+getenv.php';
require_once 'php/utils/site-generation.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "+components/page-head.php" ?>
	<link rel="stylesheet" href="/index.css">
	<link rel="stylesheet" href="/css/common.css">
	<title>Instructions — sessioncommunities.info</title>
	<meta name="description" content="Learn how to join Session Communities - step by step instructions for mobile phones and desktop.">
	<meta property="og:title" content="Instructions — sessioncommunities.info">
	<meta property="og:description" content="Complete guide on how to join Session Communities using QR codes or copy-paste links.">
</head>

<body>
<?php include "+components/index-header.php" ?>

<a href="/" class="non-anchorstyle">
    <?php include '+index.h1.php'; ?>
</a>

<div class="info-section">
    <h2 class="section-title">Instructions for joining Session Communities</h2>
    <div class="section-content">
        <p>There are three main ways to join new Session Communities depending on the device and method used:</p>
    </div>
</div>

<div class="info-section">
    <h2 class="section-title">Scanning QR code with mobile phone</h2>
    <div class="section-content">
        <div id="instructions-image" title="Mobile phone scanning a QR code from SessionCommunities.info."></div>
        <p><strong>Step 1:</strong> Open Sessioncommunities.info main page on computer or other browsing device and find a Community you would like to join.</p>
        <p><strong>Step 2:</strong> Click anywhere on Community name, description or language flag. This will bring out a pop-up window with the join link depicted as QR code.</p>
        <p><strong>Step 3:</strong> Open Session App in your phone and tap the round "+" button at the bottom of the screen to enter the "Start Conversation" menu.</p>
        <p><strong>Step 4:</strong> Tap "Join Community" and choose "Scan QR Code" option, then direct your phone's camera to the QR code.</p>
    </div>
</div>

<div class="info-section">
    <h2 class="section-title">Copying & pasting Community Join Link in your phone</h2>
    <div class="section-content">
        <p><strong>Step 1:</strong> Open Sessioncommunities.info main page on your phone and find a Community you would like to join.</p>
        <p><strong>Step 2:</strong> Click on the "Copy" button on the far right side of the chosen Community row.</p>
        <p><strong>Step 3:</strong> Open Session App in your phone and tap the round "+" button at the bottom of the screen to enter the "Start Conversation" menu.</p>
        <p><strong>Step 4:</strong> Tap "Join Community" and long-press "Enter Community URL" field, then choose "Paste" and click on "Join" button below.</p>
    </div>
</div>

<div class="info-section">
    <h2 class="section-title">Copying & pasting Community Join Link in your desktop app</h2>
    <div class="section-content">
        <p><strong>Step 1:</strong> Open Sessioncommunities.info main page in your web browser and find a Community you would like to join.</p>
        <p><strong>Step 2:</strong> Click on the "Copy" button on the far right side of the chosen Community row.</p>
        <p><strong>Step 3:</strong> Open your Session desktop app and tap the "+" button at the top left next to "Messages" to reveal menu options.</p>
        <p><strong>Step 4:</strong> Click "Join Community", then right click above "Enter Community URL" field and choose "Paste" or simply press Ctrl+V. Then click on "Join".</p>
    </div>
</div>

</main>

<?php include "+components/footer.php"; ?>
</body>
</html>
