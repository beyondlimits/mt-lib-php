<?php namespace Minetest;

use Exception;

class StringHelper
{
	public static function split ($s)
	{
		$result = [];
		$state = 0;
		$i = 0;
		$n = strlen($s);

		for ($i = 0; $i < $n; $i++) {
			$c = $s[$i];
			switch ($state)
			{
				case 0:
					if ($c === '"') {
						$temp = '';
						$state = 2;
					} elseif ($c !== ' ') {
						$temp = $c;
						$state = 1;
					}
					break;

				case 1:
					if ($c === ' ') {
						$result[] = $temp;
						$state = 0;
					} else {
						$temp .= $c;
					}
					break;

				case 2:
					if ($c === '\\') {
						$state = 3;
					} elseif ($c === '"') {
						$result[] = $temp;
						$state = 0;
					} else {
						$temp .= $c;
					}
					break;

				case 3:
					switch ($c)
					{
						case 'b':
							$temp .= "\b";
							break;

						case 'f':
							$temp .= "\b";
							break;

						case 'n':
							$temp .= "\n";
							break;

						case 'r':
							$temp .= "\r";
							break;

						case 't':
							$temp .= "\t";
							break;

#           case 'u':
#             throw new Exception('Unicode characters are not supported');

						default:
							$temp .= $c;
							$state = 2;
					}
					break;
			}
		}

		if ($state == 1) {
			$result[] = $temp;
		}

		return $result;
	}

	public static function join (array $array)
	{
		$result = [];

		foreach ($array as $s) {
			$s = (string) $s;

			if ($s == '') {
				$result[] = '""';
				continue;
			}

			$n = strlen($s);
			$e = false;
			for ($i = 0; $i < $n; $i++) {
				$c = $s[$i];
				if ($c == ' ' || $c == "\n") {
					$e = true;
					break;
				}
			}
			if ($e) {
				$temp = '';
				for ($i = 0; $i < $n; $i++) {
					$c = $s[$i];
					switch ($c)
					{
						case "\n";
							$temp .= "\\n";
							break;

						case '"':
							$temp .= '\\"';
							break;

						case '\\';
							$temp .= '\\\\';
							break;

						default:
							$temp .= $c;
					}
				}
				$result[] = '"' . $temp . '"';
			} else {
				$result[] = $s;
			}
		}

		return implode(' ', $result);
	}

	public static function parseInt($s)
	{
		if ($s === null) {
			throw new Exception('Integer expected');
		}

		$s = (string) $s;

		if (!ctype_digit($s)) {
			throw new Exception('Integer expected');
		}

		return $s + 0;
	}

	public static function parseSignedInt($s)
	{
		if ($s === null) {
			throw new Exception('Integer expected');
		}

		$s = (string) $s;

		if (!ctype_digit($s[0] == '-' ? substr($s, 1) : $s)) {
			throw new Exception('Integer expected');
		}

		return $s + 0;
	}
}
