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

use pocketmine\entity\Human;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\Item;
use pocketmine\network\Network;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\network\protocol\Info;

class PlayerInventory extends BaseInventory{
	
	const OFFHAND_ARMOR_SLOT_ID = 4;

	protected $itemInHandIndex = 0;
	/** @var int[] */
	protected $hotbar;

	public function __construct(Human $player){
		for ($i = 0; $i < $this->getHotbarSize(); $i++) {
			$this->hotbar[$i] = $i;
		}
//		$this->hotbar = array_fill(0, $this->getHotbarSize(), -1);
		parent::__construct($player, InventoryType::get(InventoryType::PLAYER));
	}

	public function getSize(){
		return parent::getSize() - 5; //Remove armor slots
	}

	public function setSize($size){
		parent::setSize($size + 5);
	}
	
	/**
	 * 
	 * @param int $index
	 * @return Item
	 */
	public function getHotbatSlotItem($index) {
		$slot = $this->getHotbarSlotIndex($index);
		return $this->getItem($slot);
	}

	public function getHotbarSlotIndex($index){
		return ($index >= 0 and $index < $this->getHotbarSize()) ? $this->hotbar[$index] : -1;
	}

	public function setHotbarSlotIndex($index, $slot){
		if ($this->holder instanceof Player && $this->holder->getInventoryType() == Player::INVENTORY_CLASSIC) {
			if ($index == $slot || $slot < 0) {
				return;
			}
			$tmp = $this->getItem($index);
			$this->setItem($index, $this->getItem($slot));
			$this->setItem($slot, $tmp);
		} else {
			if($index >= 0 and $index < $this->getHotbarSize() and $slot >= -1 and $slot < $this->getSize()){
				$this->hotbar[$index] = $slot;
			}
		}
	}

	public function getHeldItemIndex(){
		return $this->itemInHandIndex;
	}
	
	/**
	 * @impportant For win10 inventory only
	 * @param int $index
	 */
	public function justSetHeldItemIndex($index) {
		if($index >= 0 and $index < $this->getHotbarSize()){
			$this->itemInHandIndex = $index;
		}
	}

	public function setHeldItemIndex($index, $isNeedSendToHolder = true){
		if($index >= 0 and $index < $this->getHotbarSize()){
			$this->itemInHandIndex = $index;
			
			if ($isNeedSendToHolder === true && $this->getHolder() instanceof Player) {
				$this->sendHeldItem($this->getHolder());
			}
			$this->sendHeldItem($this->getHolder()->getViewers());
		}
	}

