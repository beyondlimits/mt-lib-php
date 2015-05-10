<?php namespace Minetest;

require_once dirname(__FILE__) . '/StreamHelper.php';
require_once dirname(__FILE__) . '/ItemStack.php';

use Exception;

class InventoryList
{
	private $inventory = array();
	private $size = 0;
	private $width = 0;

	// int size
	// array with inventories
	// resource stream
	// streamhelper
	// string with data

	public function __construct($arg = null)
	{
		if ($arg !== null) {
			if (is_int($arg)) {
				$this->size = $arg;
				for ($i = 0; $i < $arg; $i++) {
					$this->inventory[] = new ItemStack;
				}
			} elseif (is_array($arg)) {
				$this->size = count($arg);
				foreach ($arg as $stack) {
					$this->inventory[] = self::castItemStack($stack);
				}
			} elseif (is_string($arg) || is_resource($arg)) {
				$this->parse(new StreamHelper($arg));
			} elseif ($arg instanceof StreamHelper) {
				$this->parse($arg);
			} else {
				throw new Exception(__METHOD__ . ' accepts only integer, array, string, file handle or StreamHelper');
			}
		}
	}

	private static function toItemStack($stack)
	{
		if ($stack instanceof ItemStack) {
			return $stack;
		}

		return new ItemStack($stack);
	}

	private function parse(StreamHelper $sh)
	{
		for (;;) {
			$s = $sh->readStringLF();
			if ($s == 'Empty') {
				$this->inventory[] = new ItemStack;
			} elseif ($s == 'EndInventoryList') {
				$this->size = count($this->inventory);
				return;
			} elseif (preg_match('/^Width (?P<width>\d+)$/', $s, $m)) {
				# no idea what is this.
				$this->width = $m['width'];
			} elseif (preg_match('/^Item (?P<itemstack>.+)$/', $s, $m)) {
				$this->inventory[] = new ItemStack($m['itemstack']);
			} else {
				throw new Exception("Invalid inventory entry: '$s'");
			}
		}
	}

	public function data()
	{
		$data = 'Width ' . $this->width . "\n";

		foreach ($this->inventory as $stack) {
			$data .= $stack->data();
		}

		return $data . "EndInventoryList\n";
	}

	private function parseIndex($index)
	{
		$index = StringHelper::parseInt($index);

		if ($index < 0 || $index >= $this->size) {
			throw new Exception('Index out of bounds');
		}

		return $index;
	}

	public function all()
	{
		return $this->inventory;
	}

	public function get($index)
	{
		$index = $this->parseIndex($index);
		return $this->inventory[$index];
	}

	public function set($index, $value)
	{
		$index = $this->parseIndex($index);
		$this->inventory[$index] = self::castItemStack($value);
	}

	// hmmm...
	public function size()
	{
		return $this->size;
	}

	public function __get($name)
	{
		return $this->$name;
	}
}
