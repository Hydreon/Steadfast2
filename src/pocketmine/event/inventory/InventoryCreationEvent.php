<?php

namespace pocketmine\event\inventory;

use pocketmine\event\Event;
use pocketmine\Player;
use pocketmine\inventory\PlayerInventory;

class InventoryCreationEvent extends Event {

	public static $handlerList = null;
	/** @var Player */
	private $player;
	/** @var PlayerInventory::class */
	private $baseClass;
	/** @var PlayerInventory::class */
	private $inventoryClass;

	/**
	 * @param PlayerInventory::class $baseClass
	 * @param PlayerInventory::class $playerClass
	 * @param Player $player
	 */
	public function __construct($baseClass, $inventoryClass, $player) {
		$this->player = $player;
		if (!is_a($baseClass, PlayerInventory::class, true)) {
			throw new \RuntimeException("Base class $baseClass must extend " . PlayerInventory::class);
		}
		$this->baseClass = $baseClass;
		if (!is_a($inventoryClass, PlayerInventory::class, true)) {
			throw new \RuntimeException("Class $inventoryClass must extend " . PlayerInventory::class);
		}
		$this->inventoryClass = $inventoryClass;
	}

	/**
	 * @return PlayerInventory::class
	 */
	public function getBaseClass() {
		return $this->baseClass;
	}

	/**
	 * @param PlayerInventory::class $class
	 */
	public function setBaseClass($class) {
		if (!is_a($class, $this->baseClass, true)) {
			throw new \RuntimeException("Base class $class must extend " . $this->baseClass);
		}
		$this->baseClass = $class;
	}

	/**
	 * @return PlayerInventory::class
	 */
	public function getInventoryClass() {
		return $this->inventoryClass;
	}

	/**
	 * @param PlayerInventory::class $class
	 */
	public function setInventoryClass($class) {
		if (!is_a($class, $this->baseClass, true)) {
			throw new \RuntimeException("Class $class must extend " . $this->baseClass);
		}
		$this->inventoryClass = $class;
	}

}