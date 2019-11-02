<?php

namespace pocketmine\block;

use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

abstract class Thin extends Transparent {

	public function isSolid() {
		return false;
	}

	protected function recalculateBoundingBox() {
		$f = 0.4375;
		$f1 = 0.5625;
		$f2 = 0.4375;
		$f3 = 0.5625;

		$flag = $this->canConnect($this->getSide(Vector3::SIDE_NORTH));
		$flag1 = $this->canConnect($this->getSide(Vector3::SIDE_SOUTH));
		$flag2 = $this->canConnect($this->getSide(Vector3::SIDE_WEST));
		$flag3 = $this->canConnect($this->getSide(Vector3::SIDE_EAST));

		if ((!$flag2 || !$flag3) && ($flag2 || $flag3 || $flag || $flag1)) {
			if ($flag2 && !$flag3) {
				$f = 0;
			} elseif (!$flag2 && $flag3) {
				$f1 = 1;
			}
		} else {
			$f = 0;
			$f1 = 1;
		}

		if ((!$flag || !$flag1) && ($flag2 || $flag3 || $flag || $flag1)) {
			if ($flag && !$flag1) {
				$f2 = 0;
			} elseif (!$flag && $flag1) {
				$f3 = 1;
			}
		} else {
			$f2 = 0;
			$f3 = 1;
		}

		return new AxisAlignedBB(
			$this->x + $f,
			$this->y,
			$this->z + $f2,
			$this->x + $f1,
			$this->y + 1,
			$this->z + $f3
		);
	}


	public function canConnect(Block $block){
		return $block->isSolid() || $block->getId() === $this->getId() || $block->getId() === self::GLASS_PANE || $block->getId() === self::GLASS;
	}

}