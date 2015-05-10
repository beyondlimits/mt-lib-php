<?php namespace Minetest;

require_once dirname(__FILE__) . '/StreamHelper.php';
require_once dirname(__FILE__) . '/InventoryList.php';

use Exception;

class Inventory
{
	private $inventory = array();

	public function __construct($arg = null)
	{
		if ($arg !== null) {
			if (is_array($arg)) {
				foreach ($arg as $list) {
					$this->stacks[] = self::castInventoryList($list);
				}
			} elseif (is_string($arg) || is_resource($arg)) {
				$this->parse(new StreamHelper($arg));
			} elseif ($arg instanceof StreamHelper) {
				$this->parse($arg);
			} else {
				throw new Exception('Array, string, file handle or StreamHelper expected');
			}
		}
	}

	private static function castInventoryList($list)
	{
		if ($list instanceof InventoryList) {
			return $list;
		}

		return new InventoryList($list);
	}

	private function parse (StreamHelper $sh)
	{
		for (;;) {
			$s = $sh->readStringLF();
			if ($s === 'EndInventory') {
				return;
			} elseif (preg_match('/^List (?P<name>[^ ]+) (?P<size>\d+)$/', $s, $m)) {
				$name = $m['name'];
				if (array_key_exists($name, $this->inventory)) {
					throw new Exception("Duplicate inventory definition: $name");
				}
				$inventory = new InventoryList($sh);
				$this->inventory[$name] = $inventory;
				if ($inventory->size != $m['size']) {
					throw new Exception('Inventory size does not match');
				}
			} else {
				throw new Exception("Invalid inventory entry: '$s'");
			}
		}
	}

	public function data()
	{
		$data = '';

		foreach ($this->inventory as $name => $inventory) {
			$size = $inventory->size();
			$data .= "List $name " . $inventory->size() . "\n" . $inventory->data();
		}

		return $data . "EndInventory\n";
	}

	public function has($key)
	{
		return array_key_exists($key, $this->inventory);
	}

	public function add($key, $value)
	{
		if (array_key_exists($key, $this->metadata)) {
			throw new Exception("Key $key already exists.");
		}

		if ($value !== null) {
			$this->inventory[$key] = self::castInventoryList($value);
		}
	}

	public function delete($key)
	{
		unset ($this->inventory[$key]);
	}

	public function all()
	{
		return $this->inventory;
	}

	public function get($key)
	{
		return array_key_exists($key, $this->inventory)
				 ? $this->inventory[$key]
				 : null;
	}

	public function set($key, $value)
	{
		if ($value === null) {
			unset($this->inventory[$key]);
		} else {
			$this->inventory[$key] = self::castInventoryList($value);
		}
	}

	public function isEmpty()
	{
		return empty($this->inventory);
	}

	public function __get($name)
	{
		return $this->$name;
	}
}
