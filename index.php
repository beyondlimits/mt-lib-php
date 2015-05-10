<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

require_once 'Minetest/Map.php';
require_once 'Minetest/MapBlock.php';
require_once 'Minetest/LuaEntity.php';

use Minetest\Map;
use Minetest\MapBlock;
use Minetest\LuaEntity;

$map = new Map('map.sqlite');

$block = $map->get(-20, 0, -148);

if ($block) {
	$block = new MapBlock($block);
	print_r($block);
}
