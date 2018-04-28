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
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\ItemDespawnEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\Player;
use pocketmine\nbt\NBT;
use pocketmine\level\Level;

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
//	protected $drag = 0.02;
	protected $drag = 0.15;

	public $canCollide = false;

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

		$tickDiff = $currentTick - $this->lastUpdate;
		if ($tickDiff < 1) {
			$tickDiff = 1;
		}
		$this->lastUpdate = $currentTick;

		//$this->timings->startTiming();

		$hasUpdate = $this->entityBaseTick($tickDiff);

		if (!$this->dead) {

			if ($this->pickupDelay > 0 && $this->pickupDelay < 32767) { //Infinite delay
				$this->pickupDelay -= $tickDiff;
			}

			$this->motionY -= $this->gravity;

			$this->keepMovement = $this->checkObstruction($this->x, ($this->boundingBox->minY + $this->boundingBox->maxY) / 2, $this->z);
			$this->move($this->motionX, $this->motionY, $this->motionZ);

			$friction = 1 - $this->drag;

			if ($this->onGround && ($this->motionX != 0 || $this->motionZ != 0)) {
				$friction = $this->level->getBlock(new Vector3($this->getFloorX(), $this->getFloorY() - 1, $this->getFloorZ()))->getFrictionFactor() * $friction;
			}

			$this->motionX *= $friction;
			$this->motionY *= 1 - $this->drag;
			$this->motionZ *= $friction;

			$this->updateMovement();
			
			if ($this->y < 1) {
				$this->kill();
				$hasUpdate = true;
			} else {
				if ($this->onGround) {
					$this->motionY *= -0.5;
				}

				if ($this->age > 1200) {
					$this->server->getPluginManager()->callEvent($ev = new ItemDespawnEvent($this));
					if ($ev->isCancelled()) {
						$this->age = 0;
					} else {
						$this->kill();
						$hasUpdate = true;
					}
				}
			}
			$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_AFFECTED_BY_GRAVITY, true); // fix for 1.2.14.3
		}

		//$this->timings->stopTiming();
		
		return $hasUpdate || !$this->onGround || $this->motionX != 0 || $this->motionY != 0 || $this->motionZ != 0;
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
			$player->dataPacket($pk);
	//		$this->sendData($player);
			$this->hasSpawned[$player->getId()] = $player;
		}
	}

	
	protected function updateMovement(){	
		$diffPositionX =  abs($this->x - $this->lastX);
		$diffPositionY =  abs($this->y - $this->lastY);
		$diffPositionZ =  abs($this->z - $this->lastZ);		
		
		$diffMotionX = abs($this->motionX - $this->lastMotionX);
		$diffMotionY = abs($this->motionY - $this->lastMotionY);
		$diffMotionZ = abs($this->motionZ - $this->lastMotionZ);
		

		if($diffPositionX > 0.2 || $diffPositionZ > 0.2 || ($diffPositionX > 0.01 && $diffPositionZ > 0.01 && $diffPositionY > 0.2)){
			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;
			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;
			
			$this->level->addEntityMovement($this->getViewers(), $this->id, $this->x, $this->y + $this->getEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw);
		}

		if($diffMotionX > 0.05 || $diffMotionZ > 0.05 || ($diffMotionX > 0.001 && $diffMotionZ > 0.001 && $diffMotionY > 0.05 )){ 
			$this->lastMotionX = $this->motionX;
			$this->lastMotionY = $this->motionY;
			$this->lastMotionZ = $this->motionZ;
			
			$this->level->addEntityMotion($this->getViewers(), $this->id, $this->motionX, $this->motionY, $this->motionZ);
		}
	}
	
}
