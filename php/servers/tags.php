<?php
	/**
	 * \file
	 * Implement maintainer-given and derived tags for Community rooms.
	 */

	require_once 'utils/utils.php';

	/**
	 * Enumerates types of labels applied to Communities.
	 */
	class TagType {
		private function __construct() {}
		/**
		 * Specifies custom tag added by Community maintainer or Community source.
		 */
		const USER_TAG = 'user';
		/**
		 * Specifies basic type of tag reserved for assignment by our aggregator.
		 */
		const RESERVED_TAG = 'reserved';
		/**
		 * Specifies warning tag reserved for assignment by our aggregator.
		 */
		const WARNING_TAG = 'warning';
	}

	/**
	 * Represents a label applied to Communities
	 */
	class CommunityTag implements JsonSerializable {
		/**
		 * Create a new CommunityTag instance.
		 * @param string $text Text the tag should read.
		 * @param string $tag_type {@link TagType} enumeration value.
		 */
		public function __construct(
			string $text,
			string $tag_type = TagType::USER_TAG,
		) {
			$this->text = CommunityTag::preprocess_tag($text);
			$this->type = $tag_type;
		}

		/**
		 * @var string $type
		 * Tag type as given by a {@link TagType} value.
		 */
		public readonly string $type;

		/**
		 * @var string $text
		 * The string tag itself.
		 */
		public readonly string $text;

		/**
		 * Return a lowercase representation of the tag for purposes of de-duping.
		 */
		public function __toString(): string {
			return strtolower($this->text);
		}

		/**
		 * Return a lowercase representation of the tag for use in display.
		 */
		public function get_text(): string {
			return strtolower($this->text);
		}

		/**
		 * Return a lowercase text representation of the tag for use in HTML.
		 */
		public function get_text_sanitized(): string {
			return html_sanitize($this->get_text());
		}

		/**
		 * @var string[] $descriptions
		 * Dictionary of reserved tag descriptions.
		 */
		public static array $descriptions = [];

		/**
		 * Return the tag's description.
		 *
		 * @return string
		 */
		public function get_description_sanitized(): string {
			// Feels out-of-place anywhere else.
			global $TAGS_FILE;
			if (empty(CommunityTag::$descriptions)) {
				CommunityTag::loadSerializedClassData(file_get_contents($TAGS_FILE));
			}
			return html_sanitize(CommunityTag::$descriptions[$this->text] ?? "Tag: $this->text");
		}

		/**
		 * Associate the current tag's text with the given description.
		 *
		 * @param string $description New description for the current tag text.
		 *
		 * @return CommunityTag Return self.
		 */
		public function set_description_globally(string $description): self {
			CommunityTag::$descriptions[$this->text] = $description;
			return $this;
		}

		/**
		 * Serialize tag data into a string.
		 *
		 * @return string JSON data
		 */
		public static function serializeClassData(): string {
			return json_encode(CommunityTag::$descriptions);
		}

		/**
		 * Load tag data from the given serialized string.
		 *
		 * @param string $data JSON string of tag descriptions.
		 *
		 * @return void
		 */
		public static function loadSerializedClassData(string $data) {
			CommunityTag::$descriptions = json_decode($data, associative: true);
		}

		/**
		 * Produce data used to serialize the tag.
		 */
		public function jsonSerialize(): mixed {
			$details = [];
			$details['text'] = $this->get_text();
			$details['type'] = $this->get_tag_type();
			return $details;
		}

		private static function preprocess_tag(?string $tag) {
			$tag = trim($tag);

			if (strlen($tag) == 0) {
				return $tag;
			}

			$tag = html_entity_decode($tag);

			if ($tag[0] == '#') {
				return substr($tag, 1);
			}

			return strtolower($tag);
		}

		/**
		 * @param string[] $tag_array
		 * @return CommunityTag[]
		 */
		private static function from_string_tags(array $tag_array) {
			$tags = array_filter(
				$tag_array, function(?string $tag) {
					return strlen($tag) != 0;
				}
			);

			$tags = CommunityTag::dedupe_tags($tags);

			return array_map(function(string $tag) {
				return new CommunityTag($tag);
			}, $tags);
		}

		/**
		 * Constructs the tags given, removing any reserved tags.
		 * @param string[] $tags
		 * @param bool $remove_redundant Removes meaningless tags.
		 * @return CommunityTag[]
		 */
		public static function from_user_tags(
			array $tags,
			bool $remove_redundant = true
		): array {
			$tags_user = array_values(array_filter(
				$tags,
				function($tag) {
					return !CommunityTag::is_reserved_tag($tag);
				}
			));

			$tags_built = CommunityTag::from_string_tags($tags_user);

			if ($remove_redundant) {
				$tags_built = array_values(
					array_filter($tags_built, function(CommunityTag $tag) {
						return !in_array($tag->get_text(), CommunityTag::REDUNDANT_TAGS);
					})
				);
			}

			return $tags_built;
		}

		/**
		 * @param array $details Deserialized tag info.
		 * @return CommunityTag
		 */
		public static function from_details(array $details): CommunityTag {
			return new CommunityTag(
				$details['text'],
				$details['type']
			);
		}


		/**
		 * @param array[] $details_array Array of deserialized tag info.
		 * @return CommunityTag[]
		 */
		public static function from_details_array(array $details_array): array {
			return array_map(function($details) {
				return CommunityTag::from_details($details);
			}, $details_array);
		}

		/**
		 * @param CommunityTag[] $tags
		 * @return CommunityTag[]
		 */
		public static function dedupe_tags(array $tags) {
			return array_values(array_unique($tags));
		}

		/**
		 * Return a HTML classname corresponding to the tag.
		 */
		public function get_tag_classname(): string {
			$tag_type = $this->get_tag_type();
			$classname = "tag-$tag_type";
			if (CommunityTag::is_showcased_tag($this->text)) {
				$classname .= " tag-showcased";
			}
			if (CommunityTag::is_highlighted_tag($this->text)) {
				$classname .= " tag-highlighted";
			}
			return $classname;
		}

		/**
		 * Return a string representation of the tag's {@link TagType}.
		 * @return "user", "reserved", or "warning", as appropriate.
		 */
		public function get_tag_type(): string {
			return match($this->type) {
				TagType::USER_TAG => 'user',
				TagType::RESERVED_TAG => 'reserved',
				TagType::WARNING_TAG => 'warning'
			};
		}

		/**
		 * @var string[] RESERVED_TAGS
		 * Array of derived tags unavailable for manual tagging.
		 */
		private const RESERVED_TAGS = [
			"official",
			"nsfw",
			"new",
			"modded",
			"moderated",
			"not modded",
			"not moderated",
			"read-only",
			"uploads off",
			"we're here",
			"test",
			"pinned"
		];

		private const SHOWCASED_TAGS = ["official", "new", "we're here", "nsfw", "read-only", "pinned"];

		private const HIGHLIGHTED_TAGS = ["new", "we're here", "pinned"];

		private const REDUNDANT_TAGS = [];

		/**
		 * @var string[] NSFW_KEYWORDS
		 * Keywords indicating a not-safe-for-work Community.
		 */
		public const NSFW_KEYWORDS = ["nsfw", "porn", "erotic", "18+", "sex"];

		/**
		 * Check whether the given user tag is reserved by our aggregator.
		 *
		 * @param string $tag String tag to check.
		 *
		 * @return bool
		 */
		public static function is_reserved_tag(string $tag): bool {
			return in_array(strtolower($tag), CommunityTag::RESERVED_TAGS);
		}

		/**
		 * Return true if the tag should be given a chance to appear in more crowded views.
		 *
		 * @param string $tag String tag to check.
		 *
		 * @return bool
		 */
		public static function is_showcased_tag(string $tag): bool {
			return in_array(strtolower($tag), CommunityTag::SHOWCASED_TAGS);
		}

		/**
		 * Return true if the tag should be given visibility in more crowded views.
		 *
		 * @param string $tag String tag to check.
		 *
		 * @return bool
		 */
		public static function is_highlighted_tag(string $tag): bool {
			return in_array(strtolower($tag), CommunityTag::HIGHLIGHTED_TAGS);
		}
	}

	/**
	 * Constructs Community tags reserved by the aggregator.
	 */
	class ReservedTags {
		/**
		 * Return "official" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function official(): CommunityTag {
			$CHECK_MARK = "✅";

			return (new CommunityTag(
				"official",
				TagType::RESERVED_TAG,
			))->set_description_globally("This Community is maintained by the Session team. $CHECK_MARK");
		}

		/**
		 * Return "nsfw" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function nsfw(): CommunityTag {
			$WARNING_ICON = "⚠️";

			return (new CommunityTag(
				"nsfw",
				TagType::WARNING_TAG,
			))->set_description_globally("This Community may contain adult material. $WARNING_ICON");
		}

		/**
		 * Return "moderated" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function moderated(): CommunityTag {
			$CHECK_MARK = "✅";

			return (new CommunityTag(
				"moderated",
				TagType::RESERVED_TAG,
			))->set_description_globally("This Community seems to have enough moderators. $CHECK_MARK");
		}

		/**
		 * Return "not_modded" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function not_modded(): CommunityTag {
			$WARNING_ICON = "⚠️";

			return (new CommunityTag(
				"not modded",
				TagType::WARNING_TAG,
			))->set_description_globally("This Community does not seem to have enough moderators. $WARNING_ICON");
		}

		/**
		 * Return "read_only" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function read_only(): CommunityTag {
			return (new CommunityTag(
				"read-only",
				TagType::RESERVED_TAG,
			))->set_description_globally("This Community is read-only.");
		}

		/**
		 * Return "no_upload_permission" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function no_upload_permission(): CommunityTag {
			return (new CommunityTag(
				"uploads off",
				TagType::RESERVED_TAG,
			))->set_description_globally("This Community does not support uploading files or link previews.");
		}

		/**
		 * Return "recently_created" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function recently_created(): CommunityTag {
			return (new CommunityTag(
				"new",
				TagType::RESERVED_TAG,
			))->set_description_globally("This Community was created recently.");
		}

		/**
		 * Return "used_by_project" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function used_by_project(): CommunityTag {
			return (new CommunityTag(
				"we're here",
				TagType::RESERVED_TAG,
			))->set_description_globally("The sessioncommunities.info maintainer(s) can post updates "
			. "or respond to feedback in this Community.");
		}

		/**
		 * Return "testing" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function testing(): CommunityTag {
			return (new CommunityTag(
				"test",
				TagType::RESERVED_TAG,
			))->set_description_globally("This Community is intended for testing only.");
		}

		/**
		 * Return "stickied" reserved Community tag.
		 *
		 * @return CommunityTag
		 */
		 public static function stickied(): CommunityTag {
			return (new CommunityTag(
				"pinned",
				TagType::RESERVED_TAG,
			))->set_description_globally("This Community has been pinned for greater visibility. 📌");
		}
	}
?>
