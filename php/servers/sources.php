<?php
	require_once 'utils/utils.php';
	require_once 'servers/tags.php';

	/**
	 * Provides tags for Session Communities.
	 */
	interface CommunitySourceWithTags {
		/**
		 * Produce an array of string tags for all Communities found.
		 * @return string[][] Array associating room IDs with string tag arrays.
		 */
		public function get_tags(): array;
	}



	/**
	 * Finds Communities on the web.
	 */
	class CommunitySources {
		private const SOURCES = array(
			'SIMP' => 'https://simplifiedprivacy.com/techgroups',
			'EURO' => 'https://euroexit.net',
		);

		private readonly string $contents_simp;
		private readonly string $contents_euro;
		private readonly string $contents_aggregated;

		/**
		 * Arraying associating room identifiers with arrays of raw tags.
		 * @var array<string,string[]> $room_tags
		 */
		private array $room_tags = [];

		/**
		 * Creates a new CommunitySources instance with processed Community data from the Web.
		 */
		public function __construct() {

			log_info("Requesting SimplifiedPrivacy.com list...");
			$this->contents_simp = CommunitySources::fetch_source('SIMP');

			log_info("Requesting EURO Carrd list...");
                        $this->contents_euro = CommunitySources::fetch_source('EURO');

			log_info('Done fetching sources.');

			$this->contents_aggregated =
				$this->contents_simp .
				$this->contents_euro ;
		}

		private static function source_cache_file(string $source_key) {
			global $SOURCES_CACHE;
			return "$SOURCES_CACHE/$source_key";
		}

		private static function fetch_source(string $source_key) {
			$url = CommunitySources::SOURCES[$source_key];

			$contents = file_get_contents($url);
			log_debug($http_response_header[0]);
			$cache_file = CommunitySources::source_cache_file($source_key);

			if ($contents) {
				file_put_contents($cache_file, $contents);
				return $contents;
			}

			$contents = file_get_contents($cache_file);
			if ($contents) {
				log_warning("Could not fetch source from $url, using cache");
				return $contents;
			}

			log_error("Could not fetch source from $url.");
			return "";
		}

		/**
		 * @param string[][] $tags Array associating room IDs to tag arrays
		 */
		private function add_tags(array $tags) {
			foreach ($tags as $room_id => $room_tags) {
				if (!isset($this->room_tags[$room_id])) {
					$this->room_tags[$room_id] = [];
				}

				$this->room_tags[$room_id] = [
					...$this->room_tags[$room_id],
					...$room_tags
				];
			}
		}


		/**
		 * Return all known join links to Session Communities.
		 * @return string[] Join URLs.
		 */
		public function get_join_urls(): array {
			return array_unique(
				parse_join_links($this->contents_aggregated)
			);
		}

		/**
		 * Return all known tags for the given room.
		 * @param string $room_id Room identifier.
		 * @return CommunityTag[] Array of string tags.
		 */
		public function get_room_tags($room_id): array {
			if (!isset($this->room_tags[$room_id])) {
				return [];
			}

			return $this->room_tags[$room_id];
		}
	}

	file_exists($SOURCES_CACHE) or mkdir($SOURCES_CACHE, 0755, recursive: true);
?>
