<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\math;

use pocketmine\level\MovingObjectPosition;
use pocketmine\math\Vector3;

class AxisAlignedBB{

	public $minX;
	public $minY;
	public $minZ;
	public $maxX;
	public $maxY;
	public $maxZ;

	public function __construct($minX, $minY, $minZ, $maxX, $maxY, $maxZ){
		$this->minX = $minX;
		$this->minY = $minY;
		$this->minZ = $minZ;
		$this->maxX = $maxX;
		$this->maxY = $maxY;
		$this->maxZ = $maxZ;
	}

	public function setBounds($minX, $minY, $minZ, $maxX, $maxY, $maxZ){
		$this->minX = $minX;
		$this->minY = $minY;
		$this->minZ = $minZ;
		$this->maxX = $maxX;
		$this->maxY = $maxY;
		$this->maxZ = $maxZ;

		return $this;
	}

	public function addCoord($x, $y, $z){
		return new AxisAlignedBB(
			$x < 0 ? $this->minX + $x : $this->minX, 
			$y < 0 ? $this->minY + $y : $this->minY, 
			$z < 0 ? $this->minZ + $z : $this->minZ, 
			$x > 0 ? $this->maxX + $x : $this->maxX, 
			$y > 0 ? $this->maxY + $y : $this->maxY, 
			$z > 0 ? $this->maxZ + $z : $this->maxZ
		);
	}

	public function grow($x, $y, $z){
		return new AxisAlignedBB($this->minX - $x, $this->minY - $y, $this->minZ - $z, $this->maxX + $x, $this->maxY + $y, $this->maxZ + $z);
	}

	public function expand($x, $y, $z){
		$this->minX -= $x;
		$this->minY -= $y;
		$this->minZ -= $z;
		$this->maxX += $x;
		$this->maxY += $y;
		$this->maxZ += $z;

		return $this;
	}

	public function offset($x, $y, $z){
		$this->minX += $x;
		$this->minY += $y;
		$this->minZ += $z;
		$this->maxX += $x;
		$this->maxY += $y;
		$this->maxZ += $z;

		return $this;
	}

	public function shrink($x, $y, $z){
		return new AxisAlignedBB($this->minX + $x, $this->minY + $y, $this->minZ + $z, $this->maxX - $x, $this->maxY - $y, $this->maxZ - $z);
	}

	public function contract($x, $y, $z){
		$this->minX += $x;
		$this->minY += $y;
		$this->minZ += $z;
		$this->maxX -= $x;
		$this->maxY -= $y;
		$this->maxZ -= $z;

		return $this;
	}

	public function setBB(AxisAlignedBB $bb){
		$this->minX = $bb->minX;
		$this->minY = $bb->minY;
		$this->minZ = $bb->minZ;
		$this->maxX = $bb->maxX;
		$this->maxY = $bb->maxY;
		$this->maxZ = $bb->maxZ;
		return $this;
	}

	public function getOffsetBoundingBox($x, $y, $z){
		return new AxisAlignedBB($this->minX + $x, $this->minY + $y, $this->minZ + $z, $this->maxX + $x, $this->maxY + $y, $this->maxZ + $z);
	}

	public function calculateXOffset(AxisAlignedBB $bb, $x){
		if($bb->maxY <= $this->minY or $bb->minY >= $this->maxY){
			return $x;
		}
		if($bb->maxZ <= $this->minZ or $bb->minZ >= $this->maxZ){
			return $x;
		}
		if($x > 0 and $bb->maxX <= $this->minX){
			$x1 = $this->minX - $bb->maxX;
			if($x1 < $x){
				$x = $x1;
			}
		}
		if($x < 0 and $bb->minX >= $this->maxX){
			$x2 = $this->maxX - $bb->minX;
			if($x2 > $x){
				$x = $x2;
			}
		}

		return $x;
	}

	public function calculateYOffset(AxisAlignedBB $bb, $y){
		if($bb->maxX <= $this->minX or $bb->minX >= $this->maxX){
			return $y;
		}
		if($bb->maxZ <= $this->minZ or $bb->minZ >= $this->maxZ){
			return $y;
		}
		if($y > 0 and $bb->maxY <= $this->minY){
			$y1 = $this->minY - $bb->maxY;
			if($y1 < $y){
				$y = $y1;
			}
		}
		if($y < 0 and $bb->minY >= $this->maxY){
			$y2 = $this->maxY - $bb->minY;
			if($y2 > $y){
				$y = $y2;
			}
		}

		return $y;
	}

