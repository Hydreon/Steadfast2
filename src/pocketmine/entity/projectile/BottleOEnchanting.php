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

use pocketmine\entity\Human;
use pocketmine\level\format\FullChunk;

use pocketmine\level\particle\RainSplashParticle;
use pocketmine\level\sound\GenericSound;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\SpawnExperienceOrbPacket;
use pocketmine\Player;
use pocketmine\entity\Projectile;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\Server;


class BottleOEnchanting extends Projectile{
	const NETWORK_ID = 68;

	public $width = 0.25;
	public $length = 0.25;
	public $height = 0.25;

	protected $gravity = 0.03;
	protected $drag = 0.01;

	protected $canExplode = false;
	protected $givenOutXp = false;

	public function __construct(FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null)
	{
		parent::__construct($chunk, $nbt, $shootingEntity);

	}


	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		//$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);


		// If bottle has collided with the ground or with an entity
		if($this->age > 1200 || $this->isCollided || $this->hadCollision){
			// Add Splash particles
			for($i = 0; $i <= 14; $i++) {
				$this->level->addParticle(new RainSplashParticle($this->add(
					$this->width / 2 + mt_rand(-100, 100) / 500,
					$this->height / 2 + mt_rand(-100, 100) / 500,
					$this->width / 2 + mt_rand(-100, 100) / 500)));
			}

			$playerToUpdate = $this->shootingEntity;

			foreach ($this->shootingEntity->getViewers() as $player) {
				if ($player instanceof Player) {
					$player->sendSound("SOUND_GLASS", ['x' => $this->getX(), 'y' => $this->getY(), 'z' => $this->getZ()]);

					// For now if the bottle breaks on another player then the other player gets the xp, if it breaks anywhere else then the thrower gets the xp
					$xRange = range($this->getFloorX() - 1,$this->getFloorX() + 1 );
					$zRange = range($this->getFloorZ() - 1,$this->getFloorZ() + 1 );
					$yRange = range($this->getFloorY() - 3,$this->getFloorY() + 3 );

					if(in_array($player->getFloorX(), $xRange) && in_array($player->getFloorZ(), $zRange) && in_array($player->getFloorY(), $yRange)) {
						$playerToUpdate = $player;
					}
					//TODO: Spawn XB orbs, but for now just give the player xp
//					$pk = new SpawnExperienceOrbPacket();
//					$pk->x = $this->x;
//					$pk->y = $this->y;
//					$pk->z = $this->z;
//					$player->dataPacket($pk);
				}
			}

			// Spawn XB orbs, but for now just give the player xp
			if($playerToUpdate instanceof Player) {
				//$this->shootingEntity->sendSound("SOUND_GLASS", ['x' => $this->shootingEntity->getX(), 'y' => $this->shootingEntity->getY(), 'z' => $this->shootingEntity->getZ()]);
				//TODO: 6-33 xp per bottle break:  2 - 3 orbs containing 3 - 11 xp
				if(!$this->givenOutXp) {
					$playerToUpdate->addExperience(rand(6, 22));
					$this->givenOutXp = true;
					Server::getInstance()->getDefaultLevel()->addSound(new GenericSound($this->getPosition(), 1051), [$playerToUpdate]);
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