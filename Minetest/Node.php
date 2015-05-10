<?php namespace Minetest;

require_once dirname(__FILE__) . '/Metadata.php';
require_once dirname(__FILE__) . '/Inventory.php';
require_once dirname(__FILE__) . '/StreamHelper.php';
require_once dirname(__FILE__) . '/StringHelper.php';

use Exception;

class Node
{
	private $name;
	private $param1;
	private $param2;
	private $metatype;
	private $metadata;
	private $inventory;
	private $timeout;
	private $elapsed;

	public function __construct($name = 'ignore', $param1 = 0, $param2 = 0)
	{
		$this->__set('name', $name);
		$this->__set('param1', $param1);
		$this->__set('param2', $param2);
	}

	public function __get($name)
	{
		return $this->$name;
	}

	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'name':
				$this->name = (string) $value;
				break;

			case 'param1':
			case 'param2':
				$value = StringHelper::parseInt($value);
				if ($value < 0 || $value > 255) {
					throw new Exception("Invalid value for $name: $value");
				}
				$this->$name = $value;
				break;

			case 'metatype':
				$value = StringHelper::parseInt($value);
				if ($value < 0 || $value > 65535) {
					throw new Exception("Invalid value for metatype: $value");
				}
				$this->$name = $value;
				break;

			case 'timeout':
			case 'elapsed':
				$this->$name = $value === null
										 ? null
										 : StringHelper::parseInt($value);
			break;

			case 'metadata':
				$this->metadata = ($value === null || $value instanceof Metadata)
												? $value
												: new Metadata($value);
				break;

			case 'inventory':
				$this->inventory = ($value === null || $value instanceof Inventory)
												 ? $value
												 : new Inventory($value);
				break;

			default:
				throw new Exception("Property not defined: $name");
		}
	}

//   public function getMeta()
//   {
//     return $this->metadata;
//   }
//
//   public function setMeta(array $meta = [])
//   {
//     $this->metadata = $meta;
//   }
//
//   public function get($name)
//   {
//     return array_key_exists($name, $this->metadata)
//          ? $this->metadata[$name]
//          : null;
//   }
//
//   public function set($name, $value = null)
//   {
//     if (isset($value))
//       $this->metadata[$name] = (string) $value;
//     else
//       unset($this->metadata[$name]);
//   }
//
//   public function has($name)
//   {
//     return array_key_exists($name, $this->metadata);
//   }

//   public function getInventory()
//   {
//     return $this->inventory;
//   }
//
//   public function setInventory($value)
//   {
//     $this->inventory = (string) $value;
//   }
//
	public function __toString()
	{
		return $this->name;
	}
}