	public function calculateZOffset(AxisAlignedBB $bb, $z){
		if($bb->maxX <= $this->minX or $bb->minX >= $this->maxX){
			return $z;
		}
		if($bb->maxY <= $this->minY or $bb->minY >= $this->maxY){
			return $z;
		}
		if($z > 0 and $bb->maxZ <= $this->minZ){
			$z1 = $this->minZ - $bb->maxZ;
			if($z1 < $z){
				$z = $z1;
			}
		}
		if($z < 0 and $bb->minZ >= $this->maxZ){
			$z2 = $this->maxZ - $bb->minZ;
			if($z2 > $z){
				$z = $z2;
			}
		}

		return $z;
	}

	public function intersectsWith(AxisAlignedBB $bb){
		if($bb->maxX > $this->minX and $bb->minX < $this->maxX){
			if($bb->maxY > $this->minY and $bb->minY < $this->maxY){
				return $bb->maxZ > $this->minZ and $bb->minZ < $this->maxZ;
			}
		}

		return false;
	}

	public function isVectorInside(Vector3 $vector){
		if($vector->x <= $this->minX or $vector->x >= $this->maxX){
			return false;
		}
		if($vector->y <= $this->minY or $vector->y >= $this->maxY){
			return false;
		}

		return $vector->z > $this->minZ and $vector->z < $this->maxZ;
	}

	public function getAverageEdgeLength(){
		return ($this->maxX - $this->minX + $this->maxY - $this->minY + $this->maxZ - $this->minZ) / 3;
	}

	public function isVectorInYZ(Vector3 $vector){
		return $vector->y >= $this->minY and $vector->y <= $this->maxY and $vector->z >= $this->minZ and $vector->z <= $this->maxZ;
	}

	public function isVectorInXZ(Vector3 $vector){
		return $vector->x >= $this->minX and $vector->x <= $this->maxX and $vector->z >= $this->minZ and $vector->z <= $this->maxZ;
	}

	public function isVectorInXY(Vector3 $vector){
		return $vector->x >= $this->minX and $vector->x <= $this->maxX and $vector->y >= $this->minY and $vector->y <= $this->maxY;
	}
	
	public function getDistanceToLineStart(Vector3 $lineStart, Vector3 $lineStop) {
		$tMin = ($this->minX - $lineStart->x) / $lineStop->x;
		$tMax = ($this->maxX - $lineStart->x) / $lineStop->x;

		if ($tMin > $tMax) {
			$tmp = $tMin;
			$tMin = $tMax;
			$tMax = $tmp;
		}

		$tYMin = ($this->minY - $lineStart->y) / $lineStop->y;
		$tYMax = ($this->minY - $lineStart->y) / $lineStop->y;

		if ($tYMin > $tYMax) {
			$tmp = $tYMin;
			$tYMin = $tYMax;
			$tYMax = $tmp;
		}

		if (($tMin > $tYMax) || ($tYMin > $tMax)) {
			return false;
		}

		if ($tYMin > $tMin) {
			$tMin = $tYmin;
		}

		if ($tYMax < $tMax) {
			$tMax = $tYMax;
		}

		$tZMin = ($this->minZ - $lineStart->z) / $lineStop->z;
		$tZMax = ($this->maxZ - $lineStart->z) / $lineStop->z;

		if ($tZMin > $tZMax) {
			$tmp = $tZMin;
			$tZMin = $tZMax;
			$tZMax = $tmp;
		}

		if (($tMin > $tZMax) || ($tZMin > $tMax)) {
			return false;
		}

		if ($tZMin > $tMin) {
			$tMin = $tZMin;
		}

		if ($tZMax < $tMax) {
			$tMax = $tZMax;
		}
		
		var_dump($tMin, $tMax);

		return true; 
	}
	
	/**
	 * I don't know yet how it exactly works
	 * 
	 * @param float $dst1
	 * @param float $dst2
	 * @param Vector3 $a
	 * @param Vector3 $b
	 * @param Vector3 $result
	 * @return boolean
	 */
	protected function getIntersection($dst1, $dst2, Vector3 $a, Vector3 $b, Vector3 &$result) {
		if ($dst1 * $dst2 >= 0.0 || $dst1 == $dst2) return false;
		$result->x = $a->x + ($b->x - $a->x) * ($dst1 / ($dst1 - $dst2));
		$result->y = $a->y + ($b->y - $a->y) * ($dst1 / ($dst1 - $dst2));
		$result->z = $a->z + ($b->z - $a->z) * ($dst1 / ($dst1 - $dst2));
		return true;
	}

