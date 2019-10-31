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

use function mt_rand;
use pocketmine\block\Block;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\format\FullChunk;
use pocketmine\level\sound\GenericSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\Server;

abstract class Projectile extends Entity {

	const DATA_SHOOTER_ID = 17;

	/** @var Entity */
	public $shootingEntity = null;
	protected $damage = 0;
	public $hadCollision = false;

	public function __construct(FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null) {
		$this->shootingEntity = $shootingEntity;
		if ($shootingEntity !== null) {
			$this->setDataProperty(self::DATA_SHOOTER_ID, self::DATA_TYPE_LONG, $shootingEntity->getId());
		}
		parent::__construct($chunk, $nbt);
	}

	public function attack($damage, EntityDamageEvent $source) {
		if ($source->getCause() === EntityDamageEvent::CAUSE_VOID) {
			parent::attack($damage, $source);
		}
	}

	protected function initEntity() {
		parent::initEntity();
		$this->setMaxHealth(1);
		$this->setHealth(1);
		if (isset($this->namedtag->Age)) {
			$this->age = $this->namedtag["Age"];
		}
	}

	public function canCollideWith(Entity $entity) {
		return $entity instanceof Living and ! $this->onGround;
	}

	public function saveNBT() {
		parent::saveNBT();
		$this->namedtag->Age = new ShortTag("Age", $this->age);
	}

	public function onUpdate($currentTick) {
		if ($this->closed) {
			return false;
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		if ($tickDiff <= 0 and ! $this->justCreated) {
			return true;
		}
		$this->lastUpdate = $currentTick;
		$hasUpdate = $this->entityBaseTick($tickDiff);
		if ($this->isAlive() && !$this->isCollided) {
		    $this->motionY -= $this->isInsideOfWater() ? $this->gravity * 10 : $this->gravity;
			$moveVector = new Vector3($this->x + $this->motionX, $this->y + $this->motionY, $this->z + $this->motionZ);
			$itersectionPoint = new Vector3();
			// find nearest collided entity
			$list = $this->getLevel()->getCollidingEntities($this->getBoundingBox()->addCoord($this->motionX, $this->motionY, $this->motionZ), $this);
			$nearEntityDistance = PHP_INT_MAX;
			$nearEntity = null;
			foreach ($list as $entity) {
				if (($entity === $this->shootingEntity && $this->ticksLived < 5)) {
					continue;
				}
				$axisalignedbb = $entity->boundingBox;
				if (!$axisalignedbb->getIntersectionWithLine($this, $moveVector, $itersectionPoint)) {
					continue;
				}
				$distance = $this->distanceSquared($itersectionPoint);
				if ($distance < $nearEntityDistance) {
					$nearEntityDistance = $distance;
					$nearEntity = $entity;
				}
			}
			// find nearest collided block
			$blockList = $this->getLevel()->getCollisionBlocks($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ), $this);
			$nearBlockDistance = PHP_INT_MAX;
			$nearBlock = null;
			foreach ($blockList as $block) {
				$axisalignedbb = $block->boundingBox;
				if (is_null($axisalignedbb) || $block->isLiquid()) {
					continue;
				}
				if (!$axisalignedbb->getIntersectionWithLine($this, $moveVector, $itersectionPoint)) {
					continue;
				}
				$distance = $this->distanceSquared($itersectionPoint);
				if ($distance < $nearBlockDistance) {
					$nearBlockDistance = $distance;
					$nearBlock = $block;
				}
			}
			// if entity near than block
			if ($nearEntity !== null && $nearBlockDistance - $nearEntityDistance > 0.01) {
				$this->server->getPluginManager()->callEvent(new ProjectileHitEvent($this));
				$motion = sqrt($this->motionX ** 2 + $this->motionY ** 2 + $this->motionZ ** 2);
				$damage = ceil($motion * $this->damage);
				if ($this instanceof Arrow && $this->isCritical()) {
				    $damage += mt_rand(0, (int) ($damage / 2) + 1);
				}
				if ($this->shootingEntity === null) {
					$ev = new EntityDamageByEntityEvent($this, $nearEntity, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
				} else {
					$ev = new EntityDamageByChildEntityEvent($this->shootingEntity, $this, $nearEntity, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
				}
				$nearEntity->attack($ev->getFinalDamage(), $ev);

				if($this->shootingEntity instanceof Player && !$ev->isCancelled()){
					//$this->shootingEntity->sendSound('successful_hit', ['x' => $this->shootingEntity->getX(), 'y' => $this->shootingEntity->getY(), 'z' => $this->shootingEntity->getZ()]);
					$this->getLevel()->addSound(new GenericSound($this->shootingEntity->getPosition(), 1051));
                }

				$this->hadCollision = true;
				if ($this->fireTicks > 0) {
					$ev = new EntityCombustByEntityEvent($this, $nearEntity, 5);
					$this->server->getPluginManager()->callEvent($ev);
					if (!$ev->isCancelled()) {
						$nearEntity->setOnFire($ev->getDuration());
					}
				}
				$this->kill();
				return true;
			}

			if ($nearBlock !== null && $nearBlockDistance < 0.3) {
				$this->server->getPluginManager()->callEvent(new ProjectileHitEvent($this));

				if(($this->shootingEntity instanceof Player) && ($this instanceof Arrow)){
                    $this->motionX = 0;
                    $this->motionY = 0;
                    $this->motionZ = 0;
                } else {
				    $this->kill();
                }

                $this->isCollided = true;
				$this->hadCollision = true;
				return true;
			}

			// if doesnt hit neither entity nor block
			$this->move($this->motionX, $this->motionY, $this->motionZ);

			if ($this->isCollided && !$this->hadCollision) { //whyyyy there's no difference lol
				$this->hadCollision = true;
                return true;
			}

			if (!$this->isCollided && $this->hadCollision) {
				$this->hadCollision = false;
			}

			if (!$this->onGround or abs($this->motionX) > 0.00001 or abs($this->motionY) > 0.00001 or abs($this->motionZ) > 0.00001) {
				$f = sqrt(($this->motionX ** 2) + ($this->motionZ ** 2));
				$this->yaw = (atan2($this->motionX, $this->motionZ) * 180 / M_PI);
				$this->pitch = (atan2($this->motionY, $f) * 180 / M_PI);
				$hasUpdate = true;
			}
			$this->updateMovement();
		}
		return $hasUpdate;
	}

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddEntityPacket();
			$pk->type = static::NETWORK_ID;
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$player->dataPacket($pk);
		}
	}
}
