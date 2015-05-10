<?php namespace Minetest;

require_once dirname(__FILE__) . '/StreamHelper.php';

use Exception;

class StaticObject
{
	private $type = 0;
	private $data = '';
	private $x = 0;
	private $y = 0;
	private $z = 0;

	public function __construct ($x = 0, $y = 0, $z = 0, $type = 0, $data = '')
	{
		switch (func_num_args())
		{
			case 0:
				break;

			case 1:
				if ($x !== null) {
					if (is_array($x)) {
						$this->fill($x);
					} elseif (is_string($x) || is_resource($x)) {
						$this->parse(new StreamHelper($x));
					} elseif ($x instanceof StreamHelper) {
						$this->parse($x);
					} else {
						throw new Exception(__METHOD__ . ' accepts only array, string, file handle or StreamHelper');
					}
				}
				break;

			case 3:
			case 4:
			case 5:
				$this->__set('x', $x);
				$this->__set('y', $y);
				$this->__set('z', $z);
				$this->__set('type', $type);
				$this->__set('data', $data);
				break;

			default:
				throw new Exception(__METHOD__ . ' accepts only 1, 3, 4 or 5 arguments');
		}
	}

	private function parse (StreamHelper $sh)
	{
		list ($this->type, $this->x, $this->y, $this->z, $this->data) = $sh->read('CIIIs');
	}

	public function data ()
	{
		return pack('CNNNn', $this->type, $this->x, $this->y, $this->z, strlen($this->data)) . $this->data;
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
		switch ($name)
		{
			case 'x':
			case 'y':
			case 'z':
				$this->name = StringHelper::parseInt($value);
				break;

			case 'type':
				$value = StringHelper::parseInt($value);

				if ($value < 0 || $value > 255) {
					throw new Exception("Invalid value for type: $value");
				}

				$this->type = $value;
				break;

			case 'data':
				$this->data = (string) $value;
				break;

			default:
				throw new Exception("Unknown property: $name");
		}
	}
}
