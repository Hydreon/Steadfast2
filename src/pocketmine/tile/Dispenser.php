<?php

namespace pocketmine\tile;

use pocketmine\inventory\DispenserInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\tile\Tile;

class Dispenser extends Spawnable implements InventoryHolder, Container, Nameable {

	/** @var DispenserInventory */
	protected $inventory = null;

	public function __construct(FullChunk $chunk, Compound $nbt) {
		parent::__construct($chunk, $nbt);
		$this->inventory = new DispenserInventory($this);
		if (!isset($this->namedtag->Items) || !($this->namedtag->Items instanceof Enum)) {
			$this->namedtag->Items = new Enum("Items", []);
			$this->namedtag->Items->setTagType(NBT::TAG_Compound);
		}

		for ($i = 0; $i < $this->getSize(); ++$i) {
			$this->inventory->setItem($i, $this->getItem($i));
		}
	}

	public function close() {
		if ($this->closed === false) {
			foreach ($this->inventory->getViewers() as $player) {
				$player->removeWindow($this->inventory);
			}
			parent::close();
		}
	}

	public function saveNBT() {
		parent::saveNBT();
		$this->namedtag->Items = new Enum("Items", []);
		$this->namedtag->Items->setTagType(NBT::TAG_Compound);
		$inventorySize = $this->getSize();
		for ($i = 0; $i < $inventorySize; $i++) {
			$this->setItem($i, $this->inventory->getItem($i));
		}
	}

	/**
	 * 
	 * @return DispenserInventory
	 */
	public function getInventory() {
		return $this->inventory;
	}

	protected function getSlotIndex($index) {
		foreach ($this->namedtag->Items as $i => $slot) {
			if ((int) $slot["Slot"] === (int) $index) {
				return (int) $i;
			}
		}

		return -1;
	}

	public function getItem($index) {
		$i = $this->getSlotIndex($index);
		if ($i < 0) {
			return Item::get(Item::AIR, 0, 0);
		} else {
			return NBT::getItemHelper($this->namedtag->Items[$i]);
		}
	}

	public function setItem($index, Item $item) {
		$i = $this->getSlotIndex($index);
		if ($item->getId() === Item::AIR || $item->getCount() <= 0) {
			if ($i >= 0) {
				unset($this->namedtag->Items[$i]);
			}
		} else {
			if ($i < 0) {
				$inventorySize = $this->getSize();
				for ($i = 0; $i < $inventorySize; $i++) {
					if (!isset($this->namedtag->Items[$i])) {
						$this->namedtag->Items[$i] = NBT::putItemHelper($item, $index);
						return true;
					}
				}
				return false;
			} else {
				$this->namedtag->Items[$i] = NBT::putItemHelper($item, $index);
			}
		}
		return true;
	}

	public function getSize() {
		return $this->inventory->getSize();
	}

	public function getSpawnCompound() {
		$compound = new Compound("", [
			new StringTag("id", Tile::DISPENSER),
			new IntTag("x", (int) $this->x),
			new IntTag("y", (int) $this->y),
			new IntTag("z", (int) $this->z)
		]);
		if ($this->hasName()) {
			$compound->CustomName = $this->namedtag->CustomName;
		}

		return $compound;
	}

	public function hasName() {
		return isset($this->namedtag->CustomName);
	}

	public function setName($str) {
		if ($str === "") {
			unset($this->namedtag->CustomName);
			return;
		}
		$this->namedtag->CustomName = new StringTag("CustomName", $str);
	}

	public function getName() {
		return $this->hasName() ? $this->namedtag->CustomName->getValue() : parent::getName();
	}

}
