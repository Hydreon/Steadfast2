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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ItemDespawnEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\Player;
use pocketmine\nbt\NBT;
use pocketmine\level\Level;
use pocketmine\level\format\FullChunk;
use pocketmine\block\Block;

class Item extends Entity{
	const NETWORK_ID = 64;

	protected $owner = null;
	protected $thrower = null;
	protected $pickupDelay = 0;
	/** @var ItemItem */
	protected $item;

	public $width = 0.25;
	public $length = 0.25;
	public $height = 0.25;
	protected $gravity = 0.04;
	protected $drag = 0.02;

	public $canCollide = false;
	
	protected static $transparentBlocks = [
		Block::AIR, Block::WATER, Block::STILL_WATER, Block::LAVA, Block::STILL_LAVA,
		Block::BROWN_MUSHROOM, Block::CARPET, Block::COBWEB, Block::DANDELION,
		Block::FIRE, Block::RED_FLOWER, Block::FLOWER_POT_BLOCK, Block::RED_MUSHROOM,
		Block::SAPLING, Block::SNOW_LAYER, Block::TALL_GRASS, Block::TORCH, Block::DOUBLE_PLANT,
		Block::NETHER_WART_BLOCK, Block::WHEAT_BLOCK
	];
	
	public function __construct(FullChunk $chunk, Compound $nbt) {
		parent::__construct($chunk, $nbt);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_NO_AI, true, self::DATA_TYPE_LONG, false);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_AFFECTED_BY_GRAVITY, true, self::DATA_TYPE_LONG, false); // fix for 1.2.14.3
	}

	protected function initEntity(){
		parent::initEntity();

		$this->setMaxHealth(5);
		$this->setHealth($this->namedtag["Health"]);
		if(isset($this->namedtag->Age)){
			$this->age = $this->namedtag["Age"];
		}
		if(isset($this->namedtag->PickupDelay)){
			$this->pickupDelay = $this->namedtag["PickupDelay"];
		}
		if(isset($this->namedtag->Owner)){
			$this->owner = $this->namedtag["Owner"];
		}
		if(isset($this->namedtag->Thrower)){
			$this->thrower = $this->namedtag["Thrower"];
		}
		if (isset($this->namedtag->Item)) {
			$this->item = NBT::getItemHelper($this->namedtag->Item);
			$this->server->getPluginManager()->callEvent(new ItemSpawnEvent($this));
		} else {
			$this->close();
		}	
	}


	public function attack($damage, EntityDamageEvent $source){
		if(
			$source->getCause() === EntityDamageEvent::CAUSE_VOID or
			$source->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK or
			$source->getCause() === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION or
			$source->getCause() === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION
		){
			parent::attack($damage, $source);
		}
	}

	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}
		if ($this->dead) {
			$this->despawnFromAll();
			$this->close();
			return false;
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		if ($tickDiff < 1) {
			$tickDiff = 1;
		}
		$this->lastUpdate = $currentTick;
		$this->age += $tickDiff;
		if ($this->pickupDelay > 0 && $this->pickupDelay < 32767) { //Infinite delay
			$this->pickupDelay -= $tickDiff;
		}
		$this->checkBlockCollision();
		if ($this->y < 1) {
			$this->kill();
			return true;
		} elseif ($this->age > 1200) {
			$this->server->getPluginManager()->callEvent($ev = new ItemDespawnEvent($this));
			if ($ev->isCancelled()) {
				$this->age = 0;
			} else {
				$this->kill();
				return true;
			}
		}
		if (!$this->onGround || $this->motionX != 0 || $this->motionY != 0 || $this->motionZ != 0) {
			if ($this->onGround && $this->motionY <= 0) {
				$this->motionY = 0;
			} else {
				$this->motionY -= $this->gravity;
				$this->motionY *= 0.96;
			}
			if (abs($this->motionX) < 0.001) {
				$this->motionX = 0;
			}
			if (abs($this->motionZ) < 0.001) {
				$this->motionZ = 0;
			}
			if ($this->motionX != 0 || $this->motionZ != 0) {
				$friction = 1 - $this->drag;
				if ($this->onGround) {
					$friction *= Block::getFrictionFactor();
				}
				$this->motionX *= $friction;
				$this->motionZ *= $friction;
			}
		
			$this->move($this->motionX, $this->motionY, $this->motionZ);
			$this->updateMovement();
		}		
		return true;
	}

	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->Item = new Compound("Item", [
			"id" => new ShortTag("id", $this->item->getId()),
			"Damage" => new ShortTag("Damage", $this->item->getDamage()),
			"Count" => new ByteTag("Count", $this->item->getCount())
		]);
		$this->namedtag->Health = new ShortTag("Health", $this->getHealth());
		$this->namedtag->Age = new ShortTag("Age", $this->age);
		$this->namedtag->PickupDelay = new ShortTag("PickupDelay", $this->pickupDelay);
		if($this->owner !== null){
			$this->namedtag->Owner = new StringTag("Owner", $this->owner);
		}
		if($this->thrower !== null){
			$this->namedtag->Thrower = new StringTag("Thrower", $this->thrower);
		}
	}

	/**
	 * @return ItemItem
	 */
	public function getItem(){
		return $this->item;
	}

	public function canCollideWith(Entity $entity){
		return false;
	}

	/**
	 * @return int
	 */
	public function getPickupDelay(){
		return $this->pickupDelay;
	}

	/**
	 * @param int $delay
	 */
	public function setPickupDelay($delay){
		$this->pickupDelay = $delay;
	}

	/**
	 * @return string
	 */
	public function getOwner(){
		return $this->owner;
	}

	/**
	 * @param string $owner
	 */
	public function setOwner($owner){
		$this->owner = $owner;
	}

	/**
	 * @return string
	 */
	public function getThrower(){
		return $this->thrower;
	}

	/**
	 * @param string $thrower
	 */
	public function setThrower($thrower){
		$this->thrower = $thrower;
	}

	public function spawnTo(Player $player){
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$pk = new AddItemEntityPacket();
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->item = $this->getItem();
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);
			$this->hasSpawned[$player->getId()] = $player;
		}
	}

	
	protected function updateMovement() {
		$diffPosition = ($this->x - $this->lastX) ** 2 + ($this->y - $this->lastY) ** 2 + ($this->z - $this->lastZ) ** 2;
		if ($diffPosition > 0.04) {
			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;
			$this->level->addEntityMovement($this->getViewers(), $this->id, $this->x, $this->y + $this->getEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw);
		}
	}

	public function move($dx, $dy, $dz) {
		$this->boundingBox->offset($dx, $dy, $dz);
		$this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
		$this->checkChunks();
		$this->onGround = !$this->isTransparentBlock(floor($this->x), floor($this->y - 0.5), floor($this->z));
		if (!$this->isTransparentBlock(floor($this->x + $this->motionX), floor($this->y), floor($this->z))) {
			$this->motionX = 0;
		}
		if (!$this->isTransparentBlock(floor($this->x), floor($this->y), floor($this->z + $this->motionZ))) {
			$this->motionZ = 0;
		}
		return true;
	}

	protected function isTransparentBlock($x, $y, $z) {
		return in_array($this->level->getBlockIdAt($x, $y, $z), static::$transparentBlocks);
	}

}
