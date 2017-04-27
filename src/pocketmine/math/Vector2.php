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

/**
 * WARNING: This class is available on the PocketMine-MP Zephir project.
 * If this class is modified, remember to modify the PHP C extension.
 */
class Vector2{
	public $x;
	public $y;

	public function __construct($x = 0, $y = 0){
		$this->x = $x;
		$this->y = $y;
	}

	public function getX(){
		return $this->x;
	}

	public function getY(){
		return $this->y;
	}

	public function getFloorX(){
		return (int) $this->x;
	}

	public function getFloorY(){
		return (int) $this->y;
	}

	public function add($x, $y = 0) {
		if ($x instanceof Vector2) {
			return new Vector2($this->x + $x->x, $this->y + $y->y);
		} else {
			return new Vector2($this->x + $x, $this->y + $y);
		}
	}

	public function subtract($x, $y = 0) {
		if ($x instanceof Vector2) {
			return new Vector2($this->x - $x->x, $this->y - $y->y);
		} else {
			return new Vector2($this->x - $x, $this->y - $y);
		}
	}

	public function ceil(){
		return new Vector2((int) ($this->x + 1), (int) ($this->y + 1));
	}

	public function floor(){
		return new Vector2((int) $this->x, (int) $this->y);
	}

	public function round(){
		return new Vector2(round($this->x), round($this->y));
	}

	public function abs(){
		return new Vector2(abs($this->x), abs($this->y));
	}

	public function multiply($number){
		return new Vector2($this->x * $number, $this->y * $number);
	}

	public function divide($number){
		return new Vector2($this->x / $number, $this->y / $number);
	}

	public function distance($x, $y = 0){
		if($x instanceof Vector2){
			return sqrt(($this->x - $x->x) ** 2 + ($this->y - $x->y) ** 2);
		}else{
			return sqrt(($this->x - $x) ** 2 + ($this->y - $y) ** 2);
		}
	}

	public function distanceSquared($x, $y = 0) {
		if ($x instanceof Vector2) {
			return ($this->x - $x->x) ** 2 + ($this->y - $x->y) ** 2;
		} else {
			return ($this->x - $x) ** 2 + ($this->y - $y) ** 2;
		}
	}

	public function length(){
		return sqrt($this->x * $this->x + $this->y * $this->y);
	}

	public function lengthSquared(){
		return $this->x * $this->x + $this->y * $this->y;
	}

	public function normalize(){
		if ($this->x != 0 || $this->y != 0) {
			$len = sqrt($this->x * $this->x + $this->y * $this->y);
			return new Vector2($this->x / $len, $this->y / $len);
		}

		return new Vector2(0, 0);
	}

	public function dot(Vector2 $v){
		return $this->x * $v->x + $this->y * $v->y;
	}

	public function __toString(){
		return "Vector2(x=" . $this->x . ",y=" . $this->y . ")";
	}

}