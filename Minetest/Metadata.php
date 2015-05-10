<?php namespace Minetest;

require_once dirname(__FILE__) . '/StreamHelper.php';

use Exception;

class Metadata
{
	private $data = array();

	# accepts:
	# - array with keys => values
	# - binary metadata
	# - stream
	# - StreamHelper
	# - nothing
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
#   $type = $sh->readShort();
#   if ($type != 0)
#     throw new Exception("Unknown metadata type: $type");

		$n = $sh->readShort();
		for ($i = 0; $i < $n; $i++) {
			list ($key, $value) = $sh->read('sS');
			$this->add($key, $value);
		}
	}

	# returns binary metadata
	public function data()
	{
		$data = pack('n', count($this->data));
		foreach ($this->data as $key => $value) {
			$data .= pack('n', strlen($key)) . $key . pack('N', strlen($value)) . $value;
		}
		return $data;
	}

	# not really needed, but a shorthand
	public function has($key)
	{
		return array_key_exists($key, $this->data);
	}

	# required, because $this->metadata is not a reference
	public function add($key, $value)
	{
		if (array_key_exists($key, $this->data)) {
			throw new Exception("Key $key already exists.");
		}

		if ($value !== null) {
			$this->data[$key] = (string) $value;
		}
	}

	# required, because $this->metadata is not a reference
	public function delete($key)
	{
		unset ($this->data[$key]);
	}

	# not really needed; time will tell
	public function all()
	{
		return $this->data;
	}

	# returns null when key does not exist
	public function get($key)
	{
		return array_key_exists($key, $this->data)
				 ? $this->data[$key]
				 : null;
	}

	# required, because $this->metadata is not a reference
	public function set($key, $value = null)
	{
		if ($value === null) {
			unset($this->data[$key]);
		} else {
			$this->data[$key] = (string) $value;
		}
	}

	# merges the arrays
	public function fill(array $array)
	{
		foreach ($array as $key => $value) {
			$this->set($key, $value);
		}
	}

	public function count()
	{
		return count($this->data);
	}

	# not really needed; time will tell
	public function isEmpty()
	{
		return empty($this->data);
	}

	public function __get($name)
	{
		return $this->get($name);
	}

	public function __set($name, $value)
	{
		$this->set($name, $value);
	}
}
