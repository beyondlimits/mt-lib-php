<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

function search($haystack, $needles)
{
	foreach ((array) $needles as $needle) {
		if (strpos($haystack, $needle) !== false) {
			return true;
		}
	}
	return false;
}

require_once 'Minetest/Map.php';
require_once 'Minetest/MapBlock.php';

use Minetest\Map;
use Minetest\MapBlock;

$term = $argv;
unset ($term[0]);

if (empty($term)) {
	exit;
}

$map = new Map('map.sqlite');

foreach ($map->all() as $pos) {
	$block = $map->get($pos);

	$bx = fmod($pos, 4096);
	if ($bx >= 2048) {
		$bx -= 4096;
	} elseif ($bx < -2048) {
		$bx += 4096;
	}
	$pos -= $bx;
	$pos /= 4096;

	$by = fmod($pos, 4096);
	if ($by >= 2048) {
		$by -= 4096;
	} elseif ($by < -2048) {
		$by += 4096;
	}
	$pos -= $by;
	$pos /= 4096;

	$bz = $pos;
	if ($bz >= 2048) {
		$bz -= 4096;
	} elseif ($bz < -2048) {
		$bz += 4096;
	}

	if (empty($block)) {
		echo "Invalid block at pos ($bx, $by, $bz)\n";
		continue;
	}

	$bx <<= 4;
	$by <<= 4;
	$bz <<= 4;

	if (search($block, $term)) {
		$block = new MapBlock($block);

		for ($z = 0; $z < 16; $z++)
		for ($y = 0; $y < 16; $y++)
		for ($x = 0; $x < 16; $x++) {
			$node = $block->get($x, $y, $z);
			$name = $node->name;
			if (search($name, $term)) {
				printf("%6d,%6d,%6d -> %s\n", $bx | $x, $by | $y, $bz | $z, $name);
			}
		}
	}
}
