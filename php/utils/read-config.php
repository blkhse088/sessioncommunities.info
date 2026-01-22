<?php

require_once 'php/utils/logging.php';
require_once 'php/utils/utils.php';

/**
 * @var string $CONFIG_ROOT
 */

function config_requiring_type(
	mixed $value,
	bool $int = false,
	bool $string = false,
	bool $bool = false,
	bool $float = false,
	bool $null = true
) {
	if (is_int($value) && $int) return $value;
	if (is_bool($value) && $bool) return $value;
	if (is_string($value) && $string) return $value;
	if (is_float($value) && $float) return $value;
	if (is_null($value) && $null) return $value;
	$realtype = get_debug_type($value);
	throw new Error("Unexpected configuration option type: $realtype");
}

function remove_trailing_key_slashes(array $array): array {
	return $array;
	$newarray = array();
	foreach ($array as $key => $value) {
		if (str_ends_with($key, "/")) {
			$newarray[substring($key, 0, -1)] = $value;
		} else $newarray[$key] = $value;
	}
	return $newarray;
}

/**
 * Provides custom configuration values.
 */
class LocalConfig {
	private function __construct() {
		$room_overrides =
			LocalConfig::maybe_parse_ini_file(LocalConfig::ROOM_OVERRIDES_CONFIG)
			?? array();
		// Sort room overrides last
		uksort($room_overrides, function($identifier_a, $identifier_b) {
			return str_contains($identifier_b, "+") - str_contains($identifier_a, "+");
		});
		$this->room_overrides = $room_overrides;
		$this->room_overrides_computed = array();
		$this->known_servers = remove_trailing_key_slashes(
			LocalConfig::maybe_parse_ini_file(LocalConfig::KNOWN_SERVERS_CONFIG)
			?? array()
		);
	}

	private static LocalConfig | null $instance = null;

	/**
	 * Get the canonical instance of LocalConfig.
	 * @return LocalConfig
	 */
	public static function get_instance(): LocalConfig {
		return LocalConfig::$instance ??= LocalConfig::read_from_files();
	}

	private const ROOM_OVERRIDES_CONFIG = "room-overrides.ini";
	private const KNOWN_SERVERS_CONFIG = "known-servers.ini";

	private readonly array $room_overrides;

	private array $room_overrides_computed;

	private readonly array $known_servers;

	private static function maybe_parse_ini_file(string $filename): array | null {
		global $CONFIG_ROOT;
		$file = "$CONFIG_ROOT/$filename";

		if (!file_exists($file)) {
			log_warning("config file not found: $file");
			return null;
		}

		return parse_ini_file($file, process_sections: true, scanner_mode: INI_SCANNER_TYPED);
	}

	/**
	 * Read local config from the filesystem.
	 */
	private static function read_from_files() {
		return new LocalConfig();
	}

	private function get_room_overrides(CommunityRoom $room): array {
		$room_id = $room->get_room_identifier();

		if (isset($this->room_overrides_computed[$room_id])) {
			return $this->room_overrides_computed[$room_id];
		}

		$room_overrides = array();

		foreach ($this->room_overrides as $identifier => $overrides) {
			if ($room->matched_by_identifier($identifier)) {
				$room_overrides = [...$room_overrides, ...$overrides];
			}
		}

		return $this->room_overrides_computed[$room_id] = $room_overrides;
	}

	private function get_server_overrides(CommunityServer $server): array {
		$server_base_url = $server->get_base_url();

		return $this->known_servers[$server_base_url] ?? array();
	}

	public function get_room_staff_count_override(CommunityRoom $room): int | null {
		return config_requiring_type(
			$this->get_room_overrides($room)['staff_count'],
			int: true,
		);
	}

	public function get_room_safety_override(CommunityRoom $room): RoomSafety {
		return RoomSafety::from_keyword(
			$this->get_room_overrides($room)['safety']
		);
	}

	private function get_bool_override_value(CommunityRoom $room, string $override_key) {
		return config_requiring_type(
			$this->get_room_overrides($room)[$override_key] === true,
			bool: true
		) === true;
	}

	public function is_testing_room(CommunityRoom $room): bool {
		return $this->get_bool_override_value($room, 'testing');
	}

	public function is_stickied_room(CommunityRoom $room): bool {
		return $this->get_bool_override_value($room, 'stickied');
	}

	public function is_hidden_room(CommunityRoom $room): bool {
		return $this->get_bool_override_value($room, 'hidden');
	}

	public function get_known_servers(): array {
		return $this->known_servers;
	}

	public function get_server_icon_room(CommunityServer $server): ?string {
		return $this->get_server_overrides($server)['icon'] ?? null;
	}

	public function get_server_icon_file(CommunityServer $server): ?string {
		$hostname = $server->get_hostname(include_port: false);
		
		// Check for direct file upload first
		$icon_file_path = $this->get_server_icon_file_path($hostname);
		if ($icon_file_path && file_exists($icon_file_path)) {
			return $hostname;
		}
		
		// Fallback to existing room-based approach
		return $this->get_server_icon_room($server);
	}

	public function get_server_icon_file_path(string $hostname): ?string {
		global $CUSTOM_CONTENT_ROOT;
		$icons_dir = "$CUSTOM_CONTENT_ROOT/server-icons";
		
		// Check for extensions first (preferred order)
		$extensions = ['png', 'webp', 'jpg', 'jpeg', 'gif'];
		foreach ($extensions as $ext) {
			$path = "$icons_dir/$hostname.$ext";
			if (file_exists($path)) {
				return $path;
			}
		}
		
		// Check for exact hostname match
		$exact_path = "$icons_dir/$hostname";
		if (file_exists($exact_path)) {
			return $exact_path;
		}
		
		return null;
	}
}

class RoomSafety {
	private function __construct(int $value) {
		$this->safety = $value;
	}

	private readonly int $safety;

	private const UNSAFE = -2;
	private const NOT_SAFE_FOR_WORK = -1;
	private const UNSET = 0;
	private const SAFE_FOR_WORK = 1;

	public static function from_keyword(string|null $keyword) {
		if (null == $keyword) {
			return new RoomSafety(RoomSafety::UNSET);
		}
		switch (mb_strtolower($keyword)) {
			case "unsafe":
				return new RoomSafety(RoomSafety::UNSAFE);
			case "nsfw":
				return new RoomSafety(RoomSafety::NOT_SAFE_FOR_WORK);
			case "sfw":
				return new RoomSafety(RoomSafety::SAFE_FOR_WORK);
			default:
				throw new Error("Unknown safety class: $keyword");
		}
	}

	private function is_set() {
		return $this->safety !== RoomSafety::UNSET;
	}

	public function rated_nsfw(): bool | null {
		if (!$this->is_set()) return null;
		return $this->safety <= RoomSafety::NOT_SAFE_FOR_WORK;
	}
}

?>
