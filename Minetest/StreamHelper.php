<?php namespace Minetest;

use Exception;

class StreamHelper
{
	private $stream;
	private $own;

	public function __construct($arg)
	{
		if (is_string($arg)) {
			$this->stream = fopen('php://memory', 'rb+');
			fwrite($this->stream, $arg);
			rewind($this->stream);
			$this->own = true;
		} elseif (is_resource($arg)) {
			$this->stream = $arg;
			$this->own = false;
		} else {
			throw new Exception('Only string or stream accepted');
		}
	}

	public function __destruct()
	{
		if ($this->own) {
			fclose($this->stream);
		}
	}

	public function readByte()
	{
		$value = fgetc($this->stream);

		if ($value === false) {
			throw new Exception('Premature termination of stream');
		}

		return ord($value);
	}

	public function readShort()
	{
		$value = fread($this->stream, 2);

		if ($value === false || strlen($value) != 2) {
			throw new Exception('Premature termination of stream');
		}

		$value = unpack('nvalue', $value);

		return $value['value'];
	}

	public function readUnsignedShort()
	{
		return $this->readShort();
	}

	public function readSignedShort()
	{
		$value = $this->readShort();
		return $value < 32768 ? $value : $value - 65536;
	}

	public function readInt()
	{
		$value = fread($this->stream, 4);

		if ($value === false || strlen($value) != 4) {
			throw new Exception('Premature termination of stream');
		}

		$value = unpack('Nvalue', $value);

		return $value['value'];
	}

	public function readUnsignedInt()
	{
		$value = $this->readInt();
		return $value < 0 ? $value + 4294967296 : $value;
	}

	public function readSignedInt()
	{
		$value = $this->readInt();
		return $value < 2147483648 ? $value : $value - 4294967296;
	}

	public function readString($n)
	{
		$value = fread($this->stream, $n);

		if ($value === false || strlen($value) != $n) {
			throw new Exception('Premature termination of stream');
		}

		return $value;
	}

	public function readStringLF()
	{
		$result = '';

		for (;;) {
			$c = fgetc($this->stream);

			if ($c === false) {
				throw new Exception('Premature termination of stream');
			}

			if ($c == "\n") {
				return $result;
			}

			$result .= $c;
		}
	}

	public function readString16()
	{
		return $this->readString($this->readShort());
	}

	public function readString32()
	{
		return $this->readString($this->readInt());
	}

	public function read($format)
	{
		# C char
		# u int 16
		# U int 32
		# I signed int 32
		# s string 16
		# S string 32

		$func = array(
			'C' => 'readByte',
			'u' => 'readUnsignedShort',
			'U' => 'readUnsignedInt',
			'i' => 'readSignedShort',
			'I' => 'readSignedInt',
			's' => 'readString16',
			'S' => 'readString32',
		);

		$n = strlen($format);
		$result = array();
		for ($i = 0; $i < $n; $i++) {
			$c = $format[$i];

			if (!array_key_exists($c, $func))
				throw new Exception("Unsupported token: $c");

			$result[] = call_user_func(array($this, $func[$c]));
		}

		return $result;
	}

	public function dump($filename = 'dump')
	{
		file_put_contents($filename, stream_get_contents($this->stream));
	}
}
