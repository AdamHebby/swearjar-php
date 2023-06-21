<?php

namespace swearjar;

use \Symfony\Component\Yaml\Yaml;

/**
 * Profanity tester class.
 *
 * @package swearjar
 */
class Tester {
	protected array $matchers = array();

	/**
	 * Constructor
	 */
	public function __construct(?string $file = null) {
		if ($file === null) {
			$file = __DIR__ . '/config/en.yml';
		}

		$this->loadFile($file);
	}

	/**
	 * Loads a YAML file containing rules for matching profanity.
	 */
	public function loadFile(string $path): void {
		$this->matchers = Yaml::parse(file_get_contents($path));
	}

	/**
	 * Scans `$text` looking for profanity. The callback is invoked on
	 * each instance of profanity.
	 *
	 * The signature of `$callback` is:
	 *
	 *    function ($word, $index, $types) { ... }
	 *
	 * Where `$word` is the possible profane word, `$index` is the offset of the word
	 * in the text, and `$types` is a list of tags for the word.
	 */
	public function scan(string $text, \Closure $callback): void {
		preg_match_all('/\w+/u', $text, $matches, PREG_OFFSET_CAPTURE);

		foreach ($matches[0] as $match) {
			[$word, $index] = $match;

			$key = mb_strtolower($word);

			if (array_key_exists($key, $this->matchers['simple'])) {
				if ($callback->__invoke($word, $index, $this->matchers['simple'][$key]) === false) {
					return;
				}
			}
		}

		foreach ($this->matchers['regex'] as $regex => $types) {
			preg_match_all('/' . $regex . '/i', $text, $matches, PREG_OFFSET_CAPTURE);
			foreach ($matches[0] as $match) {
				[$word, $index] = $match;

				if ($callback->__invoke($word, $index, $types) === false) {
					return;
				}
			}
		}
	}

	/**
	 * Returns true if `$text` contains profanity
	 */
	public function profane(string $text): bool {
		$profane = false;

		$this->scan($text, function($word, $index, $types) use (&$profane) {
			$profane = true;
			return false;
		});

		return $profane;
	}

	/**
	 * Analyzes `$text` and generates a report of the type of profanity found.
	 *
	 * @return array<string,int>
	 */
	public function scorecard(string $text): array {
		$scorecard = [];

		$this->scan($text, function($word, $index, $types) use (&$scorecard) {
			foreach ($types as $type) {
				$scorecard[$type] = ($scorecard[$type] ?? 0) + 1;
			}

			return true;
		});

		return $scorecard;
	}

	/**
	 * Scans `$text` and censors profanity.
	 */
	public function censor(string $text, bool $hint = false): string {
		$censored = $text;

		$offset = $hint ? 1 : 0;

		$this->scan($text, function($word, $index, $types) use (&$censored, $offset) {
			$censoredWord = preg_replace('/\S/', '*', $word);
			$censored = mb_substr($censored, 0, $index + $offset) . mb_substr($censoredWord, $offset) . mb_substr($censored, $index + mb_strlen($word));
			return true;
		});

		return $censored;
	}
}
