<?php
	/**
	 * \file
	 * Provide language flags for hardcoded Communities.
	 */

	/**
	 * @var array<string, string> $server_languages
	 *
	 * Dictionary of language flags for hardcoded Communities.
	 *
	 * The array key a Community ID (long or legacy short).
	 */
	$server_languages = [];

	// https://open.getsession.org/
	$server_languages[] = array(
		"crypto+a03c"                          => "🇬🇧",
		"lokinet+a03c"                         => "🇬🇧",
		"lokinet-updates+a03c"                 => "🇬🇧",
		"oxen+a03c"                            => "🇬🇧",
		"oxen-updates+a03c"                    => "🇬🇧",
		"session-dev+a03c"                     => "🇬🇧",
		"session-farsi+a03c"                   => "🇮🇷",
		"session-updates+a03c"                 => "🇬🇧",
		"session+a03c"                         => "🇬🇧"
	);

	/**
	 * @var string[] $languages
	 * Array matching room identifier or server public key to language flag.
	 */
	$languages = array_merge(...$server_languages);
?>
