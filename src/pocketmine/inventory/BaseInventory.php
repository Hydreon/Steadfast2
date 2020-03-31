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

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\network\protocol\v120\InventorySlotPacket;
use pocketmine\network\protocol\v120\InventoryContentPacket;

abstract class BaseInventory implements Inventory{

	/** @var InventoryType */
	protected $type;
	/** @var int */
	protected $maxStackSize = Inventory::MAX_STACK;
	/** @var int */
	protected $size;
	/** @var string */
	protected $name;
	/** @var string */
	protected $title;
	/** @var Item[] */
	protected $slots = [];
	/** @var Player[] */
	protected $viewers = [];
	/** @var InventoryHolder */
	protected $holder;
	
	protected $air;

	/**
	 * @param InventoryHolder $holder
	 * @param InventoryType   $type
	 * @param Item[]          $items
	 * @param int             $overrideSize
	 * @param string          $overrideTitle
	 */
	public function __construct(InventoryHolder $holder, InventoryType $type, array $items = [], $overrideSize = null, $overrideTitle = null){
		$this->holder = $holder;

		$this->type = $type;		
		if($overrideSize !== null){
			$this->size = (int) $overrideSize;
		}else{
			$this->size = $this->type->getDefaultSize();
		}

		if($overrideTitle !== null){
			$this->title = $overrideTitle;
		}else{
			$this->title = $this->type->getDefaultTitle();
		}

		$this->name = $this->type->getDefaultTitle();

		$this->setContents($items);
		$this->air =  Item::get(Item::AIR, null, 0);
	}

	public function __destruct(){
		$this->holder = null;
		$this->slots = [];
	}

	public function getSize(){
		return $this->size;
	}

	public function setSize($size){
		$this->size = (int) $size;
	}

	public function getMaxStackSize(){
		return $this->maxStackSize;
	}

	public function getName(){
		return $this->name;
	}

	public function getTitle(){
		return $this->title;
	}

	public function getItem($index){
		return isset($this->slots[$index]) ? clone $this->slots[$index] : clone $this->air;
	}

	public function getContents(){
		return $this->slots;
	}

	/**
	 * @param Item[] $items
	 */
	public function setContents(array $items){
		if(count($items) > $this->size){
			$items = array_slice($items, 0, $this->size, true);
		}
		
		for($i = 0; $i < $this->size; ++$i){
			if(!isset($items[$i])){
				if(isset($this->slots[$i])){
					$this->clear($i);
				}
			}else{
				if (!$this->setItem($i, $items[$i])){
					$this->clear($i);
				}
			}
		}
	}

	public function setItem($index, Item $item){
		$item = clone $item;
		if($index < 0 or $index >= $this->size){
			return false;
		}elseif($item->getId() === 0 or $item->getCount() <= 0){
			return $this->clear($index);
		}

		$holder = $this->getHolder();
		if($holder instanceof Entity){
			Server::getInstance()->getPluginManager()->callEvent($ev = new EntityInventoryChangeEvent($holder, $this->getItem($index), $item, $index));
			if($ev->isCancelled()){
				$this->sendSlot($index, $this->getViewers());
				return false;
			}
			$item = $ev->getNewItem();
		}

		$old = $this->getItem($index);
		$this->slots[$index] = clone $item;
		$this->onSlotChange($index, $old);

		return true;
	}

	public function contains(Item $item){
		$count = max(1, $item->getCount());
		$checkDamage = $item->getDamage() === null ? false : true;
		$checkTags = $item->getId() != Item::ARROW && $item->hasCompound();
		foreach($this->getContents() as $i){
			if($item->equals($i, $checkDamage, $checkTags)){
				$count -= $i->getCount();
				if($count <= 0){
					return true;
				}
			}
		}

		return false;
	}

	public function all(Item $item){
		$slots = [];
		$checkDamage = $item->getDamage() === null ? false : true;
		$checkTags = $item->hasCompound();
		foreach($this->getContents() as $index => $i){
			if($item->equals($i, $checkDamage, $checkTags)){
				$slots[$index] = $i;
			}
		}

		return $slots;
	}

