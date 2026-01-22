// This file contains definitions which help to reduce the amount
// of redundant values in the main file, especially those that could
// change in the foreseeable future.

class _RoomInfo {
	static ROOMS_ENDPOINT = '/servers.json';
	static TAGS_ENDPOINT = '/tags.json';
	static rooms = {};
	static servers = {};
	static tags = {};

	static async fetchRooms() {
		const responses = await Promise.all([fetch(this.ROOMS_ENDPOINT), fetch(this.TAGS_ENDPOINT)]);
		const servers = await responses[0].json();
		for (const server of servers) {
			const { server_id } = server;
			for (const room of server.rooms) {
				const identifier = `${room.token}+${server_id}`;
				this.rooms[identifier] = {...room, server_id};
			}
			delete server.rooms;
			this.servers[server_id] = server;
		}
		this.tags = await responses[1].json();
	}

	/**
	 * @param {string} identifier
	 */
	static assertRoomExists(identifier) {
		if (!(identifier in this.rooms)) {
			throw new Error(`No such room: ${identifier}`);
		}
	}

	/**
	 * @param {string} identifier
	 * @returns {CommunityRoom}
	 */
	static getRoom(identifier) {
		this.assertRoomExists(identifier);
		return this.rooms[identifier];
	}

	/**
	 * @param {string} identifier
	 * @returns {CommunityServer}
	 */
	static getRoomServer(identifier) {
		this.assertRoomExists(identifier);
		return this.servers[this.rooms[identifier].server_id];
	}
}
export class RoomInfo {
	static async fetchRooms() {
		return _RoomInfo.fetchRooms();
	}

	/**
	 * @param {string} identifier
	 * @returns {{type: string, text: string, description: string}[]}
	 */
	static getRoomTags(identifier) {
		const tags = _RoomInfo.getRoom(identifier).tags;
		return tags.map(tag => ({
			...tag,
			description: tag.type == 'user' ? `Tag: ${tag.text}` : _RoomInfo.tags[tag.text]
		}));
	}

	/**
	 * @param {string} identifier
	 * @returns {string[]}
	 */
	static getRoomStaff(identifier) {
		const room = _RoomInfo.getRoom(identifier);
		const { admins = [], moderators = [] } = room;
		return [...new Set([...admins, ...moderators])];
	}

	/**
	 * @param {string} identifier
	 * @returns {string}
	 */
	static getRoomPublicKey(identifier) {
		const server = _RoomInfo.getRoomServer(identifier);
		return server.pubkey;
	}

	/**
	 * @param {string} identifier
	 * @returns {Date}
	 */
	static getRoomCreationDate(identifier) {
		const room = _RoomInfo.getRoom(identifier);
		return new Date(room.created * 1000);
	}

	/**
	 * @param {string} identifier
	 * @returns {string}
	 */
	static getRoomToken(identifier) {
		return identifier.split("+")[0];
	}

	/**
	 * @param {string} identifier
	 * @returns {string}
	 */
	static getRoomServerId(identifier) {
		return identifier.split("+")[1];
	}

	/**
	 * @param {string} identifier
	 * @returns {string}
	 */
	static getRoomLanguageFlag(identifier) {
		return _RoomInfo.getRoom(identifier).language_flag;
	}

	/**
	 * @param {string} identifier
	 * @returns {string}
	 */
	static getRoomName(identifier) {
		return _RoomInfo.getRoom(identifier).name;
	}

	/**
	 * @param {string} identifier
	 * @returns {string}
	 */
	static getRoomDescription(identifier) {
		return _RoomInfo.getRoom(identifier).description;
	}

	/**
	 * @param {string} identifier
	 * @returns {number}
	 */
	static getRoomUserCount(identifier) {
		return _RoomInfo.getRoom(identifier).active_users;
	}

	/**
	 * @param {string} identifier
	 * @returns {string}
	 */
	static getRoomPreviewLink(identifier) {
		const server = _RoomInfo.getRoomServer(identifier);
		return `${server.base_url}/r/${RoomInfo.getRoomToken(identifier)}`;
	}

	/**
	 * @param {string} identifier
	 * @returns {string}
	 */
	static getRoomJoinLink(identifier) {
		const server = _RoomInfo.getRoomServer(identifier);
		const token = RoomInfo.getRoomToken(identifier);
		return `${server.base_url}/${token}?public_key=${server.pubkey}`;
	}

