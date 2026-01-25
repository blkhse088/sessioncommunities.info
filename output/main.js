// Hello reader!
// This project can be found at:
// https://github.com/blkhse088/sessioncommunities.info

/**
 * This JavaScript file uses the JSDoc commenting style.
 * Learn more: https://jsdoc.app/
 */

// Nudge TypeScript plugins to type-check using JSDoc comments.
// @ts-check

// Early prevention for bugs introduced by lazy coding.
'use strict';

// Import magic numbers and data
import {
	dom, COMPARISON, ATTRIBUTES,
	columnAscendingByDefault, columnIsSortable, COLUMN_TRANSFORMATION,
	element, JOIN_URL_PASTE, communityQRCodeURL, STAFF_ID_PASTE, IDENTIFIER_PASTE, DETAILS_LINK_PASTE, CLASSES, flagToLanguageAscii, RoomInfo, unreachable, onInteractive,
	PUBKEY_PASTE
} from './js/util.js';

/**
 * Hanging reference to preloaded images to avoid garbage collection.
 */
let preloadedImages = [];

/**
 * Community ID currently displayed by modal.
 */
let shownCommunityId = "";

/**
 * Create an interactive version of the Community join link.
 * @returns {HTMLElement}
 */
const transformJoinURL = () => {
	return element.button({
		textContent: "COPY",
		className: "copy_button",
		title: "Click here to copy the join link",
		onclick: function () {
			if (!(this instanceof HTMLButtonElement)) throw new Error("Not a button");
			copyToClipboard(
				dom.row_info(
					this.closest(".room-row")
					?? unreachable("No row parent found for button")
				).join_link
			);
		}
	});
}

/**
 * Fetches the last modification timestamp from the DOM.
 * @returns {?number}
 */
function getTimestamp() {
	const timestampRaw = dom.meta_timestamp()
		?.getAttribute('content');
	if (!timestampRaw) return null;
	const timestamp = parseInt(timestampRaw);
	if (Number.isNaN(timestamp)) return null;
	return timestamp;
}

/**
 * Processes initial URL hash and parameter to trigger actions on the page.
 */
function reactToURLParameters() {
	const rawHash = location.hash;
	if (rawHash == "") return;

	const hash = decodeURIComponent(rawHash.slice(1));

	if (hash.startsWith("q=")) {
		useSearchTerm(decodeURIComponent(hash.slice(2)), { fillSearchBarWithTerm: true });
		return;
	}

	if (!hash.includes("+") && !document.querySelector(`#${hash}`)) {
		useSearchTerm(`#${hash}`, { fillSearchBarWithTerm: true });
		return;
	}

	const communityIDPrefix = hash;
	const row = dom.community_row(communityIDPrefix, true);
	if (row == null || !(row instanceof HTMLTableRowElement)) {
		return;
	}

	const communityID = dom.row_info(row).identifier;
	if (communityID == null) { throw new Error("Unreachable"); }

	// manual scrolling to prevent jumping after every modal open

	row.scrollIntoView({
		behavior: "smooth"
	});

	try {
		displayQRModal(communityID);
	} catch (e) {
		console.error("Could not navigate to community " + communityID);
		console.error(e);
	}
}

function addInformativeInteractions() {
	const moreSitesInfoButton = document.getElementById('more-sites-info-button');
	moreSitesInfoButton?.addEventListener('click', () => {
		alert(
			`Lokinet Gitea and session.directory compile lists of
			Session Closed Groups and Communities, and are linked
			in recognition of their importance.
			However, sessioncommunities.info already includes Communities
			from these sources on this page.
			`.replace(/\s+/g, " ").trim()
		);
	});
}

function enableEnterClicks() {
	Array.from(document.querySelectorAll('.enter-clicks')).forEach(element => {
		if (!(element instanceof HTMLElement)) return;
		element.addEventListener('keydown', (/** @type {KeyboardEvent} */ ev) => {
			if (ev.key == "Enter") {
				if (!(ev.currentTarget instanceof HTMLElement)) {
					console.error(".enter-clicks could not find its currentTarget");
				} else ev.currentTarget.click();
			}
		})
	})
}



/**
 * Triggers all actions dependent on page load.
 */