	public function remove(Item $item){
		$checkDamage = $item->getDamage() === null ? false : true;
		$checkTags = $item->hasCompound();

		foreach($this->getContents() as $index => $i){
			if($item->equals($i, $checkDamage, $checkTags)){
				$this->clear($index);
			}
		}
	}

	public function first(Item $item){
		$count = max(1, $item->getCount());
		$checkDamage = $item->getDamage() === null ? false : true;
		$checkTags = $item->hasCompound();

		foreach($this->getContents() as $index => $i){
			if($item->equals($i, $checkDamage, $checkTags) and $i->getCount() >= $count){
				return $index;
			}
		}

		return -1;
	}

	public function firstEmpty(){
		for($i = 0; $i < $this->size; ++$i){
			if($this->getItem($i)->getId() === Item::AIR){
				return $i;
			}
		}

		return -1;
	}

	public function canAddItem(Item $item){
		$item = clone $item;
		$checkDamage = $item->getDamage() === null ? false : true;
		$checkTags = $item->hasCompound();
		for($i = 0; $i < $this->getSize(); ++$i){
			$slot = $this->getItem($i);
			if($item->deepEquals($slot, $checkDamage, $checkTags)){
				if(($diff = $slot->getMaxStackSize() - $slot->getCount()) > 0){
					$item->setCount($item->getCount() - $diff);
				}
			}elseif($slot->getId() === Item::AIR){
				$item->setCount($item->getCount() - $this->getMaxStackSize());
			}

			if($item->getCount() <= 0){
				return true;
			}
		}

		return false;
	}

	public function addItem(...$slots){
		/** @var Item[] $itemSlots */
		/** @var Item[] $slots */
		$itemSlots = [];
		foreach($slots as $slot){
			if(!($slot instanceof Item)){
				throw new \InvalidArgumentException("Expected Item[], got ".gettype($slot));
			}
			if($slot->getId() !== 0 and $slot->getCount() > 0){
				$itemSlots[] = clone $slot;
			}
		}

		$emptySlots = [];

		$invSize = $this->getSize();
		for ($i = 0; $i < $invSize; ++$i) {
			$item = $this->getItem($i);
			if($item->getId() === Item::AIR || $item->getCount() <= 0){
				$emptySlots[] = $i;
			}

			$itemCount = $item->getCount();
			foreach($itemSlots as $index => $slot){
				if($slot->deepEquals($item) && $itemCount < $item->getMaxStackSize()){
					$slotCount = $slot->getCount();
					$amount = min($item->getMaxStackSize() - $itemCount, $slotCount, $this->getMaxStackSize());
					if($amount > 0){
						$slot->setCount($slotCount - $amount);
						$item->setCount($itemCount + $amount);
						$this->setItem($i, $item);
						if($slot->getCount() <= 0){
							unset($itemSlots[$index]);
						}
					}
				}
			}

			if(count($itemSlots) === 0){
				break;
			}
		}

		if(count($itemSlots) > 0 and count($emptySlots) > 0){
			foreach($emptySlots as $slotIndex){
				//This loop only gets the first item, then goes to the next empty slot
				foreach($itemSlots as $index => $slot){
					$amount = min($slot->getMaxStackSize(), $slot->getCount(), $this->getMaxStackSize());
					$slot->setCount($slot->getCount() - $amount);
					$item = clone $slot;
					$item->setCount($amount);
					$this->setItem($slotIndex, $item);
					if($slot->getCount() <= 0){
						unset($itemSlots[$index]);
					}
					break;
				}
			}
		}

		return $itemSlots;
	}