	/**
	 * @param {string} identifier
	 * @returns {string}
	 */
	static getRoomHostname(identifier) {
		return _RoomInfo.getRoomServer(identifier)?.base_url;
	}
}

export const dom = {
	/** @return {HTMLTableElement | null} */
	tbl_communities: () => document.getElementById("tbl_communities"),
	tbl_communities_content_rows:
		() => Array.from(dom.tbl_communities()?.rows)?.filter(row => !row.querySelector('th')),
	/**
	 * @param {string} communityID
	 * @param {bool} matchIdPrefix
	 * @returns {HTMLRowElement | null}
	 */
	community_row: (communityID, matchIdPrefix=false) => {
		const identifier = ATTRIBUTES.ROW.IDENTIFIER;
		// Support matching shorter legacy IDs in links online
		const matches = matchIdPrefix ? '^=' : '=';
		// Support matching room token, but only as a full match (plus symbol and hex code follows)
		const id = (!matchIdPrefix || communityID.includes('+')) ? communityID : `${communityID}+`;
		return document.querySelector(`.room-row[${identifier}${matches}"${id}"]`);
	},
	/**
	 * @param {HTMLTableRowElement} row
	 */
	row_info: (row) => {
		const identifier = row.getAttribute(ATTRIBUTES.ROW.IDENTIFIER);
		const dateCreated = RoomInfo.getRoomCreationDate(identifier);
		const [icon, iconSafety] = row.getAttribute(ATTRIBUTES.ROW.ROOM_ICON).split(":");
		/** @type {string[]} */
		return {
			language_flag: RoomInfo.getRoomLanguageFlag(identifier),
			name: RoomInfo.getRoomName(identifier),
			description: RoomInfo.getRoomDescription(identifier),
			users: RoomInfo.getRoomUserCount(identifier),
			preview_link: RoomInfo.getRoomPreviewLink(identifier),
			join_link: RoomInfo.getRoomJoinLink(identifier),
			identifier,
			server_id: identifier.split("+")[1],
			hostname: RoomInfo.getRoomHostname(identifier),
			public_key: RoomInfo.getRoomPublicKey(identifier),
			staff: RoomInfo.getRoomStaff(identifier),
			tags: RoomInfo.getRoomTags(identifier),
			icon: icon,
			has_icon: icon.trim() != "",
			icon_safety: parseInt(iconSafety),
			date_created: dateCreated,
			creation_datestring: dateCreated.toLocaleDateString(undefined, {dateStyle: "medium"})
		};
	},
	meta_timestamp: () => document.querySelector('meta[name=timestamp]'),
	last_checked: () => document.getElementById("last_checked_value"),
	/** @return {HTMLDialogElement | null} */
	details_modal: () => document.getElementById('details-modal'),
	details_modal_tag_container: () => document.getElementById('details-modal-room-tags'),
	details_modal_qr_code: () => document.getElementById('details-modal-qr-code'),
	details_modal_room_icon: () => document.getElementById('details-modal-community-icon'),
	servers_hidden: () => document.getElementById("servers_hidden"),
	snackbar: () => document.getElementById("copy-snackbar"),
	qr_code_buttons: () => document.querySelectorAll('.td_qr_code > a'),
	/** @return {HTMLInputElement | null} */
	search_bar: () => document.querySelector('#search-bar'),
	btn_clear_search: () => document.querySelector("#btn-clear-search"),
	btn_share_search: () => document.querySelector("#btn-share-search"),
	btn_search: () => document.querySelector("#btn-search"),
	btn_random_search: () => document.querySelector("#btn-random-search"),
	search_container: () => document.querySelector("#search-container"),
	tags: () => document.querySelectorAll("#tbl_communities .tag"),
}

export const JOIN_URL_PASTE = "Copied URL to clipboard. Paste into Session app to join.";

export const STAFF_ID_PASTE = "Copied staff ping to clipboard.";

export const IDENTIFIER_PASTE = "Copied internal room identifier."

export const PUBKEY_PASTE = "Copied server public key to clipboard.";

export const DETAILS_LINK_PASTE = "Copied link to Community details.";

export const communityQRCodeURL = (communityID) => `/qr-codes/${communityID}.png`;

export const communityQRCodeURL = (communityID) => `/qr-codes/${communityID}.png`

export const COLUMN = {
	LANGUAGE:     0,  NAME:         1,
	DESCRIPTION:  2,  USERS:        3,  PREVIEW:      4,
	SERVER_ICON:  5,  JOIN_URL:     6
};

