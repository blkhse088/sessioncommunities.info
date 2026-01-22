<?php
	/**
	 * \file
	 * Implement the fetching of room invite QR codes.
	 */
	require_once 'php/utils/logging.php';
	require_once 'php/utils/fetching-coroutines.php';
	require_once 'php/servers/servers-rooms.php';

	/**
	 * Return local path to room invite code.
	 * @param string $room_id Id of room to locate QR code for.
	 */
	function room_qr_code_path(string $room_id): string {
		global $QR_CODES;
		return "$QR_CODES/$room_id.png";
	}

	/**
	 * Return server-relative path to room invite code.
	 * @param string $room_id Id of room to locate QR code for.
	 */
	function room_qr_code_path_relative(string $room_id): string {
		global $QR_CODES_RELATIVE;
		return "$QR_CODES_RELATIVE/$room_id.png";
	}


	/**
	 * Fetches the QR code for the given Community, yielding required network requests.
	 * @param CommunityRoom $room Community to fetch QR code for
	 * @return Generator<int,CurlHandle,CurlHandle|false,void>
	 */
	function fetch_qr_code_coroutine(CommunityRoom $room): Generator {
		$room_id = $room->get_room_identifier();
		$png_cached = room_qr_code_path($room_id);
		$image_expired = file_exists($png_cached) &&
			filemtime($png_cached) < strtotime("-12 hour");
		if (file_exists($png_cached) && !$image_expired) {
			return room_qr_code_path_relative($room_id);
		}
		log_debug("Fetching QR code for $room_id.");
		$png_response = yield from FetchingCoroutine::from_url($room->get_invite_url())->run();
		$png = $png_response ? curl_multi_getcontent($png_response) : null;
		if (empty($png)) {
			log_warning("$room_id returned an empty QR code.");
			touch($png_cached);
		}
		// Never overwrite with an empty file.
		if (!(file_exists($png_cached) && filesize($png_cached) > 0 && empty($png))) {
			file_put_contents($png_cached, $png);
		}
	}

	/**
	 * Fetch QR invite of the given room and return its relative path.
	 * @param CommunityRoom $room
	 * @return string
	 */
	function room_qr_code(CommunityRoom $room): string {
		$room_id = $room->get_room_identifier();
		if (!file_exists(room_qr_code_path($room_id))) {
			log_warning("Missing QR code asset for $room_id.");
			return "";
		}
		return room_qr_code_path_relative($room_id);
	}

	file_exists($QR_CODES) or mkdir($QR_CODES, 0755);
?>