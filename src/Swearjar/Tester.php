<?php

declare(strict_types=1);

namespace Swearjar;

use \Symfony\Component\Yaml\Yaml;

/**
 * Profanity tester class.
 *
 * @package Swearjar
 */
class Tester {
	protected array $matchers = array();
	private bool $leetCodeDetection = false;

	public const FILTER_BLASPHEMY = 'blasphemy';
	public const FILTER_DISCRIMINATORY = 'discriminatory';
	public const FILTER_INAPPROPRIATE = 'inappropriate';
	public const FILTER_INSULT = 'insult';
	public const FILTER_SEXUAL = 'sexual';

	/**
	 * Constructor
	 */
	public function __construct(?string $file = null) {
		if ($file === null) {
			$file = __DIR__ . '/../../config/en.yml';
		}

		$this->loadFile($file);
	}

	public function enableLeetcodeDetection(bool $enabled = true): self {
		$this->leetCodeDetection = $enabled;
		return $this;
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
		$text = $this->replaceLeetCode($text);
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
	 * Returns true if text contains profanity of the given type.
	 *
	 * @param array<string> $filters From Tester::
	 */
	public function containsType(string $text, array $filters): bool {
		$text     = $this->replaceLeetCode($text);
		$contains = false;
		$filters  = array_flip(array_map('strtolower', $filters));

		$this->scan($text, function (string $word, int $index, array $types) use (&$contains, $filters) {
			foreach ($types as $type) {
				if (isset($filters[$type])) {
					$contains = true;
					return false;
				}
			}

			return true;
		});

		return $contains;
	}

	/**
	 * Returns true if `$text` contains profanity
	 */
	public function profane(string $text): bool {
		$text    = $this->replaceLeetCode($text);
		$profane = false;

		$this->scan($text, function (string $word, int $index, array $types) use (&$profane) {
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
		$text      = $this->replaceLeetCode($text);
		$scorecard = [];

		$this->scan($text, function (string $word, int $index, array $types) use (&$scorecard) {
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

		$this->scan($text, function (string $word, int $index, array $types) use (&$censored, $offset) {
			$censoredWord = preg_replace('/\S/', '*', $word);
			$censored = (
				mb_substr($censored, 0, $index + $offset) .
				mb_substr($censoredWord, $offset) .
				mb_substr($censored, $index + mb_strlen($word))
			);
			return true;
		});

		return $censored;
	}

	/**
	 * A strict scan, primarily for usernames where words don't have spaces, and could have letters in the middle.
	 *
	 * This is very likely to have false positives, so it's not recommended for general use.
	 */
	public function scanStrict(string $text): bool {
		$text = $this->replaceLeetCode($text);

		foreach ($this->matchers['regex'] as $regex => $types) {
			if (preg_match('/' . $regex . '/i', $text)) {
				return true;
			}
		}

		foreach ($this->matchers['simple'] as $word => $types) {
			if (mb_stripos($text, $word) !== false) {
				return true;
			}
		}

		return false;
	}

	private function replaceLeetCode(string $text): string {
		if (!$this->leetCodeDetection) {
			return $text;
		}

		$leet = array(
			'a' => array('@', '4'),
			'b' => array('8', '13', '6'),
			'c' => array('('),
			'd' => array(')'),
			'e' => array('3'),
			'f' => array('ph'),
			'g' => array('6', '9'),
			'h' => array('4', '|-|'),
			'i' => array('1', '!', '|'),
			'j' => array('_|'),
			'k' => array('|<', '1<'),
			'l' => array('1', '|'),
			'm' => array('|\/|', '|v|', '/\/\\'),
			'n' => array('|\/|', '/\/'),
			'o' => array('0'),
			'p' => array('|D'),
			'q' => array('9'),
			'r' => array('12', '|2'),
			's' => array('5', '$'),
			't' => array('7', '+'),
			'u' => array('v'),
			'v' => array('\/'),
			'w' => array('\/\/', 'vv'),
			'x' => array('><', '}{'),
			'y' => array('`/'),
			'z' => array('2'),
		);

		foreach ($leet as $letter => $replacements) {
			foreach ($replacements as $replacement) {
				$text = str_replace($replacement, $letter, $text);
			}
		}

		return $text;
	}
}
