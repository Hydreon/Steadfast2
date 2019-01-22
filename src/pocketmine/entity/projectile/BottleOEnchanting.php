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

use pocketmine\entity\Entity;
use pocketmine\level\particle\SplashParticle;
use pocketmine\level\sound\GenericSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\SpawnExperienceOrbPacket;
use pocketmine\Player;
use pocketmine\entity\Projectile;
use pocketmine\network\multiversion\Entity as EntityIds;
use pocketmine\Server;


class BottleOEnchanting extends Projectile{
	const NETWORK_ID = EntityIds::ID_EXP_BOTTLE;
	protected $gravity = 0.05;
	protected $drag = 0.01;

	protected $givenOutXp = false;

	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::onUpdate($currentTick);


		// If bottle has collided with the ground or with an entity
		if($this->age > 1200 || $this->isCollided || $this->hadCollision){
			// Add Splash particles
			for($i = 0; $i <= 14; $i++) {
				$particle = new SplashParticle($this->add(
					$this->width / 2 + mt_rand(-100, 100) / 500,
					$this->height / 2 + mt_rand(-100, 100) / 500,
					$this->width / 2 + mt_rand(-100, 100) / 500));


				$this->level->addParticle($particle);
			}
			$playerToUpdate = $this->shootingEntity;

			// Until the orbs spawn on the ground dont send glass breaking sound
//			if($this->shootingEntity instanceof Player) {
//				$this->shootingEntity->sendSound("SOUND_GLASS", ['x' => $this->getX(), 'y' => $this->getY(), 'z' => $this->getZ()],EntityIds::ID_NONE, -1 ,$this->getViewers());
//			}

			//TODO: Spawn XB orbs (pocketmine/entity/ExperienceOrb) here, for now just give a player xp
			// 6-33 xp per bottle break. Spawn 2 - 3 orbs containing 3 - 11 xp
			foreach ($this->shootingEntity->getViewers() as $player) {
				if ($player instanceof Player) {

					// For now if the bottle breaks on another player then the other player gets the xp, if it breaks anywhere else then the thrower gets the xp
					$xRange = range($this->getFloorX() - 1,$this->getFloorX() + 1 );
					$zRange = range($this->getFloorZ() - 1,$this->getFloorZ() + 1 );
					$yRange = range($this->getFloorY() - 3,$this->getFloorY() + 3 );

					if(in_array($player->getFloorX(), $xRange) && in_array($player->getFloorZ(), $zRange) && in_array($player->getFloorY(), $yRange)) {
						$playerToUpdate = $player;
					}
				}
			}

			if($playerToUpdate instanceof Player) {
				if(!$this->givenOutXp) {
					$playerToUpdate->addExperience(rand(6, 33));
					$this->givenOutXp = true;
					$players = $playerToUpdate->getViewers();
					array_push($players, $playerToUpdate);
					$this->level->addSound(new GenericSound($this->getPosition(), 1051), $players);
				}

			}
			$this->kill();
			$hasUpdate = true;
		}

		//$this->timings->stopTiming();
		return $hasUpdate;
	}


}