async function onLoad() {
	const timestamp = getTimestamp();
	if (timestamp !== null) {
		setLastChecked(timestamp);
	}
	initializeSearch();
	createJoinLinkUI();
	markSortableColumns();
	addQRModalHandlers();
	addStickyHeaderHandler();
	preloadImages();
	setInterval(() => {
		preloadImages();
	}, 60 * 60E3);
	addInformativeInteractions();
	enableEnterClicks();
	await RoomInfo.fetchRooms();
	reactToURLParameters();
	addServerIconInteractions();
	addSearchInteractions();
}

/**
 * Construct room tag DOM from its description.
 * @param {Object} param0
 * @param {string} param0.text Tag name
 * @param {string} param0.type Tag classification
 * @param {string} param0.description Tag details
 * @returns HTMLElement
 */
const tagBody = ({text, type, description = ""}) => element.span({
	textContent: text.slice(0, 16),
	className: `tag tag-${type} badge`,
	title: description || `Tag: ${text}`
});

/**
 * Shows the details modal hydrated with the given community's details.
 * @param {string} communityID
 * @param {number} pane Pane number to display in modal
 */
function displayQRModal(communityID, pane = 0) {
	const modal = dom.details_modal();

	if (!modal) {
		throw new DOMException("Modal element not found.");
	}

	const row = dom.community_row(communityID);

	if (!row) {
		throw new DOMException("Community row not found.");
	}

	shownCommunityId = communityID;

	const rowInfo = dom.row_info(row);

	for (const element of modal.querySelectorAll(`[${ATTRIBUTES.HYDRATION.CONTENT}]`)) {
		const attributes = element.getAttribute(ATTRIBUTES.HYDRATION.CONTENT);
		if (!attributes) continue;
		for (const attribute of attributes.split(';')) {
			const [property, targetProperty] = attribute.includes(':')
				? attribute.split(":")
				: [attribute, 'textContent'];
			if (!Object.getOwnPropertyNames(rowInfo).includes(property)) {
				console.error(`Unknown rowInfo property: ${property}`);
				continue;
			}
			if (targetProperty === 'textContent') {
				element.textContent = rowInfo[property];
			} else {
				element.setAttribute(targetProperty, rowInfo[property]);
			}
		}
	}

	const tagContainer = dom.details_modal_tag_container();

	if (tagContainer) {

		tagContainer.innerHTML = "";

		tagContainer.append(
			...rowInfo.tags.map(tag => tagBody(tag))
		);

	} else console.error(`Could not find tag container for ${communityID}`);

	const qrCode = dom.details_modal_qr_code();
	if (qrCode instanceof HTMLImageElement) {
		// Prevent old content flashing
		qrCode.src = "";
		qrCode.src = communityQRCodeURL(communityID);
	} else console.error(`Could not find QR code <img>`);

	document.getElementById('details-modal-panes')?.setAttribute('data-pane', `${pane}`);

	location.hash=`#${communityID}`;

	modal.showModal();
}

/**
 * Hides the Community details modal.
 */
function hideQRModal() {
	const detailsModal = dom.details_modal();
	if (detailsModal) detailsModal.close(); else console.error("hideQRModal(): detailsModal is null")
	shownCommunityId = "";
}

/**
 * Adds handlers for details modal-related actions.
 */
