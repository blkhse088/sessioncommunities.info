<?php
	/**
	 * \file
	 * Generate modal to hold Community details.
	 */
	
?>
<dialog id="details-modal">
	<div id="details-modal-contents">
		<div id="details-modal-close">
				&times;
		</div>
		<div id="details-modal-navigation-hint">
			(Esc to close, ← → to navigate)
		</div>
		<div id="details-modal-flow">
			<div id="details-modal-panes" data-pane="0">
				<div id="details-modal-start" class="details-modal-pane" data-pane="0">
					<div id="details-modal-title">
						<div
							id="details-modal-community-icon-wrapper"
							class="clickable"
							data-hydrate-with="has_icon:data-has-icon;icon_safety:data-icon-safety"
							title="Community icon"
						><img
							id="details-modal-community-icon"
							width="64"
							height="64"
							data-hydrate-with="icon:src;icon_safety:data-icon-safety"
							alt="Community icon"
						/></div><a
							id="details-modal-community-name"
							class="h2-like"
							data-hydrate-with="name;preview_link:href"
							title="Preview Community in browser"
							href="#"
							target="_blank"
						></a>
						<img
							id="details-modal-share-icon"
							class="feather-icon clickable enter-clicks"
							src="/assets/icons/share-2.svg"
							alt="Share"
							title="Share this Community"
							tabindex="0"
						>
					</div>
					<div id="details-modal-description">
						<span id="details-modal-description-inner" data-hydrate-with="description"></span>
					</div>
					<div id="details-modal-room-tags">
					</div>
					<gap></gap>
					<div id="details-modal-room-info">
						<div id="details-modal-language">
							Language: <span
								id="details-modal-language-flag"
								data-hydrate-with="language_flag"
							></span>
						</div>
						<div id="details-modal-users">
							Users: <span data-hydrate-with="users"></span>
						</div>
						<div id="details-modal-created">
							Created: <span data-hydrate-with="creation_datestring"></span>
						</div>
						<div id="details-modal-host">
							Server:
							<a
								title="Open server in new tab"
								data-hydrate-with="hostname;hostname:href"
								target="_blank"
								rel="noopener noreferrer"
								href="#"
							></a>
						</div>
					</div>
					<gap></gap>
					<div id="details-modal-actions">
						<button
							id="details-modal-copy-button"
							class="themed-button themed-button-primary"
							data-hydrate-with="join_link:data-href"
							title="Click here to copy this Community's join link"
						>
							Copy join link
						</button>
						<button
							id="details-modal-copy-staff-id"
							class="themed-button"
							data-hydrate-with="staff:data-staff"
							title="Copy the ping for a random staff member"
						>
							Copy mod ping
						</button>
						<button
							id="details-modal-copy-room-id"
							class="themed-button"
							data-hydrate-with="identifier:data-id"
							title="Copy this room's identifier for sessioncommunities.info"
						>
							Copy room ID
						</button>
						<button
							id="details-modal-copy-pubkey"
							class="themed-button"
							data-hydrate-with="public_key:data-pubkey"
							title="Copy this server's public key"
						>
							Copy pubkey
						</button>
						<button
							id="details-modal-share-button"
							class="themed-button themed-button"
							alt="Share"
							title="Share this Community"
						>
							Share
						</button>
					</div>
				</div>
				<gap></gap>
				<div id="details-modal-end" class="details-modal-pane" data-pane="1">
					<img
						src=""
						id="details-modal-qr-code"
						title="Community join link encoded as QR code"
						width="512"
						height="512"
						alt="QR code not available"
					>
					<div id="details-modal-qr-code-label">
						Scan QR code in Session to join
						<span
							id="details-modal-qr-code-label-name"
						>'<span data-hydrate-with="name"></span>'</span>
					</div>
				</div>
			</div>
			<div id="details-modal-pane-selection" class="hidden">
				<button
					class="details-modal-pane-button
					themed-button"
					data-pane="0"
				>← Description</button>
				<button
					class="details-modal-pane-button
					themed-button"
					data-pane="1"
				>QR Code →</button>
			</div>
		</div>
	</div>
</dialog>
