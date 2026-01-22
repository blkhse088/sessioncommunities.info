<?php
	/**
	 * \file
	 * Implement the fetching of Community server icons.
	 */

	require_once 'servers/known-servers.php';
	require_once 'assets/room-icons.php';
	require_once 'utils/read-config.php';

	/**
	 * Return local path to uploaded server icon.
	 * @param string $hostname Hostname of server.
	 */
	function server_icon_file_path(string $hostname): string {
		global $SERVER_ICONS_CACHE;
		return "$SERVER_ICONS_CACHE/$hostname";
	}

	/**
	 * Return local path to resized server icon.
	 * @param string $hostname Hostname of server.
	 * @param string $size Image dimensions.
	 */
	function server_icon_path_resized(string $hostname, string $size): string {
		global $SERVER_ICONS;
		return "$SERVER_ICONS/$hostname-$size.webp";
	}

	/**
	 * Return web-facing path to server icon.
	 * @param string $hostname Hostname of server.
	 * @param string $size Image dimensions.
	 */
	function server_icon_path_relative(string $hostname, string $size): string {
		global $SERVER_ICONS_RELATIVE;
		return "$SERVER_ICONS_RELATIVE/$hostname-$size.webp";
	}

	/**
	 * Process uploaded server icon and create resized versions.
	 * @param string $hostname Hostname of server.
	 * @param string $size Image dimensions.
	 * @return string|null Relative path or null if processing fails.
	 */
	function process_server_icon(string $hostname, string $size): ?string {
		global $CUSTOM_CONTENT_ROOT;
		
		$config = LocalConfig::get_instance();
		$source_path = $config->get_server_icon_file_path($hostname);
		$processed_path = server_icon_path_resized($hostname, $size);
		
		// Check if source file exists
		if (!$source_path) {
			return null;
		}
		
		// Check if processed version exists and is recent
		if (file_exists($processed_path)) {
			$source_mtime = filemtime($source_path);
			$processed_mtime = filemtime($processed_path);
			if ($processed_mtime >= $source_mtime) {
				return server_icon_path_relative($hostname, $size);
			}
		}
		
		// Process and resize the image
		try {
			$image_data = file_get_contents($source_path);
			if (empty($image_data)) {
				return null;
			}
			
			$gd_image = imagecreatefromstring($image_data);
			if (!$gd_image) {
				return null;
			}
			
			// Resize to specified size with proper aspect ratio handling
			$width = imagesx($gd_image);
			$height = imagesy($gd_image);
			
			// Parse size string (e.g., "64x64")
			if (preg_match('/^(\d+)x(\d+)$/', $size, $matches)) {
				$target_width = (int)$matches[1];
				$target_height = (int)$matches[2];
			} else {
				// Default to square if size format is unexpected
				$target_width = $target_height = 64;
			}
			
			// Create target canvas
			$resized = imagecreatetruecolor($target_width, $target_height);
			
			// Calculate resize dimensions maintaining aspect ratio
			if ($width > $height) {
				$new_width = $target_width;
				$new_height = (int)($height * $target_width / $width);
				$src_x = 0;
				$src_y = 0;
			} else {
				$new_height = $target_height;
				$new_width = (int)($width * $target_height / $height);
				$src_x = 0;
				$src_y = 0;
			}
			
			// Resize and center on target canvas
			$temp = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($temp, $gd_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			
			// Fill with transparent background
			imagealphablending($resized, false);
			imagesavealpha($resized, true);
			$transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
			imagefill($resized, 0, 0, $transparent);
			imagealphablending($resized, true);
			
			// Center the resized image on the target canvas
			$offset_x = (int)(($target_width - $new_width) / 2);
			$offset_y = (int)(($target_height - $new_height) / 2);
			
			imagecopy($resized, $temp, $offset_x, $offset_y, 0, 0, $new_width, $new_height);
			
			// Ensure output directory exists
			if (!is_dir(dirname($processed_path))) {
				mkdir(dirname($processed_path), 0755, true);
			}
			
			// Save as WebP
			if (!imagewebp($resized, $processed_path)) {
				imagedestroy($gd_image);
				imagedestroy($temp);
				imagedestroy($resized);
				return null;
			}
			
			imagedestroy($gd_image);
			imagedestroy($temp);
			imagedestroy($resized);
			
			return server_icon_path_relative($hostname, $size);
			
		} catch (Exception $e) {
			log_error("Failed to process server icon for $hostname: " . $e->getMessage());
			return null;
		}
	}

	/**
	 * Fetch the icon of the given Community server and return its relative path.
	 * @param CommunityServer $server
	 * @param string $size Image dimensions.
	 * @return string Relative path or null if icon is absent.
	 */
	function server_icon(CommunityServer $server, string $size): ?string {
		$config = LocalConfig::get_instance();
		$icon_value = $config->get_server_icon_file($server);
		
		if (!$icon_value) {
			return "";
		}
		
		// Check if icon_value is a hostname (direct file approach)
		$hostname = $server->get_hostname(include_port: false);
		if ($icon_value === $hostname) {
			return process_server_icon($hostname, $size);
		}
		
		// Fallback to room-based approach (existing behavior)
		$room = $server->get_room_by_token($icon_value);
		
		if (!$room) {
			$hostname = $server->get_hostname();
			log_warning("Room $icon_value on $hostname does not exist, cannot be used as icon.");
			return "";
		}
		
		return room_icon($room, $size);
	}
?>