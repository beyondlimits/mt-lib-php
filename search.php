<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

require_once 'Minetest/Map.php';
require_once 'Minetest/NodeFinder.php';

use Minetest\Map;
use Minetest\NodeFinder;

if ($argc < 7) {
	exit;
}

$map = new Map('map.sqlite');

$px = $argv[1];
$py = $argv[2];
$pz = $argv[3];
$rh = $argv[4];
$rv = $argv[5];

$term = $argv;
unset ($term[0]);
unset ($term[1]);
unset ($term[2]);
unset ($term[3]);
unset ($term[4]);
unset ($term[5]);

$count = array();

$nf = new NodeFinder($map, function ($node) use ($term) {
	foreach ($term as $name) {
		if (fnmatch($name, $node->name)) {
			return $node;
		}
	}
});

$nf->search($px - $rh, $py - $rv, $pz - $rh,
						$px + $rh, $py + $rv, $pz + $rh);

$result = $nf->get();

usort($result, function($a, $b) use ($px, $pz) {
	if ($a['y'] != $b['y']) return $b['y'] - $a['y'];
	$p = abs($a['x'] - $px) + abs($a['z'] - $pz);
	$q = abs($b['x'] - $px) + abs($b['z'] - $pz);
	if ($p != $q) return $p - $q;
});

foreach ($result as $node) {
	printf("%6d,%6d,%6d -> %s\n", $node['x'], $node['y'], $node['z'], $node['node']->name);
}