// Reverse enum.
// Takes original key-value pairs, flips them, and casefolds the new values.
// Should correspond to #th_{} and .td_{} elements in communities table.
export const COLUMN_LITERAL = Object.fromEntries(
	Object.entries(COLUMN).map(([name, id]) => [id, name.toLowerCase()])
);

export const COMPARISON = {
	GREATER: 1, EQUAL: 0, SMALLER: -1
};

export const ATTRIBUTES = {
	ROW: {
		TAGS: 'data-tags',
		IDENTIFIER: 'data-id',
		PUBLIC_KEY: 'data-pubkey',
		STAFF_DATA: 'data-staff',
		ROOM_ICON: 'data-icon',
		DATE_CREATED: 'data-created'
	},
	SORTING: {
		ACTIVE: 'data-sort',
		ASCENDING: 'data-sort-asc',
		COLUMN: 'data-sorted-by',
		// COLUMN_LITERAL: 'sorted-by'
	},
	HYDRATION: {
		CONTENT: 'data-hydrate-with'
	},
	SEARCH: {
		TARGET_SEARCH: 'data-search'
	}
};

export const CLASSES = {
	COMPONENTS: {
		COLLAPSED: 'collapsed',
	},
	SEARCH: {
		NO_RESULTS: 'search-no-results',
	}
}

const CODEPOINT_REGIONAL_INDICATOR_A = 0x1F1E6;
const CODEPOINT_LOWERCASE_A = 0x61;

/**
 *
 * @param {string} flag
 */
export function flagToLanguageAscii(flag) {
	const regionalIndicators = [0, 2].map(idx => flag.codePointAt(idx));
	if (regionalIndicators.includes(undefined)) {
		return "";
	}
	const ascii = regionalIndicators
		.map(codePoint => codePoint - CODEPOINT_REGIONAL_INDICATOR_A)
		.map(codePoint => codePoint + CODEPOINT_LOWERCASE_A)
		.map(codePoint => String.fromCodePoint(codePoint))
		.join("");

	switch (ascii) {
		case "gb":
			return "en";
		case "cn":
			return "zh";
		default:
			return ascii;
	}
}


export function columnAscendingByDefault(column) {
	return column != COLUMN.USERS;
}

export function columnIsSortable(column) {
	return ![
		COLUMN.PREVIEW,
		// Join URL contents are not guaranteed to have visible text.
		COLUMN.JOIN_URL
	].includes(column);
}

/**
 * @type {Dictionary<number, (el: HTMLTableCellElement, row: HTMLTableRowElement) => any>}
 */
export const COLUMN_TRANSFORMATION = {
	[COLUMN.LANGUAGE]: (identifier) => RoomInfo.getRoomLanguageFlag(identifier),
	[COLUMN.USERS]: (identifier) => RoomInfo.getRoomUserCount(identifier),
	[COLUMN.IDENTIFIER]: (identifier) => identifier.toLowerCase(),
	[COLUMN.NAME]: (identifier) => RoomInfo.getRoomName(identifier).toLowerCase(),
	[COLUMN.DESCRIPTION]: (identifier) => RoomInfo.getRoomName(identifier).toLowerCase(),
	[COLUMN.SERVER_ICON]: (identifier) => RoomInfo.getRoomServerId(identifier),
}

/**
 * Creates an element, and adds attributes and elements to it.
 * @param {string} tag - HTML Tag name.
 * @param {Object|HTMLElement} args - Array of child elements, may start with props.
 * @returns {HTMLElement}
 */
function createElement(tag, ...args) {
	const element = document.createElement(tag);
	if (args.length === 0) return element;
	const propsCandidate = args[0];
	if (typeof propsCandidate !== "string" && !(propsCandidate instanceof Element)) {
		// args[0] is not child element or text node
		// must be props object
		Object.assign(element, propsCandidate);
		args.shift();
	}
	element.append(...args);
	return element;
}

/** @type {Record<string, (...args: any | HTMLElement): HTMLElement>} */
export const element = new Proxy({}, {
	get(_, key) {
		return (...args) => createElement(key, ...args)
	}
});

export const unreachable = (error = "") => { throw new Error(error || "Unreachable"); };

export const workOnMainThread = () => new Promise(resolve => setTimeout(resolve, 0));

export const onInteractive = (func) => {
	document.addEventListener("DOMContentLoaded", func);
}
