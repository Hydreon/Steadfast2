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


use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\math\Vector3;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Level;

class Squid extends WaterAnimal implements Ageable{
	const NETWORK_ID = 17;

	public $width = 0.95;
	public $length = 0.95;
	public $height = 0.95;

	/** @var Vector3 */
	public $swimDirection = null;
	public $swimSpeed = 0.1;

	private $switchDirectionTicker = 0;

	public function initEntity(){
		$this->setMaxHealth(5);
		parent::initEntity();
	}

	public function getName(){
		return "Squid";
	}

	public function attack($damage, EntityDamageEvent $source){
		parent::attack($damage, $source);
		if($source->isCancelled()){
			return;
		}

		if($source instanceof EntityDamageByEntityEvent){
			$this->swimSpeed = mt_rand(150, 350) / 2000;
			$e = $source->getDamager();
			$this->swimDirection = (new Vector3($this->x - $e->x, $this->y - $e->y, $this->z - $e->z))->normalize();

			$pk = new EntityEventPacket();
			$pk->eid = $this->getId();
			$pk->event = EntityEventPacket::SQUID_INK_CLOUD;
			Server::broadcastPacket($this->hasSpawned, $pk);
		}
	}

	private function generateRandomDirection(){
		return new Vector3(mt_rand(-1000, 1000) / 1000, mt_rand(-500, 500) / 1000, mt_rand(-1000, 1000) / 1000);
	}


	public function onUpdate($currentTick){
	}


	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddEntityPacket();
			$pk->eid = $this->getId();
			$pk->type = Squid::NETWORK_ID;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);
		}
	}

	public function getDrops(){
		return [
			ItemItem::get(ItemItem::DYE, 0, mt_rand(1, 3))
		];
	}
}
