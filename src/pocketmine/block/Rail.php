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

namespace pocketmine\block;

use pocketmine\item\Tool;
use pocketmine\block\Transparent;

class Rail extends Transparent{
	
	const STRAIGHT_EAST_WEST = 0;
	const STRAIGHT_NORTH_SOUTH = 1;
	const SLOPED_ASCENDING_NORTH = 2;
	const SLOPED_ASCENDING_SOUTH = 3;
	const SLOPED_ASCENDING_EAST = 4;
	const SLOPED_ASCENDING_WEST = 5;
	const CURVED_NORTH_WEST = 7;
	const CURVED_SOUTH_WEST = 6;
	const CURVED_SOUTH_EAST = 9;
	const CURVED_NORTH_EAST = 8;

	protected $id = self::RAIL;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Rail";
	}

	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	public function getHardness(){
		return 3;
	}
}