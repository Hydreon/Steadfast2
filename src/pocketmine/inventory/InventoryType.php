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

namespace pocketmine\inventory;

/**
 * Saves all the information regarding default inventory sizes and types
 */
class InventoryType{
	const CHEST = 0;
	const DOUBLE_CHEST = 1;
	const PLAYER = 2;
	const FURNACE = 3;
	const CRAFTING = 4;
	const WORKBENCH = 5;
	const STONECUTTER = 6;
	const BREWING_STAND = 7;
	const ANVIL = 8;
	const ENCHANT_TABLE = 9;
	const DISPENSER = 10;
	const DROPPER = 11;
	const HOPPER = 12;
	
	const TYPE_NONE = -9;
	const TYPE_INVENTORY = -1;
	const TYPE_CONTAINER = 0;
	const TYPE_WORKBENCH = 1;
	const TYPE_FURNACE = 2;
	const TYPE_ENCHANTMENT = 3;
	const TYPE_BREWING_STAND = 4;
	const TYPE_ANVIL = 5;
	const TYPE_DISPENSER = 6;
	const TYPE_DROPPER = 7;
	const TYPE_HOPPER = 8;
	const TYPE_CAULDRON = 9;
	const TYPE_MINECART_CHEST = 10;
	const TYPE_MINECART_HOPPER = 11;
	const TYPE_HORSE = 12;
	const TYPE_BEACON = 13;
	const TYPE_STRUCTURE_EDITOR = 14;
	const TYPE_TRADE = 15;
	const TYPE_COMMAND_BLOCK = 16;
	const TYPE_JUKEBOX = 17;
	const TYPE_ARMOR = 18;
	const TYPE_HAND = 19;

	private static $default = [];

	private $size;
	private $title;
	private $typeId;

	/**
	 * @param $index
	 *
	 * @return InventoryType
	 */
	public static function get($index){
		return isset(static::$default[$index]) ? static::$default[$index] : null;
	}

	public static function init(){
		if(count(static::$default) > 0){
			return;
		}
		
		// 5 - ANVIL
		// 4 - BREWING_STAND
		// 3 - ENCHANT_TABLE
		// 2 - FURNACE
		static::$default[static::CHEST] = new InventoryType(27, "Chest", self::TYPE_CONTAINER);
		static::$default[static::DOUBLE_CHEST] = new InventoryType(27 + 27, "Double Chest", self::TYPE_CONTAINER);
		static::$default[static::PLAYER] = new InventoryType(41, "Player", self::TYPE_CONTAINER); //27 CONTAINER, 4 ARMOR (9 reference HOTBAR slots), 1 OFFHAND
		static::$default[static::FURNACE] = new InventoryType(3, "Furnace", self::TYPE_FURNACE);
		static::$default[static::CRAFTING] = new InventoryType(5, "Crafting", self::TYPE_WORKBENCH); //4 CRAFTING slots, 1 RESULT
		static::$default[static::WORKBENCH] = new InventoryType(10, "Crafting", self::TYPE_WORKBENCH); //9 CRAFTING slots, 1 RESULT
		static::$default[static::STONECUTTER] = new InventoryType(10, "Crafting", self::TYPE_WORKBENCH); //9 CRAFTING slots, 1 RESULT
		static::$default[static::ENCHANT_TABLE] = new InventoryType(2, "Enchant", self::TYPE_ENCHANTMENT); //1 INPUT/OUTPUT, 1 LAPIS
 		static::$default[static::BREWING_STAND] = new InventoryType(4, "Brewing", self::TYPE_BREWING_STAND); //1 INPUT, 3 POTION
 		static::$default[static::ANVIL] = new InventoryType(3, "Anvil", self::TYPE_ANVIL); //2 INPUT, 1 OUTPUT
 		static::$default[static::DISPENSER] = new InventoryType(9, "Dispenser", self::TYPE_DISPENSER); //9 INPUT
		static::$default[static::DROPPER] = new InventoryType(9, "Dropper", self::TYPE_DROPPER); //9 INPUT
		static::$default[static::HOPPER] = new InventoryType(5, "Hopper", self::TYPE_HOPPER); //5 INPUT
	}
	
	public static function registerInventoryType($id, $size, $name, $typeId) {
		static::$default[$id] = new InventoryType($size, $name, $typeId);
	}

	/**
	 * @param int    $defaultSize
	 * @param string $defaultTitle
	 * @param int    $typeId
	 */
	private function __construct($defaultSize, $defaultTitle, $typeId = 0){
		$this->size = $defaultSize;
		$this->title = $defaultTitle;
		$this->typeId = $typeId;
	}

	/**
	 * @return int
	 */
	public function getDefaultSize(){
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getDefaultTitle(){
		return $this->title;
	}

	/**
	 * @return int
	 */
	public function getNetworkType(){
		return $this->typeId;
	}
}