function addQRModalHandlers() {
	const rows = dom.tbl_communities_content_rows();
	if (!rows) throw new Error("Rows not found");

	// Ways to open the QR Modal

	for (const row of rows) {
		const communityID = row.getAttribute(ATTRIBUTES.ROW.IDENTIFIER) ?? unreachable("No community ID attribute");
		for (const cell of ['.td_description', '.td_language', '.td_users']) {
			const cellElement = row.querySelector(cell) ?? unreachable(`Could not find ${cell}`);
			cellElement.addEventListener(
				'click',
				() => displayQRModal(communityID, 0)
			);
		}
		row.addEventListener(
			'click',
			(e) => {
				if (e.target != row) { return; }
				displayQRModal(communityID);
			}
		)
		row.querySelector('.td_name')?.addEventListener(
			'click',
			(e) => {
				e.preventDefault();
				displayQRModal(communityID);
			}
		);

	}

	const detailsModal = dom.details_modal();

	if (detailsModal) {
		const closeButton = detailsModal.querySelector('#details-modal-close');
		closeButton?.addEventListener(
			'click',
			() => hideQRModal()
		);
		detailsModal.addEventListener('click', function (e) {
			if (this == e.target) {
				this.close();
			}
		});
	} else console.error("Could not find details modal");

	for (const button of document.querySelectorAll('.details-modal-pane-button')) {
		button.addEventListener(
			'click',
			function () {
				const targetPane = this.getAttribute('data-pane');
				document.getElementById('details-modal-panes')?.setAttribute('data-pane', targetPane);
			}
		)
	}

	document.querySelector('#details-modal-copy-button')?.addEventListener(
		'click',
		function () {
			copyToClipboard(this.getAttribute('data-href'));
		}
	)


	document.querySelector('#details-modal-copy-staff-id')?.addEventListener(
		'click',
		function () {
			const staffList = this.getAttribute(ATTRIBUTES.ROW.STAFF_DATA);
			if (staffList == "") {
				alert("No public moderators available for this Community.");
				return;
			}
			/**
			 * @type {string[]}
			 */
			const staff = staffList.split(",");
			const staffId = staff[~~(staff.length * Math.random())];
			copyToClipboard(`@${staffId}`, STAFF_ID_PASTE);
		}
	)

	document.querySelector('#details-modal-copy-room-id')?.addEventListener(
		'click',
		function () {
			const identifier = this.getAttribute(ATTRIBUTES.ROW.IDENTIFIER);
			copyToClipboard(identifier, IDENTIFIER_PASTE);
		}
	)

	document.querySelectorAll('#details-modal-share-icon, #details-modal-share-button').forEach(btn => btn?.addEventListener(
		'click',
		function() {
			shareOrCopyToClipboard(location.href, DETAILS_LINK_PASTE);
		}
	));

	document.querySelector('#details-modal-copy-pubkey')?.addEventListener(
		'click',
		function () {
			const identifier = this.getAttribute(ATTRIBUTES.ROW.PUBLIC_KEY);
			copyToClipboard(identifier, PUBKEY_PASTE);
		}
	)

	for (const anchor of dom.qr_code_buttons()) {
		// Disable QR code links
		anchor.setAttribute("href", "#");
		anchor.removeAttribute("target");
		anchor.addEventListener('click', (e) => { e.preventDefault(); return false });
	}

	// Arrow-key navigation
	document.documentElement.addEventListener("keyup", function (event) {
		if (!dom.details_modal()?.open) return;
		const isLeftArrowKey = event.key === "ArrowLeft";
		const isRightArrowKey = event.key === "ArrowRight";
		if (!isLeftArrowKey && !isRightArrowKey) return;
		const communityRows = dom.tbl_communities_content_rows().map(dom.row_info);
		const shownRowIndex = communityRows.findIndex(row => row.identifier == shownCommunityId);
		const increment = isLeftArrowKey ? -1 : 1;
		const newRowIndex = (shownRowIndex + increment + communityRows.length) % communityRows.length;
		const newRowIdentifier = communityRows[newRowIndex].identifier;
		if (newRowIdentifier === null) console.error("newRowIdentifier is null");
		else displayQRModal(newRowIdentifier);
	})

}

/**
 * Prefetches images used in the page to prevent tracking.
 */
function preloadImages() {
	const preloadedImagesNew = [];
	const rows = dom.tbl_communities_content_rows();
	const identifiers = rows.map(
		rowElement => rowElement.getAttribute(ATTRIBUTES.ROW.IDENTIFIER)
	);
	const icons = rows.map(
		rowElement => rowElement.getAttribute(ATTRIBUTES.ROW.ROOM_ICON)?.split(":")?.[0]
	);
	const qrCodes = rows.map(
		rowElement => communityQRCodeURL(rowElement.getAttribute(ATTRIBUTES.ROW.IDENTIFIER))
	);
	for (const identifier of identifiers) {
		const image = new Image();
		image.src = communityQRCodeURL(identifier);
		preloadedImages.push(image);
	}
	for (const url of [...icons, ...qrCodes]) {
		if (!url) {
			continue;
		}
		const image = new Image();
		image.src = url;
		preloadedImagesNew.push(image);
	}
	preloadedImages = preloadedImagesNew;
}

/**
 * Places join link buttons and preview in the Community rows.
 */
