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

namespace pocketmine\entity;

use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item as ItemItem;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\utils\UUID;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\Player;
use pocketmine\level\Level;

use pocketmine\network\multiversion\Multiversion;

class Human extends Creature implements ProjectileSource, InventoryHolder{

	protected $nameTag = "TESTIFICATE";
	/** @var PlayerInventory */
	protected $inventory;


	/** @var UUID */
	protected $uuid;
	protected $rawUUID;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
	public $eyeHeight = 1.62;

	protected $skin;
	protected $skinName = 'Standard_Custom';
	protected $skinGeometryName = "geometry.humanoid.custom";
	protected $skinGeometryData = "";
	protected $capeData = "";

	public function getSkinData(){
		return $this->skin;
	}

	public function getSkinName(){
		return $this->skinName;
	}
	
	public function getSkinGeometryName(){
		return $this->skinGeometryName;
	}
	
	public function getSkinGeometryData(){
		return $this->skinGeometryData;
	}
	
	public function getCapeData(){
		return $this->capeData;
	}

	/**
	 * @return UUID|null
	 */
	public function getUniqueId(){
		return $this->uuid;
	}

	/**
	 * @return string
	 */
	public function getRawUniqueId(){
		return $this->rawUUID;
	}

	/**
	 * @param string $str
	 * @param bool   $skinName
	 */
	public function setSkin($str, $skinName, $skinGeometryName = "", $skinGeometryData = "", $capeData = ""){
		$this->skin = $str;
		if (is_string($skinName)) {
			$this->skinName = $skinName;
		}
		if (!empty($skinGeometryName)) {
			$this->skinGeometryName = $skinGeometryName;
		}
		if (!empty($skinGeometryData)) {
			$this->skinGeometryData = $skinGeometryData;
		}
		if (!empty($capeData)) {
			$this->capeData = $capeData;
		}
	}

	public function getInventory(){
		return $this->inventory;
	}

	protected function initEntity(){

		$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, false);
		$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [0, 0, 0]);
		
		if ($this instanceof Player){
			$this->inventory = Multiversion::getPlayerInventory($this);
		} else {
			$this->inventory = new PlayerInventory($this);
		}

		if(!($this instanceof Player)){
			if(isset($this->namedtag->NameTag)){
				$this->setNameTag($this->namedtag["NameTag"]);
			}

			if(isset($this->namedtag->Skin) and $this->namedtag->Skin instanceof Compound){
				$this->setSkin($this->namedtag->Skin["Data"], $this->namedtag->Skin["Slim"] > 0);
			}

			$this->uuid = UUID::fromData($this->getId(), $this->getSkinData(), $this->getNameTag());
		}

		if(isset($this->namedtag->Inventory) and $this->namedtag->Inventory instanceof Enum){
			foreach($this->namedtag->Inventory as $item){
				if($item["Slot"] >= 0 and $item["Slot"] < 9){ //Hotbar
					$this->inventory->setHotbarSlotIndex($item["Slot"], isset($item["TrueSlot"]) ? $item["TrueSlot"] : -1);
				}elseif($item["Slot"] >= 100 and $item["Slot"] < 104){ //Armor
					$this->inventory->setItem($this->inventory->getSize() + $item["Slot"] - 100, NBT::getItemHelper($item));
				}else{
					$this->inventory->setItem($item["Slot"] - 9, NBT::getItemHelper($item));
				}
			}
		}

		parent::initEntity();
	}

	public function getName(){
		return $this->getNameTag();
	}

	public function getDrops(){
		$drops = [];
		if($this->inventory !== null){
			foreach($this->inventory->getContents() as $item){
				$drops[] = $item;
			}
		}

		return $drops;
	}

	public function saveNBT() {
		parent::saveNBT();
		$this->namedtag->Inventory = new Enum("Inventory", []);
		$this->namedtag->Inventory->setTagType(NBT::TAG_Compound);
		if ($this->inventory !== null) {
			for ($slot = 0; $slot < 9; ++$slot) {
				$hotbarSlot = $this->inventory->getHotbarSlotIndex($slot);
				if ($hotbarSlot !== -1) {
					$item = $this->inventory->getItem($hotbarSlot);
					if ($item->getId() !== ItemItem::AIR && $item->getCount() > 0) {
						$this->namedtag->Inventory[$slot] = NBT::putItemHelper($item, $slot);
						$this->namedtag->Inventory[$slot]->TrueSlot = new ByteTag("TrueSlot", $hotbarSlot);
						continue;
					}
				}
				$this->namedtag->Inventory[$slot] = NBT::putItemHelper(ItemItem::get(ItemItem::AIR), $slot);
				$this->namedtag->Inventory[$slot]->TrueSlot = new ByteTag("TrueSlot", -1);
			}

			//Normal inventory
			$slotCount = Player::SURVIVAL_SLOTS + 9;
			for($slot = 9; $slot < $slotCount; ++$slot){
				$item = $this->inventory->getItem($slot - 9);
				$this->namedtag->Inventory[$slot] = NBT::putItemHelper($item, $slot);
			}

			//Armor
			for($slot = 100; $slot < 104; ++$slot){
				$item = $this->inventory->getItem($this->inventory->getSize() + $slot - 100);
				if($item instanceof ItemItem and $item->getId() !== ItemItem::AIR){
					$this->namedtag->Inventory[$slot] = NBT::putItemHelper($item, $slot);
				}
			}
		}
	}

	public function spawnTo(Player $player){
		if($player !== $this and !isset($this->hasSpawned[$player->getId()])  and isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])){
			$this->hasSpawned[$player->getId()] = $player;

			if(!($this instanceof Player)) {
				$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getName(), $this->skinName, $this->skin, $this->skinGeometryName, $this->skinGeometryData, $this->capeData, "", [$player]);
			}

			$pk = new AddPlayerPacket();
			$pk->uuid = $this->getUniqueId();
			$pk->username = $this->getName();
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->item = $this->getInventory()->getItemInHand();
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);

			$this->inventory->sendArmorContents($player);
			$this->level->addPlayerHandItem($this, $player);

			if(!($this instanceof Player)) {
				$this->server->removePlayerListData($this->getUniqueId(), [$player]);
			}
		}
	}

	public function despawnFrom(Player $player){
		if(isset($this->hasSpawned[$player->getId()])){
			$pk = new RemoveEntityPacket();
			$pk->eid = $this->getId();
			$player->dataPacket($pk);
			unset($this->hasSpawned[$player->getId()]);
		}
	}

	public function close(){
		if(!$this->closed){
			if(!($this instanceof Player) or $this->loggedIn){
				foreach($this->inventory->getViewers() as $viewer){
					$viewer->removeWindow($this->inventory);
				}
			}
			parent::close();
		}
	}
	
	public function isNeedSaveOnChunkUnload() {
		return false;
	}

}