	/**
	 * I don't know yet how it exactly works
	 * 
	 * @param Vector3 $result
	 * @param type $axis
	 * @return boolean
	 */
	protected function inBox(Vector3 &$result, $axis) {
		if ($axis == 1 && $result->z > $this->minZ && $result->z < $this->maxZ && $result->y > $this->minY && $result->y < $this->maxY) return true;
		if ($axis == 2 && $result->z > $this->minZ && $result->z < $this->maxZ && $result->x > $this->minX && $result->x < $this->maxX) return true;
		if ($axis == 3 && $result->x > $this->minX && $result->x < $this->maxX && $result->y > $this->minY && $result->y < $this->maxY) return true;
		return false;
	}
	
	/**
	 * 
	 * @param Vector3 $a line start
	 * @param Vector3 $b line stop
	 * @param Vector3 $result intersection point
	 * @return boolean
	 */
	public function getIntersectionWithLine(Vector3 $a, Vector3 $b, Vector3 &$result) {
		if ($a->x > $this->minX && $a->x < $this->maxX &&
			$a->y > $this->minY && $a->y < $this->maxY &&
			$a->z > $this->minZ && $a->z < $this->maxZ) {
			
			$result = $a;
			return true;
		}
		
		if ( ($this->getIntersection($a->x - $this->minX, $b->x - $this->minX, $a, $b, $result) && $this->inBox($result, 1))
		  || ($this->getIntersection($a->y - $this->minY, $b->y - $this->minY, $a, $b, $result) && $this->inBox($result, 2)) 
		  || ($this->getIntersection($a->z - $this->minZ, $b->z - $this->minZ, $a, $b, $result) && $this->inBox($result, 3)) 
		  || ($this->getIntersection($a->x - $this->maxX, $b->x - $this->maxX, $a, $b, $result) && $this->inBox($result, 1))
		  || ($this->getIntersection($a->y - $this->maxY, $b->y - $this->maxY, $a, $b, $result) && $this->inBox($result, 2)) 
		  || ($this->getIntersection($a->z - $this->maxZ, $b->z - $this->maxZ, $a, $b, $result) && $this->inBox($result, 3))) {
		  
			return true;
		}

		return false;
	}

	public function calculateIntercept(Vector3 $pos1, Vector3 $pos2){
		$v1 = $pos1->getIntermediateWithXValue($pos2, $this->minX);
		$v2 = $pos1->getIntermediateWithXValue($pos2, $this->maxX);
		$v3 = $pos1->getIntermediateWithYValue($pos2, $this->minY);
		$v4 = $pos1->getIntermediateWithYValue($pos2, $this->maxY);
		$v5 = $pos1->getIntermediateWithZValue($pos2, $this->minZ);
		$v6 = $pos1->getIntermediateWithZValue($pos2, $this->maxZ);

		$f = -1;
		$vector = null;
		if ($v1 !== null && $this->isVectorInYZ($v1)) {
			$vector = $v1;
			$f = 4;
		}

		if ($v2 !== null && $this->isVectorInYZ($v2) && ($vector === null || $pos1->distanceSquared($v2) < $pos1->distanceSquared($vector))) {
			$vector = $v2;
			$f = 5;
		}

		if ($v3 !== null && $this->isVectorInXZ($v3) && ($vector === null || $pos1->distanceSquared($v3) < $pos1->distanceSquared($vector))) {
			$vector = $v3;
			$f = 0;
		}

		if ($v4 !== null && $this->isVectorInXZ($v4) && ($vector === null || $pos1->distanceSquared($v4) < $pos1->distanceSquared($vector))) {
			$vector = $v4;
			$f = 1;
		}

		if ($v5 !== null && $this->isVectorInXY($v5) && ($vector === null || $pos1->distanceSquared($v5) < $pos1->distanceSquared($vector))) {
			$vector = $v5;
			$f = 2;
		}

		if ($v6 !== null && $this->isVectorInXY($v6) && ($vector === null || $pos1->distanceSquared($v6) < $pos1->distanceSquared($vector))) {
			$vector = $v6;
			$f = 3;
		}

		if ($vector === null) {
			return null;
		}
		return MovingObjectPosition::fromBlock(0, 0, 0, $f, $vector);
	}

	public function __toString(){
		return "AxisAlignedBB({$this->minX}, {$this->minY}, {$this->minZ}, {$this->maxX}, {$this->maxY}, {$this->maxZ})";
	}
}