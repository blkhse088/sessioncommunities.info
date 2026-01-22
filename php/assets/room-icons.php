<?php
	/**
	 * \file
	 * Implement the fetching of Community room icons.
	 */

	require_once 'servers/known-servers.php';

	/**
	 * Return local path to room icon.
	 * @param string $room_id Id of room to locate icon for.
	 */
	function room_icon_path(string $room_id): string {
		global $ROOM_ICONS_CACHE;
		return "$ROOM_ICONS_CACHE/$room_id";
	}

	/**
	 * Return local path to resized room icon.
	 * @param string $room_id Id of room to locate icon for.
	 * @param string $size Image dimensions.
	 */
	function room_icon_path_resized(string $room_id, string $size): string {
		global $ROOM_ICONS;
		return "$ROOM_ICONS/$room_id-$size.webp";
	}

	/**
	 * Return web-facing path to room icon.
	 * @param string $room_id Id of room to locate icon for.
	 * @param string $size Image dimensions.
	 */
	function room_icon_path_relative(string $room_id, string $size): string {
		global $ROOM_ICONS_RELATIVE;
		return "$ROOM_ICONS_RELATIVE/$room_id-$size.webp";
	}

	/**
	 * Fetch the icon of a Community and yield required network requests.
	 * @param CommunityRoom $room Community to fetch icon for.
	 * @return Generator<int,CurlHandle,CurlHandle|false,void>
	 */
	function fetch_room_icon_coroutine(CommunityRoom $room): Generator {
		$room_id = $room->get_room_identifier();
		$icon_cached = room_icon_path($room_id);
		$icon_expired = file_exists($icon_cached) && filemtime($icon_cached) < strtotime("-1 day");

		// Re-fetch icons periodically.
		if (!file_exists($icon_cached) || $icon_expired) {
			$icon_url = $room->get_icon_url();
			if (empty($icon_url)) {
				return null;
			}
			log_debug("Fetching icon for $room_id.");
			$icon_response = yield from FetchingCoroutine::from_url($icon_url)->run();
			$icon = $icon_response ? curl_multi_getcontent($icon_response) : null;
			if (empty($icon)) {
				log_info("$room_id returned an empty icon.");
				touch($icon_cached);
			}
			// Never overwrite with an empty file.
			if (!(file_exists($icon_cached) && filesize($icon_cached) > 0 && empty($icon))) {
				file_put_contents($icon_cached, $icon);
			}
		}
	}

	/**
	 * Resize a fetched icon of the given room and return its relative path.
	 * @param CommunityRoom $room
	 * @param string $size Image dimensions.
	 * @return string Relative path or null if icon is absent.
	 */
	function room_icon(CommunityRoom $room, string $size): ?string {
		list($width, $height) = explode("x", $size);
		$width = intval($width);
		$height = intval($height);
		assert(!empty($width) && !empty($height));

		if ($room->icon_safety() < 0) {
			return null;
		}

		$room_id = $room->get_room_identifier();
		$icon_cached = room_icon_path($room_id);
		$icon_resized = room_icon_path_resized($room_id, $size);
		$icon_expired = file_exists($icon_cached) && filemtime($icon_cached) < strtotime("-1 day");

		if (!file_exists($icon_cached)) {
			log_debug("Missing icon asset for $room_id");
			return "";
		}
		if (!file_exists($icon_resized) || $icon_expired) {
			$icon_cached_contents = file_get_contents($icon_cached);
			if (empty($icon_cached_contents)) {
				file_put_contents($icon_resized, "");
				return "";
			}
			// Resize image
			$gd_image = imagecreatefromstring($icon_cached_contents);
			$gd_resized = imagescale($gd_image, $width, $height);
			if (!imagewebp($gd_resized, $icon_resized)) {
				log_info("Converting image for $room_id to $size failed");
			}
		}
		if (filesize($icon_resized) == 0) {
			return "";
		}
		return room_icon_path_relative($room_id, $size);
	}

	file_exists($ROOM_ICONS_CACHE) or mkdir($ROOM_ICONS_CACHE, 0755, true);
	file_exists($ROOM_ICONS) or mkdir($ROOM_ICONS, 0755, true);
?>