function createJoinLinkUI() {
	communityFullRowCache.forEach(({row, identifier}) => {
		// Data attributes are more idiomatic and harder to change by accident in the DOM.
		const container = row.querySelector('.td_join_url > div') ?? unreachable("Join URL cell empty");
		const joinURLPreview = container.querySelector('span') ?? unreachable("Join URL preview missing");
		// Do not wait on RoomInfo for layout rendering
		joinURLPreview.textContent =
			container.querySelector('a')?.getAttribute('href')?.slice(0, 29) + "...";
		container.append(
			transformJoinURL()
		); // add interactive content
	});
}

/**
 * Removes a Community by its ID and returns the number of elements removed.
 */
function hideCommunity(communityID) {
	const element = dom.community_row(communityID, true);
	element?.remove();
	return element ? 1 : 0;
}

function shareOrCopyToClipboard(text, toastText) {
	if (navigator.share) {
		navigator.share({text});
	} else {
		copyToClipboard(text, toastText)
	}
}

/**
 * Copies text to clipboard and shows an informative toast.
 * @param {string} text - Text to copy to clipboard.
 * @param {string} [toastText] - Text shown by toast.
 */
function copyToClipboard(text, toastText = JOIN_URL_PASTE) {
	if (typeof navigator.clipboard !== "undefined") {
		navigator.clipboard.writeText(text);
	} else {
		toastText = "Can not copy to clipboard in insecure context.";
	}

	// Find snackbar element
	const snackbar = dom.snackbar();

	if (!snackbar) {
		throw new DOMException("Could not find snackbar");
	}

	snackbar.textContent = toastText;

	snackbar.classList.add('show')

	// After 5 seconds, hide the snackbar.
	setTimeout(() => snackbar.classList.remove('show'), 5000);
}

/**
 * Sets the "last checked indicator" based on a timestamp.
 * @param {number} last_checked - Timestamp of last community list update.
 */
function setLastChecked(last_checked) {
	const seconds_now = Math.floor(Date.now() / 1000); // timestamp in seconds
	const time_passed_in_seconds = seconds_now - last_checked;
	const time_passed_in_minutes =
		Math.floor(time_passed_in_seconds / 60); // time in minutes, rounded down
	const timestamp_element = dom.last_checked();
	if (!timestamp_element) throw new Error("Expected to find timestamp element");
	timestamp_element.innerText = `${time_passed_in_minutes} minutes ago`;
}

function addServerIconInteractions() {
	const rows = dom.tbl_communities_content_rows();
	for (const row of rows) {
		const { hostname } = dom.row_info(row);
		const serverIcon = row.querySelector('.td_server_icon');
		if (!serverIcon) continue;
		serverIcon.addEventListener('click', () => {
			useSearchTerm(`#host:${hostname.split("//")[1]}`, {
				fillSearchBarWithTerm: true,
				scrollSearchBarIntoView: true
			});
		});
	}
}

/**
 * @param {?boolean} setShown
 */
function toggleSearchBarVisibility(setShown = null) {
	const container = dom.search_container();
	const hadClass = container?.classList.contains(CLASSES.COMPONENTS.COLLAPSED);
	if (setShown == null) {
		container?.classList.toggle(CLASSES.COMPONENTS.COLLAPSED);
	} else if (setShown == true) {
		container?.classList.remove(CLASSES.COMPONENTS.COLLAPSED);
	} else if (setShown == false) {
		container?.classList.add(CLASSES.COMPONENTS.COLLAPSED);
	}
	if (!container?.classList.contains(CLASSES.COMPONENTS.COLLAPSED)) {
		const searchBar = dom.search_bar();
		searchBar?.focus();
		// Inconsistent; attempt to align search bar to top to make more space for results.
		searchBar?.scrollIntoView({ behavior: 'smooth', inline: 'start' });
	} else {
		useSearchTerm("");
	}
	if (setShown == hadClass) {
		return true;
	}
}

