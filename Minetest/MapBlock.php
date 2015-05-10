<?php namespace Minetest;

require_once dirname(__FILE__) . '/StreamHelper.php';
require_once dirname(__FILE__) . '/Metadata.php';
require_once dirname(__FILE__) . '/Inventory.php';
require_once dirname(__FILE__) . '/Node.php';

use Exception;

class MapBlock
{
	const IS_UNDERGROUND = 1;
	const DAY_NIGHT_DIFFERS = 2;
	const LIGHTING_EXPIRED = 4;
	const GENERATED = 8;
	const COMPRESSION_LEVEL = 9;
	const NODES = 4096;
	const SIZE = 16;

	private $version;
	private $flags;
	private $objects;
	private $timestamp;
	private $nodes;

	public function __construct($data = null)
	{
		if ($data === null) {
			$this->version = 25;
			$this->flags = 0;
			$this->objects = array();
			$this->timestamp = 0;
			for ($i = 0; $i < self::NODES; $i++) {
				$this->nodes[] = new Node;
			}
		} else {
			$this->load($data);
		}
	}

	public function __get($name)
	{
		switch ($name)
		{
			case 'version':
				return $this->version;

			case 'flags':
				return $this->flags;

			case 'timestamp':
				return $this->timestamp;

			case 'is_underground':
			case 'isUnderground':
				return (bool)($this->flags & self::IS_UNDERGROUND);

			case 'day_night_differs':
			case 'dayNightDiffers':
				return (bool)($this->flags & self::DAY_NIGHT_DIFFERS);

			case 'lighting_expired':
			case 'lightingExpired':
				return (bool)($this->flags & self::LIGHTING_EXPIRED);

			case 'generated':
				return (bool)($this->flags & self::GENERATED);

			case 'nodes':
				return $this->nodes;

			default:
				throw new Exception("Unknown MapBlock property: $name");
		}
	}

	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'version':
			case 'flags':
				if (!is_int($value) || $value < 0 || $value > 255)
					throw new Exception("Invalid value for $name: $value");

				$this->$name = $value;
				break;

			case 'timestamp':
				if (!is_int($value))
					throw new Exception('Timestamp must be an integer');

				$this->timestamp = $value;
				break;

			case 'is_underground':
			case 'isUnderground':
				$this->flags = $value
					? ($this->flags | self::IS_UNDERGROUND)
					: ($this->flags & ~self::IS_UNDERGROUND);
				break;

			case 'day_night_differs':
			case 'dayNightDiffers':
				$this->flags = $value
					? ($this->flags | self::DAY_NIGHT_DIFFERS)
					: ($this->flags & ~self::DAY_NIGHT_DIFFERS);
				break;

			case 'lighting_expired':
			case 'lightingExpired':
				$this->flags = $value
					? ($this->flags | self::LIGHTING_EXPIRED)
					: ($this->flags & ~self::LIGHTING_EXPIRED);
				break;

			case 'generated':
				$this->flags = $value
					? ($this->flags | self::GENERATED)
					: ($this->flags & ~self::GENERATED);
				break;

