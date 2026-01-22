<?php
	/**
	 * \file
	 * Declare basic numeric constants and implement basic numeric operations.
	 */

	/**
	 * @var int $MINUTE_SECONDS
	 * Number of seconds in a minute.
	 */
	$MINUTE_SECONDS = 60;

	/**
	 * @var int $HOUR_SECONDS
	 * Number of seconds in an hour.
	 */
	$HOUR_SECONDS = 60 * $MINUTE_SECONDS;

	/**
	 * @var int $DAY_SECONDS
	 * Number of seconds in a day.
	 */
	$DAY_SECONDS = 24 * $HOUR_SECONDS;

	/**
	 * @var int $WEEK_SECONDS
	 * Number of seconds in a week.
	 */
	$WEEK_SECONDS = 7 * $DAY_SECONDS;

	/**
	 * Format a duration in seconds to human-readable format.
	 * @param int $duration_seconds Duration in seconds.
	 * @return string Duration string including number and unit.
	 */
	function format_duration(int $duration_seconds): string {
		global $WEEK_SECONDS, $DAY_SECONDS, $HOUR_SECONDS, $MINUTE_SECONDS;

		if ($duration_seconds >= $WEEK_SECONDS) {
			return floor($duration_seconds / $WEEK_SECONDS) . ' week(s)';
		}
		if ($duration_seconds >= $DAY_SECONDS) {
			return floor($duration_seconds / $DAY_SECONDS) . ' day(s)';
		}
		if ($duration_seconds >= $HOUR_SECONDS) {
			return floor($duration_seconds / $HOUR_SECONDS) . ' hour(s)';
		}
		if ($duration_seconds >= $MINUTE_SECONDS) {
			return floor($duration_seconds / $MINUTE_SECONDS) . 'minute(s)';
		}

		return floor($duration_seconds) . 's';
	}

?>