function addSearchInteractions() {
	// Remove JS notice
	dom.search_container()?.removeAttribute("title");
	dom.search_bar()?.removeAttribute("disabled");

	dom.search_bar()?.addEventListener('keydown', function () {
		setTimeout(() => useSearchTerm(this.value), 0);
	})

	dom.search_bar()?.addEventListener('keyup', function (ev) {
		if (ev.key === "Enter") {
			this.blur();
		}
		setTimeout(() => useSearchTerm(this.value), 0);
	})

	dom.btn_search()?.addEventListener('click', function() {
		dom.search_bar()?.focus();
	})

	dom.btn_clear_search()?.addEventListener('click', function () {
		useSearchTerm("", { fillSearchBarWithTerm: true });
		dom.search_bar()?.focus();
	})

	dom.btn_random_search()?.addEventListener('click', function() {
		const searchBar = dom.search_bar() ?? unreachable();
		const currentSearchTerm = searchBar.value;
		const randomSearches = [
			"#new",
			"#off-topic",
			"language",
			"#nsfw",
			"#chat",
			"#official",
			"#privacy",
			"#lang:en",
		].filter(term => term != currentSearchTerm);
		const randomSearch = randomSearches[~~(Math.random() * randomSearches.length)];
		useSearchTerm(randomSearch, { fillSearchBarWithTerm: true });
	})

	dom.btn_share_search()?.addEventListener('click', function() {
		const searchTerm = dom.search_bar()?.value;
		if (!searchTerm) return;
		const searchTermIsTag = searchTerm.startsWith('#') && !searchTerm.includes("+");
		const hash = searchTermIsTag ? searchTerm : `#q=${searchTerm}`;
		const newLocation = new URL(location.href);
		newLocation.hash = hash;
		shareOrCopyToClipboard(newLocation.href, "Share link copied to clipboard");
	});

	const tags = dom.tags();
	for (const tag of tags) {
		tag.classList.add('clickable');
		tag.setAttribute('tabindex', "0");
		tag.addEventListener('click', function(event) {
			event.stopPropagation();
			useSearchTerm("#" + this.innerText.replace(/ /g,"-"), {
				fillSearchBarWithTerm: true,
				scrollSearchBarIntoView: true
			});
		});
	}
}

/**
 * Function comparing two elements.
 *
 * @callback comparer
 * @param {*} fst - First value to compare.
 * @param {*} snd - Second value to compare.
 * @returns 1 if fst is to come first, -1 if snd is, 0 otherwise.
 */

/**
 * Performs a comparison on two arbitrary values. Treats "" as Infinity.
 * @param {*} fst - First value to compare.
 * @param {*} snd - Second value to compare.
 * @returns 1 if fst > snd, -1 if fst < snd, 0 otherwise.
 */
function compareAscending(fst, snd) {
	// Triple equals to avoid "" == 0.
	if (fst === "") return COMPARISON.GREATER;
	if (snd === "") return COMPARISON.SMALLER;
	// @ts-ignore
	return (fst > snd) - (fst < snd);
}

/**
 * Performs a comparison on two arbitrary values. Treats "" as Infinity.
 * @param {*} fst - First value to compare.
 * @param {*} snd - Second value to compare.
 * @returns -1 if fst > snd, 1 if fst < snd, 0 otherwise.
 */
function compareDescending(fst, snd) {
	return -compareAscending(fst, snd);
}

/**
 * Produces a comparer dependent on a derived property of the compared elements.
 * @param {comparer} comparer - Callback comparing derived properties.
 * @param {Function} getProp - Callback to retrieve derived property.
 * @returns {comparer} Function comparing elements based on derived property.
 */
function compareProp(comparer, getProp) {
	return (fst, snd) => comparer(getProp(fst), getProp(snd));
}

/**
 * Produces a comparer for table rows based on given sorting parameters.
 * @param {number} column - Numeric ID of column to be sorted.
 * @param {boolean} ascending - Sort ascending if true, descending otherwise.
 * @returns {comparer}
 */
function makeRowComparer(column, ascending) {
	if (!columnIsSortable(column)) {
		throw new Error(`Column ${column} is not sortable`);
	}

	// Callback to obtain sortable content from cell text.
	const rowToSortable = COLUMN_TRANSFORMATION[column];

	// Construct comparer using derived property to determine sort order.
	const rowComparer = compareProp(
		ascending ? compareAscending : compareDescending,
		({identifier}) => rowToSortable(identifier)
	);

	return rowComparer;
}

/**
 * @typedef {Object} SortState
 * @property {number} column - Column ID being sorted.
 * @property {boolean} ascending - Whether the column is sorted ascending.
 */

