<?php namespace Minetest;

require_once dirname(__FILE__) . '/Map.php';
require_once dirname(__FILE__) . '/MapBlock.php';

class NodeFinder
{
	private $map;
	private $result = array();
	private $callback;

	public function __construct(Map $map, Callable $callback = null)
	{
		$this->map = $map;
		$this->callback = $callback;
	}

	public function search ($x0, $y0, $z0, $x1, $y1, $z1, $callback = null)
	{
		$bx0 = $x0 >> 4;
		$by0 = $y0 >> 4;
		$bz0 = $z0 >> 4;
		$bx1 = ($x1 + 1) >> 4;
		$by1 = ($y1 + 1) >> 4;
		$bz1 = ($z1 + 1) >> 4;

		if ($callback === null) {
			$callback = $this->callback;
		}

		for ($bz = $bz0; $bz <= $bz1; $bz++) {
			$rz0 = $bz << 4;
			for ($by = $by0; $by <= $by1; $by++) {
				$ry0 = $by << 4;
				for ($bx = $bx0; $bx <= $bx1; $bx++) {
					$rx0 = $bx << 4;

					$block = $this->map->get($bx, $by, $bz);

					echo "Scanning block ($bx, $by, $bz)\n";

					if ($block !== null) {
						$block = new MapBlock($block);

						$nx0 = $x0 - $rx0;
						if ($nx0 < 0) {
							$nx0 = 0;
						}

						$ny0 = $y0 - $ry0;
						if ($ny0 < 0) {
							$ny0 = 0;
						}

						$nz0 = $z0 - $rz0;
						if ($nz0 < 0) {
							$nz0 = 0;
						}

						$nx1 = $x1 - $rx0;
						if ($nx1 > 15) {
							$nx1 = 15;
						}

						$ny1 = $y1 - $ry0;
						if ($ny1 > 15) {
							$ny1 = 15;
						}

						$nz1 = $z1 - $rz0;
						if ($nz1 > 15) {
							$nz1 = 15;
						}

						for ($nz = $nz0; $nz <= $nz1; $nz++)
						for ($ny = $ny0; $ny <= $ny1; $ny++)
						for ($nx = $nx0; $nx <= $nx1; $nx++) {
							$result = $callback($block->get($nx, $ny, $nz));
							if ($result !== null) {
								$this->result[] = array(
									'x' => $rx0 + $nx,
									'y' => $ry0 + $ny,
									'z' => $rz0 + $nz,
									'node' => $result,
								);
							}
						}
					}
				}
			}
		}
	}

	public function get()
	{
		return $this->result;
	}

	public function count()
	{
		return count($this->result);
	}

	public function clear()
	{
		$this->result = array();
	}
}
