<?php namespace Minetest;

require_once dirname(__FILE__) . '/StreamHelper.php';

use Exception;

class LuaEntity
{
	private $name = '';
	private $data = '';
	private $hp = 0;
	private $vx = 0;
	private $vy = 0;
	private $vz = 0;
	private $yaw = 0;

	public function __construct($arg = null)
	{
		if ($arg !== null) {
			if (is_array($arg)) {
				$this->fill($arg);
			} elseif (is_string($arg) || is_resource($arg)) {
				$this->parse(new StreamHelper($arg));
			} elseif ($arg instanceof StreamHelper) {
				$this->parse($arg);
			} else {
				throw new Exception(__METHOD__ . ' accepts only array, string, file handle or StreamHelper');
			}
		}
	}

	private function parse(StreamHelper $sh)
	{
		$version = $sh->readByte();

		if ($version != 1) {
			throw new Exception("Unsupported LuaEntity version: $version");
		}

		list ($this->name, $this->data, $this->hp, $this->vx, $this->vy, $this->vz, $this->yaw) = $sh->read('sSiIIII');
	}

	public function data()
	{
		return pack('Cn', 1, strlen($this->name)) . $this->name
				 . pack('N', strlen($this->data)) . $this->data
				 . pack('nNNNN', $this->hp, $this->vx, $this->vy, $this->vz, $this->yaw);
	}

	public function fill(array $array)
	{
		foreach ($array as $key => $value) {
			$this->__set($key, $value);
		}
	}

	public function __get($name)
	{
		return $this->$name;
	}

	public function __set($name, $value)
	{
		$value = (string) $value;

		switch ($name)
		{
			case 'name':
			case 'data':
				$this->$name = $value;
			break;

			case 'hp':
				$value = StringHelper::parseSignedInt($value);

				if ($value < -32768 || $value > 32767) {
					throw new Exception("Invalid value for hp: $value");
				}

				$this->hp = $value;
				break;

			case 'vx':
			case 'vy':
			case 'vz':
			case 'yaw':
				$this->$name = StringHelper::parseSignedInt($value);
				break;

			default:
				throw new Exception("Unknown property: $name");
		}
	}
}