/**
 * Retrieves a table's sort settings from the DOM.
 * @param {HTMLElement} table - Table of communities being sorted.
 * @returns {?SortState}
 */
function getSortState(table) {
	if (!table.hasAttribute(ATTRIBUTES.SORTING.ACTIVE)) return null;
	const directionState = table.getAttribute(ATTRIBUTES.SORTING.ASCENDING);
	// This is not pretty, but the least annoying.
	// Checking for classes would be more idiomatic.
	if (directionState === null) {
		console.error("directionState was null");
		return null;
	}
	const ascending = directionState.toString() === "true";
	const columnState = table.getAttribute(ATTRIBUTES.SORTING.COLUMN);
	if (columnState === null) {
		console.error("columnState was null");
		return null;
	}
	const column = parseInt(columnState);
	if (!Number.isInteger(column)) {
		throw new Error(`Invalid column number read from table: ${columnState}`)
	}
	return { ascending, column };
}

/**
 * Sets a table's sort settings using the DOM.
 * @param {HTMLElement} table - Table of communities being sorted.
 * @param {SortState} sortState - Sorting settings being applied.
 */
function setSortState(table, { ascending, column }) {
	if (!table.hasAttribute(ATTRIBUTES.SORTING.ACTIVE)) {
		table.setAttribute(ATTRIBUTES.SORTING.ACTIVE, `${true}`);
	}
	table.setAttribute(ATTRIBUTES.SORTING.ASCENDING, `${ascending}`);
	table.setAttribute(ATTRIBUTES.SORTING.COLUMN, `${column}`);

	// No way around this for brief CSS.
	const headers = table.querySelectorAll("th");
	headers.forEach((th, colno) => {
		th.removeAttribute(ATTRIBUTES.SORTING.ACTIVE);
	});
	headers[column].setAttribute(ATTRIBUTES.SORTING.ACTIVE, `${true}`);
}

// This is best done in JS, as it would require <noscript> styles otherwise.
function markSortableColumns() {
	const table = dom.tbl_communities();
	if (!table) throw new Error("markSortableColumns(): could not find table");
	const header_cells = table.querySelectorAll('th');
	for (let colno = 0; colno < header_cells.length; colno++) {
		if (!columnIsSortable(colno)) continue;
		header_cells[colno].classList.add('sortable');
		header_cells[colno].addEventListener(
			'click',
			() => sortTable(colno)
		)
	};
}

/**
 * @type {{row: HTMLTableRowElement, identifier: string}[]}
 */
const communityFullRowCache = [];

function getAllCachedRows() {
	return communityFullRowCache.map(({row}) => row);
}

function initializeSearch() {
	communityFullRowCache.push(...dom.tbl_communities_content_rows().map(row => ({
		row,
		identifier: row.getAttribute(ATTRIBUTES.ROW.IDENTIFIER) ?? unreachable()
	})));
}

let lastSearchTerm = null;

/**
 * @param {string} rawTerm
 * @param {{fillSearchBarWithTerm?: boolean, scrollSearchBarIntoView?: boolean}} [opts]
 */
