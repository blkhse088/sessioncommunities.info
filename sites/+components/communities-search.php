<?php
	/**
	 * \file
	 * Generate Community search bar.
	 */
?>
<div
	id="search-container"
	title="Search requires JavaScript"
>
	<div id="search">
		<input
			id="search-bar"
			disabled
			autocomplete="off"
			type="text"
			placeholder="Search for Communities"
			tabindex="0"
		>
		<div class="search-actions" id="search-action-pre">
			<img
				id="btn-clear-search"
				class="feather-icon clickable enter-clicks"
				src="/assets/icons/x.svg"
				alt="x"
				title="Clear search"
				tabindex="0"
			>
			<img
				id="btn-random-search"
				class="feather-icon clickable enter-clicks"
				src="/assets/icons/hash.svg"
				alt="⚂"
				title="Try a random search"
				tabindex="0"
			>
		</div>
		<div class="search-actions" id="search-action-post">
			<img
				id="btn-search"
				class="feather-icon search-action-inactive clickable"
				src="/assets/icons/search.svg"
				alt="🔍"
				title="Search"
			>
			<img
				id="btn-share-search"
				class="feather-icon search-action-active clickable enter-clicks"
				src="/assets/icons/share-2.svg"
				alt="Share"
				title="Share search"
				tabindex="0"
			>
		</div>
	</div>
</div>
