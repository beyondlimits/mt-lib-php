<?php namespace Minetest;

# there should be something that
# FIRST CLASS (kind of DB provider): load/store/delete raw data from raw pos
# SECOND CLASS: converts (x,y,z) from/to raw pos
# THIRD CLASS: converts MapBlocks from/to raw data

require_once dirname(__FILE__) . '/MapBlock.php';
require_once dirname(__FILE__) . '/Node.php';
require_once dirname(__FILE__) . '/StaticObject.php';

use Exception;
use SQLite3;

class Map
{
	private $db;
	private $writable;
	private $stmt;
	private $del;

	public function __construct($filename, $writable = false)
	{
		$this->db = new SQLite3($filename, $writable ? SQLITE3_OPEN_READWRITE : SQLITE3_OPEN_READONLY);
		$this->writable = $writable;

		if ($writable) {
			$this->stmt = $this->db->prepare("INSERT OR REPLACE INTO blocks VALUES (:pos, :data)");
			$this->del = $this->db->prepare("DELETE FROM blocks WHERE pos = :pos");
		}
	}

	public function __destruct()
	{
		$this->db->close();
	}

	public function get($x, $y = null, $z = null)
	{
		switch (func_num_args())
		{
			case 1:
				$pos = StringHelper::parseSignedInt($x);
				break;

			case 3:
				$pos = self::xyzToPos($x, $y, $z);
				break;

			default:
				throw new Exception(__METHOD__ . ' accepts only 1 or 3 arguments');
		}

		return $this->db->querySingle("SELECT data FROM blocks WHERE pos = $pos");
	}

	public function set($x, $y, $z, $data)
	{
		if (!$this->writable) {
			throw new Exception('Cannot write to read-only map');
		}

		switch (func_num_args())
		{
			case 2:
				if (!is_string($y)) {
					throw new Exception('Last argument must be a string');
				}
				$pos = StringHelper::parseSignedInt($x);
				break;

			case 4:
				if (!is_string($data)) {
					throw new Exception('Last argument must be a string');
				}
				$pos = self::xyzToPos($x, $y, $z);
				break;

			default:
				throw new Exception(__METHOD__ . ' accepts only 2 or 4 arguments');
		}

		$this->stmt->reset();
		$this->stmt->bindValue('pos', $pos, SQLITE3_INTEGER);
		$this->stmt->bindValue('data', $data, SQLITE3_BLOB);

		return (bool) $this->stmt->execute();
	}

	public function delete($x, $y = null, $z = null)
	{
		if (!$this->writable) {
			throw new Exception('Cannot write to read-only map');
		}

		switch (func_num_args())
		{
			case 1:
				$pos = StringHelper::parseSignedInt($x);
				break;

			case 3:
				$pos = self::xyzToPos($x, $y, $z);
				break;

			default:
				throw new Exception(__METHOD__ . ' accepts only 1 or 3 arguments');
		}

		$this->del->reset();
		$this->del->bindValue('pos', $pos, SQLITE3_INTEGER);

		return (bool) $this->del->execute();
	}

	public function all()
	{
		$q = $this->db->query('SELECT pos FROM blocks');
		$result = [];

		while ($r = $q->fetchArray(SQLITE3_NUM)) {
			$result[] = $r[0];
		}

		return $result;
	}

	private static function parseCoordinate($x)
	{
		$x = StringHelper::parseSignedInt($x);

		if ($x < -2048 || $x >= 2048) {
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

	private static function xyzToPos($x, $y, $z)
	{
		list ($x, $y, $z) = self::parseCoordinates($x, $y, $z);
		# php is stupid and cuts to 32 bits
		# $y <<= 12;
		# $z <<= 24;
		$y *= 4096;
		$z *= 16777216;
		return $x + $y + $z;
	}
}
