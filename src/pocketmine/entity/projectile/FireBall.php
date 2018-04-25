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

namespace pocketmine\entity\projectile;

use pocketmine\level\format\FullChunk;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\entity\Projectile;
use pocketmine\entity\Entity;
use pocketmine\level\Explosion;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Level;

class FireBall extends Projectile{
	const NETWORK_ID = 85;

	public $width = 0.5;
	public $height = 0.5;

	protected $damage = 4;

	protected $drag = 0.01;
	protected $gravity = 0.05;

	protected $isCritical;
	protected $canExplode = false;

	public function __construct(FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null, bool $critical = false){
		parent::__construct($chunk, $nbt, $shootingEntity);

		$this->isCritical = $critical;
	}

	public function isExplode(){
		return $this->canExplode;
	}

	public function setExplode(bool $bool){
		$this->canExplode = $bool;
	}

	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		//$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);

		if(!$this->hadCollision and $this->isCritical){
			$this->level->addParticle(new CriticalParticle($this->add(
				$this->width / 2 + mt_rand(-100, 100) / 500,
				$this->height / 2 + mt_rand(-100, 100) / 500,
				$this->width / 2 + mt_rand(-100, 100) / 500)));
		}elseif($this->onGround){
			$this->isCritical = false;
		}

		if($this->age > 1200 or $this->isCollided){
			if($this->isCollided and $this->canExplode){
				$this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 2.8));
				if(!$ev->isCancelled()){
					$explosion = new Explosion($this, $ev->getForce(), $this->shootingEntity);
					if($ev->isBlockBreaking()){
						$explosion->explodeA();
					}
					$explosion->explodeB();
				}
			}
			$this->kill();
			$hasUpdate = true;
		}

		//$this->timings->stopTiming();
		return $hasUpdate;
	}

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddEntityPacket();
			$pk->type = self::NETWORK_ID;
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
//			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);
		}
	}

}