	public function removeItem(...$slots){
		/** @var Item[] $itemSlots */
		/** @var Item[] $slots */
		$itemSlots = [];
		foreach($slots as $slot){
			if(!($slot instanceof Item)){
				throw new \InvalidArgumentException("Expected Item[], got ".gettype($slot));
			}
			if($slot->getId() !== 0 and $slot->getCount() > 0){
				$itemSlots[] = clone $slot;
			}
		}

		for($i = 0; $i < $this->getSize(); ++$i){
			$item = $this->getItem($i);
			if($item->getId() === Item::AIR or $item->getCount() <= 0){
				continue;
			}

			$checkDamage = $slot->getDamage() === null ? false : true;
			$checkCompound = $slot->getId() != Item::ARROW && $slot->hasCompound();
			foreach($itemSlots as $index => $slot){
				if($slot->equals($item, $checkDamage, $checkCompound)){
					$amount = min($item->getCount(), $slot->getCount());
					$slot->setCount($slot->getCount() - $amount);
					$item->setCount($item->getCount() - $amount);
					$this->setItem($i, $item);
					if($slot->getCount() <= 0){
						unset($itemSlots[$index]);
					}
				}
			}

			if(count($itemSlots) === 0){
				break;
			}
		}

		return $itemSlots;
	}

	public function clear($index){
		if(isset($this->slots[$index])){
			$item = Item::get(Item::AIR, null, 0);
			$old = $this->slots[$index];
			$holder = $this->getHolder();
			if($holder instanceof Entity){
				Server::getInstance()->getPluginManager()->callEvent($ev = new EntityInventoryChangeEvent($holder, $old, $item, $index));
				if($ev->isCancelled()){
					$this->sendSlot($index, $this->getViewers());
					return false;
				}
				$item = $ev->getNewItem();
			}
			if($item->getId() !== Item::AIR){
				$this->slots[$index] = clone $item;
			}else{
				unset($this->slots[$index]);
			}

			$this->onSlotChange($index, $old);
		}

		return true;
	}

	public function clearAll(){
		foreach($this->getContents() as $index => $i){
			$this->clear($index);
		}
	}

	/**
	 * @return Player[]
	 */
	public function getViewers(){
		return $this->viewers;
	}

	public function getHolder(){
		return $this->holder;
	}

	public function setMaxStackSize($size){
		$this->maxStackSize = (int) $size;
	}

	public function open(Player $who){
		$who->getServer()->getPluginManager()->callEvent($ev = new InventoryOpenEvent($this, $who));
		if($ev->isCancelled()){
			return false;
		}
		$this->onOpen($who);

		return true;
	}

	public function close(Player $who){
		$this->onClose($who);
	}

	public function onOpen(Player $who){
		$this->viewers[spl_object_hash($who)] = $who;
	}

	public function onClose(Player $who){
		unset($this->viewers[spl_object_hash($who)]);
	}

	public function onSlotChange($index, $before, $sendPacket = true){
        if ($sendPacket) {
            $this->sendSlot($index, $this->getViewers());
        }
	}


	/**
	 * @param Player|Player[] $target
	 */
	public function sendContents($target){
		if($target instanceof Player){
			$target = [$target];
		}

		$slots = [];
		for($i = 0; $i < $this->getSize(); ++$i){
			$slots[$i] = $this->getItem($i);
		}

		foreach($target as $player){
			if(($id = $player->getWindowId($this)) === -1 or $player->spawned !== true){
				$this->close($player);
				continue;
			}
			$pk = new InventoryContentPacket();
			$pk->inventoryID = $id;
			$pk->items = $slots;
			$player->dataPacket($pk);
		}
	}

	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendSlot($index, $target){
		if($target instanceof Player){
			$target = [$target];
		}

		$item = clone $this->getItem($index);
		foreach($target as $player){
			if(($id = $player->getWindowId($this)) === -1){
				$this->close($player);
				continue;
			}
			$pk = new InventorySlotPacket();
			$pk->containerId = $id;
			$pk->item = $item;
			$pk->slot = $index;
			$player->dataPacket($pk);
		}
	}

	public function getType(){
		return $this->type;
	}

	public function isEmpty() {
		if (!empty($this->slots)) {
			$size = $this->getSize();
			for ($i = 0; $i < $size; $i++) {
				if (isset($this->slots[$i]) && $this->slots[$i] instanceof Item && $this->slots[$i]->getId() !== Item::AIR) {
					return false;
				}
			}
		}
		return true;
	}

}
