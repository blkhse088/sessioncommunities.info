<?php
	/**
	 * \file
	 * Implement logging messages to console.
	 */

	/**
	 * @var int[] $hrtime_start
	 * Seconds and nanoseconds at start of logging period.
	 */
	$hrtime_start = hrtime();

	/**
	 * @var int $NANOSEC
	 * Number of nanoseconds in a second.
	 */
	$NANOSEC = 1E9;

	/**
	 * Describes verbosity levels of diagnostic logs.
	 */
	final class LoggingVerbosity {
		// Prevent class instantiation
		private function __construct() {}

		/**
		 * Error log verbosity constant.
		 */
		const Error = 10;
		/**
		 * Warning log verbosity constant.
		 */
		const Warning = 20;
		/**
		 * Info log verbosity constant.
		 */
		const Info = 30;
		/**
		 * Debug log verbosity constant.
		 */
		const Debug = 40;

		/**
		 * Returns the proper letter to mark the given message verbosity.
		 * @param int $verbosity Numeric LoggingVerbosity value.
		 * @return string One-letter string denoting the given verbosity class.
		 */
		static function getVerbosityMarker(int $verbosity) {
			return match($verbosity) {
				LoggingVerbosity::Error => 'e',
				LoggingVerbosity::Warning => 'w',
				LoggingVerbosity::Info => 'i',
				LoggingVerbosity::Debug => 'd'
			};
		}

		/**
		 * Terminal escape sequence to clear formatting.
		 */
		private const COLOR_RESET = "\033[0m";

		/**
		 * Specifies whether to enable terminal colors.
		 */
		public static bool $showColor = true;

		/**
		 * Returns the color marker for the given logging verbosity if colors are enabled.
		 * @param int $verbosity Logging verbosity to used for printing.
		 * @return ?string Terminal escape sequence to color foreground text.
		 */
		static function getVerbosityColorMarker(int $verbosity): ?string {
			// See https://en.wikipedia.org/wiki/ANSI_escape_code#Colors for reference.
			if (!LoggingVerbosity::$showColor) {
				return '';
			}
			return match($verbosity) {
				LoggingVerbosity::Error => "\033[31m",
				LoggingVerbosity::Warning => "\033[93m",
				LoggingVerbosity::Debug => "\033[90m",
				default => ''
			};
		}

		/**
		 * @return ?string Terminal escape sequence to turn off color if colors are enabled.
		 */
		static function getColorResetMarker(): ?string {
			if (!LoggingVerbosity::$showColor) {
				return '';
			}
			return LoggingVerbosity::COLOR_RESET;
		}

		/**
		 * Returns a pair of optíons triggering the given verbosity.
		 * @param int $verbosity Logging verbosity to set using flag.
		 * @return string[] Pair of short and long command-line verbosity flags.
		 */
		static function getVerbosityFlags(int $verbosity): array {
			return match($verbosity) {
				LoggingVerbosity::Debug => ["-v", "--verbose"],
				default => ['', '']
			};
		}
	}

	/**
	 * Calculate current process runtime as [s, ns].
	 * @return int[] Seconds and nanoseconds.
	 */
	function hrtime_interval() {
		global $hrtime_start, $NANOSEC;
		list($s, $ns) = hrtime();
		list($s0, $ns0) = $hrtime_start;
		// Borrow
		if ($ns < $ns0) { $s--; $ns += $NANOSEC; }
		return [$s - $s0, $ns - $ns0];
	}

	/**
	 * Format current process runtime to millisecond precision.
	 * @return string Runtime ninutes, seconds, and milliseconds as string.
	 */
	function runtime_str(): string {
		list($s, $ns) = hrtime_interval();
		return (
			date('i:s.', $s) .
			str_pad(intdiv($ns, 1E6), 3, "0", STR_PAD_LEFT)
		);
	}

	/**
	 * @private
	 */
	function _log_message(?string $msg, int $message_verbosity) {
		global $LOGGING_VERBOSITY;
		if ($message_verbosity > $LOGGING_VERBOSITY) return;
		$runtime = runtime_str();
		$marker = LoggingVerbosity::getVerbosityMarker($message_verbosity);
		$color_marker = LoggingVerbosity::getVerbosityColorMarker($message_verbosity);
		$color_reset = LoggingVerbosity::getColorResetMarker();
		// Need to concatenate marker to avoid interpolated array member syntax.
		fwrite(STDERR, $color_marker . "[$runtime] [$marker] $msg$color_reset" . PHP_EOL);
	}

	/**
	 * Logs the given message as an error to stderr.
	 * Only logs when `$LOGGING_VERBOSITY` is Error and below.
	 * @param string $msg String message to log.
	 */
	function log_error(?string $msg) {
		debug_print_backtrace();
		_log_message($msg, LoggingVerbosity::Error);
	}

	/**
	 * Logs the given message as a warning to stderr.
	 * Only logs when `$LOGGING_VERBOSITY` is Warning and below.
	 * @param string $msg String message to log.
	 */
	function log_warning(?string $msg) {
		_log_message($msg, LoggingVerbosity::Warning);
	}

	/**
	 * Logs the given message as an info message to stderr.
	 * Only logs when `$LOGGING_VERBOSITY` is Info and below.
	 * @param string $msg String message to log.
	 */
	function log_info(?string $msg) {
		_log_message($msg, LoggingVerbosity::Info);
	}

	/**
	 * Logs the given message as a debug message to stderr.
	 * Only logs when `$LOGGING_VERBOSITY` is Debug and below.
	 * @param string $msg String message to log.
	 */
	function log_debug(?string $msg) {
		_log_message($msg, LoggingVerbosity::Debug);
	}

	/**
	 * Logs the given value in a debug message to stderr.
	 * Only logs when `$LOGGING_VERBOSITY` is debug and below.
	 * @param mixed $value Value to log.
	 * @param int $message_verbosity Verbosity to use when logging value. Default: Debug.
	 */
	function log_value(mixed $value, int $message_verbosity = LoggingVerbosity::Debug) {
		_log_message(var_export($value, true), $message_verbosity);
	}

	/**
	 * @var int $LOGGING_VERBOSITY
	 * Global setting.
	 * Controls how detailed the displayed logs are.
	 */
	$LOGGING_VERBOSITY = LoggingVerbosity::Info;
?>
