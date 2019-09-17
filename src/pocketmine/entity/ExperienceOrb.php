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

use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\multiversion\Entity as EntityIds;


class ExperienceOrb extends Entity {

	const NETWORK_ID = EntityIds::ID_EXP_ORB;
	
	protected $angle;
	protected $initialFinished = false;
	protected $initialY;
	protected $minExp = 3;
	protected $maxExp = 11;

	public function __construct(FullChunk $chunk, Compound $nbt) {
		parent::__construct($chunk, $nbt);
		$this->initialY = $this->y;
		$teta = deg2rad(mt_rand(0, 359));
		$this->motionY = 0.2;
		$this->motionX = 0.1 * sin($teta);
		$this->motionZ = 0.1 * cos($teta);
		$this->dataProperties[self::DATA_FLAGS] = [self::DATA_TYPE_LONG, (1 << self::DATA_FLAG_NO_AI)];
		$this->spawnToAll();
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
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);
		}
	}

	public function isNeedSaveOnChunkUnload() {
		return false;
	}

	public function onUpdate($currentTick) {
		if ($this->closed) {
			return false;
		}
		if ($this->dead || $this->y < 0 || $this->age > 1200) {
			$this->close();
			return false;
		}
		$this->age++;
		if (!$this->initialFinished) {
			if ($this->initialY < $this->motionY + $this->y) {
				$this->move($this->motionX, $this->motionY, $this->motionZ);
				$this->motionY -= 0.04;
			} else {
				$this->initialFinished = true;
				$this->y = $this->initialY;
			}
		} else {
			$players = $this->getViewers();
			$nearestPlayer = null;
			$minDistanceSquare = PHP_INT_MAX;
			foreach ($players as $player) {
				if(!$player->isSpectator() && $player->isAlive()){
					$s = $this->distanceSquared($player);
					if ($s < $minDistanceSquare) {
						$nearestPlayer = $player;
						$minDistanceSquare = $s;
					}
				}
			}
			if (!is_null($nearestPlayer)) {
				if ($minDistanceSquare < 1.44) {
					$nearestPlayer->addExperience(mt_rand($this->minExp, $this->maxExp));
					$this->close();
				} elseif ($minDistanceSquare < 64) {
					$distanse = sqrt($minDistanceSquare);
					$speed = max(0.1, 0.5 / $distanse);
					$dx = $nearestPlayer->x - $this->x;
					$dz = $nearestPlayer->z - $this->z;
					$teta = atan2($dx, $dz);
					$motionX = $speed * sin($teta);
					$motionZ = $speed * cos($teta);
					$motionY = $nearestPlayer->y - $this->y + 0.4;
					$this->move($motionX, $motionY, $motionZ);
				}
			}
		}
		return true;
	}

	public function move($dx, $dy, $dz) {
		$this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
		$this->boundingBox->offset($dx, $dy, $dz);
		$this->checkChunks();
		$this->level->addEntityMovement($this->getViewers(), $this->id, $this->x, $this->y, $this->z, 0, 0);
	}
	
	public function setExpInterval($minExp, $maxExp) {
		$this->minExp = $minExp;
		$this->maxExp = $maxExp;
	}

}