	public function getItemInHand(){
		$item = $this->getItem($this->getHeldItemSlot());
		if($item instanceof Item){
			return $item;
		}else{
			return clone $this->air;
		}
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setItemInHand(Item $item){
		return $this->setItem($this->getHeldItemSlot(), $item);
	}

	public function getHeldItemSlot(){
		return $this->getHotbarSlotIndex($this->itemInHandIndex);
	}

	public function setHeldItemSlot($slot){
		if($slot >= -1 and $slot < $this->getSize()){
			$item = $this->getItem($slot);

			$itemIndex = $this->getHeldItemIndex();

			if($this->getHolder() instanceof Player){
				Server::getInstance()->getPluginManager()->callEvent($ev = new PlayerItemHeldEvent($this->getHolder(), $item, $slot, $itemIndex));
				if($ev->isCancelled()){
					$this->sendContents($this->getHolder());
					return;
				}
			}

			$this->setHotbarSlotIndex($itemIndex, $slot);
		}
	}

	/**
	 * @param Player|Player[] $target
	 */
	public function sendHeldItem($target){
		$item = $this->getItemInHand();

		$pk = new MobEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->item = $item;
		$pk->slot = $this->getHeldItemSlot();
		$pk->selectedSlot = $this->getHeldItemIndex();

		$level = $this->getHolder()->getLevel();
		if(!is_array($target)){
			if($level->mayAddPlayerHandItem($this->getHolder(), $target)) {
				$target->dataPacket($pk);
				if($target === $this->getHolder()){
					$this->sendSlot($this->getHeldItemSlot(), $target);
				}
			}
		}else{
			foreach($target as $player){
				if($level->mayAddPlayerHandItem($this->getHolder(), $player)) {
					$player->dataPacket($pk);
					if($player === $this->getHolder()){
						$this->sendSlot($this->getHeldItemSlot(), $player);
					}
				}
			}
		}
	}

	public function onSlotChange($index, $before, $sendPacket = true){
		$holder = $this->getHolder();
		if ($holder instanceof Player and !$holder->spawned) {
			return;
		}

		parent::onSlotChange($index, $before, $sendPacket);

		if ($index >= $this->getSize() && $sendPacket === true) {
			$this->sendArmorSlot($index, $this->getHolder()->getViewers());
		}
	}

	public function getHotbarSize(){
		return 9;
	}

	public function getArmorItem($index){
		return $this->getItem($this->getSize() + $index);
	}

	public function setArmorItem($index, Item $item, $sendPacket = true){
		return $this->setItem($this->getSize() + $index, $item, $sendPacket);
	}

	public function getHelmet(){
		return $this->getItem($this->getSize());
	}

	public function getChestplate(){
		return $this->getItem($this->getSize() + 1);
	}

	public function getLeggings(){
		return $this->getItem($this->getSize() + 2);
	}

	public function getBoots(){
		return $this->getItem($this->getSize() + 3);
	}

	public function setHelmet(Item $helmet){
		return $this->setItem($this->getSize(), $helmet);
	}

	public function setChestplate(Item $chestplate){
		return $this->setItem($this->getSize() + 1, $chestplate);
	}

	public function setLeggings(Item $leggings){
		return $this->setItem($this->getSize() + 2, $leggings);
	}

	public function setBoots(Item $boots){
		return $this->setItem($this->getSize() + 3, $boots);
	}

	public function setItem($index, Item $item, $sendPacket = true){
		if($index < 0 or $index >= $this->size){
			return false;
		}elseif($item->getId() === 0 or $item->getCount() <= 0){
			return $this->clear($index);
		}

		if($index >= $this->getSize()){ //Armor change
			Server::getInstance()->getPluginManager()->callEvent($ev = new EntityArmorChangeEvent($this->getHolder(), $this->getItem($index), $item, $index));
			if($ev->isCancelled() and $this->getHolder() instanceof Human){
				$this->sendArmorSlot($index, $this->getHolder());
				return false;
			}
			$item = $ev->getNewItem();
		}else{
			Server::getInstance()->getPluginManager()->callEvent($ev = new EntityInventoryChangeEvent($this->getHolder(), $this->getItem($index), $item, $index));
			if($ev->isCancelled()){
				$this->sendSlot($index, $this->getHolder());
				return false;
			}
			$index = $ev->getSlot();
			$item = $ev->getNewItem();
		}


		$old = $this->getItem($index);
		$this->slots[$index] = clone $item;
		$this->onSlotChange($index, $old, $sendPacket);

		return true;
	}

	public function clear($index){
		if(isset($this->slots[$index])){
			$item = clone $this->air;
			$old = $this->slots[$index];
			if($index >= $this->getSize() and $index < $this->size){ //Armor change
				Server::getInstance()->getPluginManager()->callEvent($ev = new EntityArmorChangeEvent($this->getHolder(), $old, $item, $index));
				if($ev->isCancelled()){
					if($index >= $this->size){
						$this->sendArmorSlot($index, $this->getHolder());
					}else{
						$this->sendSlot($index, $this->getHolder());
					}
					return false;
				}
				$item = $ev->getNewItem();
			}else{
				Server::getInstance()->getPluginManager()->callEvent($ev = new EntityInventoryChangeEvent($this->getHolder(), $old, $item, $index));
				if($ev->isCancelled()){
					if($index >= $this->size){
						$this->sendArmorSlot($index, $this->getHolder());
					}else{
						$this->sendSlot($index, $this->getHolder());
					}
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

	/**
	 * @return Item[]
	 */
	public function getArmorContents(){
		$armor = [];

		for($i = 0; $i < 4; ++$i){
			$armor[$i] = $this->getItem($this->getSize() + $i);
		}

		return $armor;
	}

	public function clearAll(){
		$limit = $this->getSize() + 5;
		for($index = 0; $index < $limit; ++$index){
			$this->clear($index);
		}
	}

	/**
	 * @param Player|Player[] $target
	 */
	public function sendArmorContents($target){
		if($target instanceof Player){
			$target = [$target];
		}

		$armor = $this->getArmorContents();

		$pk = new MobArmorEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->slots = $armor;

		foreach($target as $player){
			if($player === $this->getHolder()){
				$pk2 = new ContainerSetContentPacket();
				$pk2->eid = $this->getHolder()->getId();
				$pk2->windowid = ContainerSetContentPacket::SPECIAL_ARMOR;
				$pk2->slots = $armor;
				$player->dataPacket($pk2);				
			}else{
				$player->dataPacket($pk);
			}
		}
		$this->sendOffHandContents($target);
	}
	
	private function sendOffHandContents($target) {
		$pk = new MobEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->item = $this->getItem($this->getSize() + self::OFFHAND_ARMOR_SLOT_ID);
		$pk->slot = $this->getHeldItemSlot();
		$pk->selectedSlot = $this->getHeldItemIndex();
		$pk->windowId = MobEquipmentPacket::WINDOW_ID_PLAYER_OFFHAND;
		foreach ($target as $player) {
			if ($player->getPlayerProtocol() >= Info::PROTOCOL_110) {
				if ($player === $this->getHolder()) {
					$pk2 = new ContainerSetSlotPacket();
					$pk2->windowid = ContainerSetContentPacket::SPECIAL_OFFHAND;
					$pk2->slot = 0;
					$pk2->item = $this->getItem($this->getSize() + self::OFFHAND_ARMOR_SLOT_ID);
					$player->dataPacket($pk2);
				} else {
					$player->dataPacket($pk);
				}
			}
		}
	}

	/**
	 * @param Item[] $items
	 */
	public function setArmorContents(array $items, $sendPacket = true){
		for($i = 0; $i < 4; ++$i){
			if(!isset($items[$i]) or !($items[$i] instanceof Item)){
				$items[$i] = clone $this->air;
			}

			if($items[$i]->getId() === Item::AIR){
				$this->clear($this->getSize() + $i);
			}else{
				$this->setItem($this->getSize() + $i, $items[$i], $sendPacket);
			}
		}
	}


	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendArmorSlot($index, $target){
		if (!is_array($target)) {
			if($target instanceof Player){
				$target = [$target];
			} else {
				return;
			}
		}
		
		if ($index - $this->getSize() == self::OFFHAND_ARMOR_SLOT_ID) {
			$this->sendOffHandContents($target);
			return;
		}
		
		$armor = $this->getArmorContents();

		$pk = new MobArmorEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->slots = $armor;

		foreach($target as $player){
			if($player === $this->getHolder()){
				$pk2 = new ContainerSetSlotPacket();
				$pk2->windowid = ContainerSetContentPacket::SPECIAL_ARMOR;
				$pk2->slot = $index - $this->getSize();
				$pk2->item = $this->getItem($index);
				$player->dataPacket($pk2);
			}else{
				$player->dataPacket($pk);
			}
		}
	}
	
	/**
	 * @param Player|Player[] $target
	 */
	public function sendContents($target) {
		if (!($this->getHolder() instanceof Player)) {
			return;
		}
		$pk = new ContainerSetContentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->windowid = ContainerSetContentPacket::SPECIAL_INVENTORY;
		$pk->slots = [];
		for ($i = 0; $i < $this->getSize(); ++$i) { //Do not send armor by error here
			$pk->slots[$i] = $this->getItem($i);
		}
		for ($i = $this->getSize(); $i < $this->getSize() + 9; ++$i) {
			$pk->slots[$i] = clone $this->air;
		}
		$pk->hotbar = [];
		for ($i = 0; $i < $this->getHotbarSize(); ++$i) {
			$index = $this->getHotbarSlotIndex($i);
			$pk->hotbar[] = $index <= -1 ? -1 : $index + 9;
		}
		$this->getHolder()->dataPacket($pk);
	}

	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendSlot($index, $target){
		if (!($this->getHolder() instanceof Player)) {
			return;
		}
		$pk = new ContainerSetSlotPacket();
		$pk->slot = $index;
		$pk->item = clone $this->getItem($index);
		$pk->windowid = ContainerSetContentPacket::SPECIAL_INVENTORY;
		$this->getHolder()->dataPacket($pk);
	}

	/**
	 * @return Human|Player
	 */
	public function getHolder(){
		return parent::getHolder();
	}
	
	public function removeItemWithCheckOffHand($searchItem) {
		$offhandSlotId = $this->getSize() + self::OFFHAND_ARMOR_SLOT_ID;
		$item = $this->getItem($offhandSlotId);
		if ($item->getId() !== Item::AIR && $item->getCount() > 0) {
			if ($searchItem->equals($item, $searchItem->getDamage() === null ? false : true, $searchItem->getId() == Item::ARROW || $searchItem->getCompound() === null ? false : true)) {
				$amount = min($item->getCount(), $searchItem->getCount());
				$searchItem->setCount($searchItem->getCount() - $amount);
				$item->setCount($item->getCount() - $amount);
				$this->setItem($offhandSlotId, $item);
				return;
			}
		}
		parent::removeItem($searchItem);
	}
	
	public function openSelfInventory() {
		$pk = new ContainerOpenPacket();
		$pk->windowid = ContainerSetContentPacket::SPECIAL_INVENTORY;
		$pk->type = -1;
		$pk->slots = $this->getSize();
		$pk->x = $this->getHolder()->getX();
		$pk->y = $this->getHolder()->getY();
		$pk->z = $this->getHolder()->getZ();
		$this->getHolder()->dataPacket($pk);
	}

}