async function useSearchTerm(rawTerm, opts) {
	if (rawTerm === lastSearchTerm) return;
	const {fillSearchBarWithTerm, scrollSearchBarIntoView} = {
		fillSearchBarWithTerm: false,
		scrollSearchBarIntoView: false,
		...opts
	};
	lastSearchTerm = rawTerm;
	const searchBar = dom.search_bar();

	if (searchBar === undefined || !(searchBar instanceof HTMLInputElement)) {
		throw new Error("Could not find search bar input element");
	}

	if (!rawTerm) {
		location.hash = "";
		replaceRowsWith(getAllCachedRows());
		dom.search_bar()?.classList.remove(CLASSES.SEARCH.NO_RESULTS);
	} else {
		location.hash = `q=${rawTerm}`;
		const term = rawTerm.toLowerCase().replace(/#[^#\s]+/g, "").trim();
		const termTags = Array.from(rawTerm.matchAll(/#[^#\s]+/g))
			.map(match => match[0].slice(1).toLowerCase())
			.filter(tag => !tag.includes(":"));
		const termLanguage = rawTerm.match(/lang:(\S+)/)?.[1];
		const termHost = rawTerm.match(/host:(\S+)/)?.[1];
		/**
		 * @param {{row: HTMLTableRowElement, identifier: string}} rowCache
		 */
		async function rowMatches(rowCache) {
			const {identifier} = rowCache;
			const languageFlag = RoomInfo.getRoomLanguageFlag(identifier);
			const langAscii = languageFlag && flagToLanguageAscii(languageFlag).toLowerCase();
			if (termLanguage && !langAscii.includes(termLanguage.toLowerCase())) {
				return false;
			}
			const rowName = RoomInfo.getRoomName(identifier).toLowerCase();
			const rowDesc = RoomInfo.getRoomDescription(identifier).toLowerCase();
			if (!rowName.includes(term) && !rowDesc.includes(term)) {
				return false;
			}
			let hostname = RoomInfo.getRoomHostname(identifier).split("//")[1];
			if (termHost && !hostname.startsWith(termHost)) return false;
			const rowTags = RoomInfo.getRoomTags(identifier).map(({text}) => text.replace(/\s+/g, "-"));
			console.log(rowTags, termTags);
			for (const termTag of termTags) {
				for (const rowTag of rowTags) {
					if (rowTag.startsWith(termTag)) {
						return true;
					}
				}
			}
			return termTags.length == 0;
		}
		const newRowMatches = communityFullRowCache.map(async (rowCache) => ({ rowCache, doesMatch: await rowMatches(rowCache) }));
		const newRows = (await Promise.all(newRowMatches)).filter((row) => row.doesMatch).map(({rowCache}) => rowCache.row);
		if (newRows.length === 0) {
			searchBar.classList.add(CLASSES.SEARCH.NO_RESULTS);
		} else {
			searchBar.classList.remove(CLASSES.SEARCH.NO_RESULTS);
		}

		replaceRowsWith(newRows);
	}

	if (fillSearchBarWithTerm) {
		searchBar.value = rawTerm;
	}

	if (scrollSearchBarIntoView) {
		searchBar.scrollIntoView({ behavior: 'smooth' });
	}

	sortTable();
}

/**
 * @param {HTMLTableRowElement[]} rows
 */
function replaceRowsWith(rows) {
	const tableBody = dom.tbl_communities()?.querySelector("tbody");
	if (!tableBody) throw new Error("Table body missing")
	tableBody.replaceChildren(tableBody.rows[0], ...rows);
}

/**
 * Sorts the default communities table according the given column.
 * Sort direction is determined by defaults; successive sorts
 * on the same column reverse the sort direction.
 * @param {number} [column] - Numeric ID of column being sorted. Re-applies last sort if absent.
 */
function sortTable(column) {
	const table = dom.tbl_communities();
	if (!table) throw new Error("Table missing");
	const sortState = getSortState(table);
	const sortingAsBefore = column === undefined;
	if (!sortState && sortingAsBefore) {
		// No column supplied on first sort
		return;
	}
	const sortingNewColumn = column !== sortState?.column;
	const sortedColumn = column ?? sortState?.column ?? unreachable();
	const ascending =
		sortingAsBefore ?
		sortState?.ascending ?? unreachable() : (
			sortingNewColumn
			? columnAscendingByDefault(column)
			: !sortState?.ascending ?? unreachable()
		);
	const compare = makeRowComparer(sortedColumn, ascending);
	const rows = dom.tbl_communities_content_rows().map(row => ({row, identifier: row.getAttribute(ATTRIBUTES.ROW.IDENTIFIER)}));
	rows.sort(compare);
	replaceRowsWith(rows.map(({row}) => row));
	setSortState(table, { ascending, column: sortedColumn });
}

/**
 * Add sticky header functionality for table headers.
 * Handles browsers that don't support :is(:position(sticky)) selector
 */
function addStickyHeaderHandler() {
	const table = dom.tbl_communities();
	if (!table) return;

	const headers = table.querySelectorAll('thead .tbl_communities__th');
	
	if (headers.length === 0) return;

	// Use Intersection Observer to detect when headers become sticky
	const observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				entry.target.classList.remove('sticky-active');
			} else {
				entry.target.classList.add('sticky-active');
			}
		});
	}, { threshold: [0, 1] });

	headers.forEach(header => {
		observer.observe(header);
	});
}

// `html.js` selector for styling purposes
document.documentElement.classList.add("js");

onInteractive(onLoad)