			default:
				throw new Exception("Unknown MapBlock property: $name");
		}
	}

	public function load($data)
	{
		$data = (string) $data;

		if ($data[3] == '') {
			throw new Exception('Data malformed');
		}

		$version = ord($data[0]);
		$flags = ord($data[1]);
		$cw = ord($data[2]);
		$pw = ord($data[3]);

		# version supported: 24, 25
		# versions older than 24:
		#   the single byte nodes are fucking odd
		#   and will not be supported (ver 22, 23)
		#   see mapformat.txt, section "Format of nodes"
		if ($version != 24 && $version != 25) {
			throw new Exception('Unsupported block version: ' . $version);
		}

		# content width must be 2
		if ($cw != 2) {
			throw new Exception("Invalid node ID width: $cw");
		}

		# params width must be 2
		if ($pw != 2) {
			throw new Exception("Invalid parameters width: $pw");
		}

		# zlib-compressed node data
		list ($params, $p) = self::unZlib(substr($data, 4));
		$p += 4;

		# zlib-compressed node metadata list
		list ($s, $n) = self::unZlib(substr($data, $p));
		$meta = new StreamHelper($s);
		$data = new StreamHelper(substr($data, $p + $n));

		$nodes = array();

		switch ($cw)
		{
			case 1:
				# 1 byte per node id, 1 byte per param1 and 1 byte per param2
				if (strlen($params) != 3*self::NODES) {
					throw new Exception("Invalid length of node data for cw = 1: $n");
				}

				$p0 = 0;
				$p1 = self::NODES;
				$p2 = 2*self::NODES;

				do {
					$param0 = ord($params[$p0]);
					$param1 = ord($params[$p1++]);
					$param2 = ord($params[$p2++]);

					$nodes[$p0++] = $param0 < 128
												? new Node($param0, $param1, $param2)
												: new Node(($param0 << 4) | ($param2 >> 4), $param1, $param2 & 15);
				} while ($p0 < self::NODES);

				break;

			case 2:
				# 2 bytes per node id, 1 byte per param1 and 1 byte per param2
				if (strlen($params) != 4*self::NODES) {
					throw new Exception("Invalid length of node data for cw = 2: $n");
				}

				$p = 0;
				$p0 = 0;
				$p1 = 2*self::NODES;
				$p2 = 3*self::NODES;

				do {
					$param0 = ord($params[$p0++]) << 8;
					$param0 |= ord($params[$p0++]);
					$param1 = ord($params[$p1++]);
					$param2 = ord($params[$p2++]);

					$nodes[$p++] = new Node($param0, $param1, $param2);
				} while ($p < self::NODES);

				break;

			# other cases already checked with some code above
		}

		# metadata version
		$ver = $meta->readByte();

		switch ($ver)
		{
			case 0:
				break;

			case 1:
				# count of metadata
				$n = $meta->readShort();

				for ($i = 0; $i < $n; $i++) {
					$pos = $meta->readShort();
					if ($pos >= self::NODES) {
						throw new Exception("Invalid metadata position: $pos");
					}

					$node = $nodes[$pos];

					if ($node->metadata !== null) {
						throw new Exception("Multiple metadata for node at $pos");
					}

					$type = $meta->readShort();

					if ($type != 0) {
						throw new Exception("Unknown metadata type: $type");
					}

					$node->metatype = $type;
					$node->metadata = $meta;  # __set call
					$node->inventory = $meta; # __set call
				}
				break;

			default:
				throw new Exception("Unsupported metadata version: $ver");
		}

		switch ($version)
		{
			case 23:
				# u8 unused version (always 0)
				$ver = $data->readByte();
				if ($ver != 0) {
					throw new Exception("Unsupported timer version: $ver");
				}
				break;

			case 24:
				# timer version
				$ver = $data->readByte();

				switch ($ver)
				{
					case 0:
						# nothing
						break;

					case 1:
						$n = $data->readShort();

						for ($i = 0; $i < $n; $i++) {
							$pos = $data->readShort();

							if ($pos >= self::NODES) {
								throw new Exception("Invalid timer position: $pos");
							}

							$node = $nodes[$pos];

							if ($node->timeout !== null) {
								throw new Exception("Multiple timers for node at $pos");
							}

							$node->timeout = $data->readSignedInt();
							$node->elapsed = $data->readSignedInt();
						}
						break;

					default:
						throw new Exception("Unsupported timer version: $ver");
				}
				break;
		}

		$ver = $data->readByte();

		if ($ver != 0) {
			throw new Exception("Unsupported static object version: $ver");
		}

		$n = $data->readShort();
		$objects = array();

		for ($i = 0; $i < $n; $i++) {
			$objects[] = new StaticObject($data);
		}

		$timestamp = $data->readInt();

		# ID TO NAME MAPPINGS

		$ver = $data->readByte();

		if ($ver != 0)
			throw new Exception("Unsupported name-id-mapping version: $ver");

		$n = $data->readShort();
		$map = array();

		for ($i = 0; $i < $n; $i++) {
			$id = $data->readShort();
			$map[$id] = $data->readString16();
		}

		foreach ($nodes as $node) {
			$name = $node->name;

			if (!array_key_exists($name, $map)) {
				throw new Exception("Mapping not defined for id $name");
			}

			$node->name = $map[$name];
		}

		if ($version == 25) {
			$size = $data->readByte();

			if ($size != 10) {
				throw new Exception("Invalid timer data size: $size");
			}

			$n = $data->readShort();

			for ($i = 0; $i < $n; $i++) {
				$pos = $data->readShort();

				if ($pos >= self::NODES) {
					throw new Exception("Invalid timer position $pos");
				}

				$node = $nodes[$pos];

				if ($node->timeout !== null) {
					throw new Exception('Timer already set');
				}

				$node->timeout = $data->readSignedInt();
				$node->elapsed = $data->readSignedInt();
			}
		}

		$this->version = $version;
		$this->flags = $flags;
		$this->objects = $objects;
		$this->timestamp = $timestamp;
		$this->nodes = $nodes;
	}

	public function data()
	{
		if ($this->version != 24 && $this->version != 25) {
			throw new Exception('Unsupported block version');
		}

		# $cw = $this->version < 24 ? 1 : 2;
		# $cw = 2;
		# $pw = 2;

		$data = pack('C*', $this->version, $this->flags, 2, 2);

		$next = 0;
		$map = [];

		$nodes = '';
		$param1 = '';
		$param2 = '';
		$meta = '';
		$metacount = 0;
		$timers = '';
		$timercount = 0;

		for ($i = 0; $i < self::NODES; $i++) {
			$node = $this->nodes[$i];
			$name = $node->name;

			if (array_key_exists($name, $map)) {
				$nodes .= pack('n', $map[$name]);
			} else {
				$map[$name] = $next;
				$nodes .= pack('n', $next);
				$next++;
			}

			$param1 .= chr($node->param1);
			$param2 .= chr($node->param2);

			if (!$node->metadata) {
				$node->metadata = new Metadata;
			}

			if (!$node->inventory) {
				$node->inventory = new Inventory;
			}

			if (!$node->metadata->isEmpty() || !$node->inventory->isEmpty()) {
				$meta .= pack('nn', $i, $node->metatype)
							 . $node->metadata->data()
							 . $node->inventory->data();

				$metacount++;
			}

			if (isset($node->timeout)) {
				$timers .= pack('nNN', $i, $node->timeout, $node->elapsed);
				$timercount++;
			}
		}

		$data .= gzcompress($nodes . $param1 . $param2, self::COMPRESSION_LEVEL)
					 . gzcompress(pack('nn', 1, $metacount) . $meta, self::COMPRESSION_LEVEL);

		switch ($this->version)
		{
			case 23:
				$data .= "\0";
				break;

			case 24:
				$data = $timercount ? (pack('Cn', 1, $timercount) . $timers) : "\0";
				break;
		}

		$data .= pack('Cn', 0, count($this->objects));

		foreach ($this->objects as $o) {
			$data .= $o->data();
		}

		$data .= pack('NCn', $this->timestamp, 0, count($map));

		foreach ($map as $name => $id) {
			$data .= pack('nn', $id, strlen($name)) . $name;
		}

		if ($this->version == 25) {
			$data .= pack('Cn', 10, $timercount) . $timers;
		}

		return $data;
	}

	private static function unZlib ($data)
	{
		$a = 0;
		$b = strlen($data);

		while ($a != $b) {
			$c = ($a + $b) >> 1;

			$test = @gzuncompress(substr($data, 0, $c) . "\x00");

			if ($test !== FALSE) {
				$test = @gzuncompress(substr($data, 0, $c) . "\xFF");
			}

			if ($test === FALSE) {
				$a = ++$c;
			} else {
				$result = $test;
				$b = $c;
			}
		}

		if (!isset($result)) {
			throw new Exception('Error in compressed data.');
		}

		return [$result, $c];
	}

	public function all()
	{
		return $this->nodes;
	}

	public function get($x, $y = null, $z = null)
	{
		switch (func_num_args())
		{
			case 1:
				return $this->nodes[self::parsePosition($x)];

			case 3:
				return $this->nodes[self::xyzToPos($x, $y, $z)];

			default:
				throw new Exception(__METHOD__ . ' accepts only 1 or 3 arguments');
		}
	}

	public function set($x, $y = null, $z = null, $node = null)
	{
		switch (func_num_args())
		{
			case 2:
				if (!($y instanceof Node)) {
					throw new Exception('Last argument must be a node');
				}
				$this->nodes[self::parsePosition($x)] = $y;
				break;

			case 4:
				if (!($node instanceof Node)) {
					throw new Exception('Last argument must be a node');
				}
				$this->nodes[self::xyzToPos($x, $y, $z)] = $node;
				break;

			default:
				throw new Exception(__METHOD__ . ' accepts only 2 or 4 arguments');
		}
	}

	public function getAllObjects()
	{
		return $this->objects;
	}

	public function getObject($key)
	{
		return $this->objects[$key];
	}

	public function addObject(StaticObject $object)
	{
		$this->objects[] = $object;
	}

	public function setObject($key, StaticObject $object)
	{
		$this->objects[$key] = $object;
	}

	public function deleteObject($key)
	{
		unset($this->objects[$key]);
	}

	public function deleteAllObjects()
	{
		$this->objects = array();
	}

	public function getObjectCount()
	{
		return count($this->objects);
	}

	private static function parseCoordinate($x)
	{
		$x = StringHelper::parseInt($x);

		if ($x < 0 || $x >= self::SIZE) {
			throw new Exception('Invalid coordinate');
		}

		return $x;
	}

	private static function parseCoordinates($x, $y, $z)
	{
		return array(
			self::parseCoordinate($x),
			self::parseCoordinate($y),
			self::parseCoordinate($z),
		);
	}

	private static function parsePosition($pos)
	{
		$pos = StringHelper::parseInt($pos);

		if ($pos < 0 || $pos >= self::NODES) {
			throw new Exception("Invalid position: $pos");
		}

		return $pos;
	}

	private static function xyzToPos($x, $y, $z)
	{
		list ($x, $y, $z) = self::parseCoordinates($x, $y, $z);
		$y <<= 4;
		$z <<= 8;
		return $x | $y | $z;
	}

	private static function posToString($pos)
	{
		$pos = self::parsePosition($pos);
		$z = $pos & 15;
		$y = ($pos >> 4) & 15;
		$x = $pos >> 8;
		return "($x, $y, $z)";
	}
}
