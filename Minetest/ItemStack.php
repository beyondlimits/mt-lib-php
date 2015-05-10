<?php namespace Minetest;

require_once dirname(__FILE__) . '/StringHelper.php';

use Exception;

class ItemStack
{
	private $name = '';
	private $count = 1;
	private $wear = 0;
	private $data = '';

	public function __construct($name = null, $count = null, $wear = null, $data = null)
	{
		if (is_array($name)) {
			foreach (array('name', 'count', 'wear', 'data') as $key) {
				if (array_key_exists($key, $name)) {
					$this->$key = (string) $name[$key];
				}
			}
		} else {
			if (func_num_args() == 1) {
				list ($name, $count, $wear, $data) = array_pad(StringHelper::split($name), 4, null);
			}
			$this->__set('name', $name);
			$this->__set('count', $count);
			$this->__set('wear', $wear);
			$this->__set('data', $data);
		}
	}

	public function getEntityId()
	{
		return 1;
	}

	public function data()
	{
		return $this->isEmpty()
				 ? "Empty\n"
				 : "Item $this\n";
	}

	public function isEmpty()
	{
		return $this->name == '' || empty($this->count);
	}

	public function __toString()
	{
		if ($this->data != '') {
			return StringHelper::join(array($this->name, $this->count, $this->wear, $this->data));
		} elseif ($this->wear) {
			return StringHelper::join(array($this->name, $this->count, $this->wear));
		} elseif ($this->count != 1) {
			return StringHelper::join(array($this->name, $this->count));
		} else {
			return StringHelper::join(array($this->name));
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

			case 'count':
				if ($value == '') {
					$this->count = 1;
					break;
				}

				$this->count = StringHelper::parseInt($value);
			break;

			case 'wear':
				if ($value == '') {
					$this->wear = 0;
					break;
				}

				$this->wear = StringHelper::parseInt($value);
			break;

			default:
				throw new Exception("Unknown property: $name\n");
		}
	}
}
