<?php
	/**
	 * \file
	 * Implement basic utility functions.
	 */

	/**
	 * @var string $REGEX_JOIN_LINK
	 * Regular expression matching Session Community join links.
	 */
	$REGEX_JOIN_LINK = (function(){
		// See https://github.com/oxen-io/session-pysogs/blob/dev/administration.md
		$protocol = 'https?:';
		$hostname = '[^\/]+';
		$room_name = '[0-9A-Za-z-_]+';
		$public_key = '[[:xdigit:]]{64}';
		// Use pipe delimiter for regex to avoid escaping slashes.
		return "|$protocol//$hostname/$room_name\?public_key=$public_key|i";
	})();

	/**
	 * Counts the total rooms across the given Community servers.
	 * @param CommunityServer[] $servers Community Servers to count.
	 * @return int Total number of Community rooms.
	 */
	function count_rooms(array $servers): int {
		$rooms_total = 0;
		foreach ($servers as $server) {
			foreach ($server->rooms as $room) {
				if (!$room->is_off_record()) {
					$rooms_total += 1;
				}
			}
		}
		return $rooms_total;
	}

	/**
	 * Truncates a string to the given length.
	 * @param string $str String to truncate.
	 * @param int $len Target ellipsised length, excluding ellipsis.
	 * @return string String of given length plus ellipsis,
	 * or original string if not longer.
	 */
	function truncate(string $str, int $len) {
		$decoded = html_entity_decode($str);
		$truncated = (mb_strlen($decoded) > $len + 3)
			? mb_substr($decoded, 0, $len).'...'
			: $decoded;
		if ($decoded != $str) return html_sanitize($truncated);
		return $truncated;
	}

	/**
	 * Constructs a cURL handle for performing network requests.
	 *
	 * @param string $url Target resource to fetch.
	 * @param array $curlopts Associative array of cURL options. (optional)
	 * @return CurlHandle
	 */
	function make_curl_handle(string $url, $curlopts = []): CurlHandle {
		global $CURL_CONNECT_TIMEOUT_MS, $CURL_TIMEOUT_MS;

		$curl = curl_init($url);

		// curl_setopt($curl, CURLOPT_VERBOSE, true);

		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $CURL_CONNECT_TIMEOUT_MS / 1E3);
		curl_setopt($curl, CURLOPT_TIMEOUT, $CURL_TIMEOUT_MS / 1E3);

		curl_setopt_array($curl, $curlopts);

		foreach ($curlopts as $opt => $val) curl_setopt($curl, $opt, $val);

		return $curl;
	}

	/**
	 * Downgrades a HTTPS-facing cURL handle to HTTP.
	 * @param CurlHandle $handle cURL handle.
	 * @return CurlHandle|null Handle copy, or null if not applicable.
	 */
	function curl_handle_downgrade(CurlHandle $handle): CurlHandle|null {
		$url = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
		$scheme = parse_url($url, PHP_URL_SCHEME);
		if ($scheme != 'https') return null;
		$handle_copy = curl_copy_handle($handle);
		$url = 'http' . substr($url, strlen('https'));
		curl_setopt($handle_copy, CURLOPT_URL, $url);
		return $handle_copy;
	}

	/**
	 * Returns the base path of a URL.
	 * @param string $url The URL to slice the path from.
	 * @param bool $include_scheme [optional]
	 * Includes the scheme. `true` by default.
	 * @param bool $include_port [optional]
	 * Includes the port. `true` by default.
	 * @return string A URL composed of the original scheme (unless specified),
	 * hostname, and port (if present).
	 */
	function url_get_base(string $url, bool $include_scheme = true, bool $include_port = true) {
		$url_components = parse_url($url);
		$scheme = $url_components['scheme'];
		$host = $url_components['host'];

		if (isset($url_components['port']) && $include_port) {
			$port = $url_components['port'];
			$host .= ":$port";
		}

		return $include_scheme ? "$scheme://$host" : $host;
	}

	/**
	 * Extracts the room token from a join URL.
	 * @param string $join_url Join URL for Session Community.
	 * @return string Name of Community room.
	 */
	function url_get_token(string $join_url) {
		$token = parse_url($join_url)['path'];
		return str_replace("/", "", $token);
	}

	/**
	 * Extracts the server public key from a join URL.
	 * @param string $join_url Join URL for Session Community.
	 * @return string|null SOGS public key
	 */
	function url_get_pubkey(string $join_url) {
		$url_components = parse_url($join_url);
		$query = $url_components['query'] ?? "";
		if ($query === "") {
			log_value($join_url);
			throw new DomainException("Join URL does not contain public key");
		}
		parse_str($query, $query_components);
		return $query_components['public_key'];
	}

	/**
	 * Computes a room's ID from a join URL.
	 * @param string $join_url Join URL for Session Community.
	 * @return string Room identifier per our format.
	 */
	function url_get_room_id(string $join_url) {
		$room_token = url_get_token($join_url);
		$pubkey = url_get_pubkey($join_url);
		$pubkey_4 = substr($pubkey, 0, 4);
		$base_url = url_get_base($join_url, include_scheme: false);
		$base_url_hash_4 = substr(md5($base_url), 0, 4);
		return "$room_token+$pubkey_4$base_url_hash_4";
	}

	/**
	 * Extracts join links that match $REGEX_JOIN_LINK.
	 * @param string $html Text to find join URLs in.
	 * @return string[] Sorted array of unique server join links.
	 */
	function parse_join_links(?string $html): array {
		global $REGEX_JOIN_LINK;
		preg_match_all($REGEX_JOIN_LINK, $html, $match_result);
		$links = $match_result[0];
		sort($links);
		$links = array_unique($links);
		return $links;
	}

	/**
	 * Convert special characters to html entities.
	 * @param string $str String to sanitize
	 * @param int $flags [optional]
	 * A bitmask of one or more of the following flags,
	 * which specify how to handle quotes, invalid code unit sequences
	 * and the used document type. The default is ENT_COMPAT | ENT_HTML401.
	 * @param string $encoding Character encoding used. [optional]
	 * @param bool $double_encode [optional]
	 * When double_encode is turned off, PHP will not encode
	 * existing html entities, the default is to convert everything.
	 * @return string The converted string, possibly empty.
	 */
	function html_sanitize(
		?string $str, int $flags = ENT_QUOTES|ENT_SUBSTITUTE,
		?string $encoding = null, bool $double_encode = true
	): ?string {
		if ($str == "") {
			return "";
		}
		return htmlspecialchars($str, $flags, $encoding, $double_encode);
	}

	/**
	 * Return the sign of the given number.
	 *
	 * @param float $num Floating-point number.
	 *
	 * @return int -1 if negative, 1 if positive, 0 otherwise
	 */
	function sign(float $num): int {
		return ($num > 0) - ($num < 0);
	}

	function substring(string $string, int $start, int $end): string {
		if ($end < 0) $end += strlen($string);
		$length = $end - $start;
		if ($length < 0) return ""; else return substr($string, $start, $length);
	}
?>
