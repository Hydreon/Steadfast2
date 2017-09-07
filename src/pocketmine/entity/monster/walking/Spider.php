<?php

namespace pocketmine\entity\monster\walking;

use pocketmine\entity\monster\WalkingMonster;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Spider extends WalkingMonster{
	const NETWORK_ID = 35;

	public $width = 1.3;
	public $height = 1.12;

	public function getSpeed(){
		return 1.13;
	}

	public function initEntity(){
		parent::initEntity();

		$this->setMaxHealth(16);
		$this->setDamage([0, 2, 2, 3]);
	}

	public function getName(){
		return "Spider";
	}

	public function onUpdate($currentTick){
		if($this->server->getDifficulty() < 1){
			$this->close();
			return false;
		}

		if(!$this->isAlive()){
			if(++$this->deadTicks >= 23){
				$this->close();
				return false;
			}
			return true;
		}

		$tickDiff = $currentTick - $this->lastUpdate;
		$this->lastUpdate = $currentTick;
		$this->entityBaseTick($tickDiff);

		if(!$this->isMovement()){
			return null;
		}

		if($this->isKnockback()){
			$this->move($this->motionX * $tickDiff, $this->motionY, $this->motionZ * $tickDiff);
			$this->motionY -= 0.15 * $tickDiff;
			$this->updateMovement();
			return null;
		}

		$before = $this->baseTarget;
		$this->checkTarget();
		if($this->baseTarget instanceof Creature or $before !== $this->baseTarget){
			$x = $this->baseTarget->x - $this->x;
			$y = $this->baseTarget->y - $this->y;
			$z = $this->baseTarget->z - $this->z;

			$distance = $this->distance($target = $this->baseTarget);
			if($distance <= 2){
				if($target instanceof Creature){
					if($distance <= $this->width / 2 + 0.05){
						if($this->attackDelay < 10){
							$diff = abs($x) + abs($z);
							$this->motionX = $this->getSpeed() * 0.1 * ($x / $diff);
							$this->motionZ = $this->getSpeed() * 0.1 * ($z / $diff);
						}else{
							$this->motionX = 0;
							$this->motionZ = 0;
							$this->attackEntity($target);
						}
					}else{
						$diff = abs($x) + abs($z);
						if(!$this->isFriendly()){
							$this->motionY = 0.2;
						}
						$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
						$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
					}
				}else if($target != null && (pow($this->x - $target->x, 2) + pow($this->z - $target->z, 2)) <= 1){
					$this->moveTime = 0;
				}
			}else{
				$diff = abs($x) + abs($z);
				$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
				$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
			}
			$this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
			$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
		}

		$isJump = false;
		$dx = $this->motionX * $tickDiff;
		$dy = $this->motionY * $tickDiff;
		$dz = $this->motionZ * $tickDiff;
		$this->move($dx, $dy, $dz);
		$be = new Vector2($this->x + $dx, $this->z + $dz);
		$af = new Vector2($this->x, $this->z);
	

		if($be->x != $af->x || $be->y != $af->y){
			$x = 0;
			$z = 0;
			if($be->x - $af->x != 0){
				$x += $be->x - $af->x > 0 ? 1 : -1;
			}
			if($be->y - $af->y != 0){
				$z += $be->y - $af->y > 0 ? 1 : -1;
			}

			$vec = new Vector3(Math::floorFloat($be->x), (int) $this->y, Math::floorFloat($be->y));
			$block = $this->level->getBlock($vec->add($x, 0, $z));
			$block2 = $this->level->getBlock($vec->add($x, 1, $z));
			if(!$block->canPassThrough()){
				$bb = $block2->getBoundingBox();
				if(
					$this->motionY > -$this->gravity * 4
					&& ($block2->canPassThrough() || ($bb == null || ($bb != null && $bb->maxY - $this->y <= 1)))
				){
					$isJump = true;
					if($this->motionY >= 0.3){
						$this->motionY += $this->gravity;
					}else{
						$this->motionY = 0.3;
					}
				}else{
					$isJump = true;
					$this->motionY = 0.15;
				}
			}

			if(!$isJump){
				$this->moveTime -= 90 * $tickDiff;
			}
		}

		if($this->onGround && !$isJump){
			$this->motionY = 0;
		}elseif(!$isJump){
			if($this->motionY > -$this->gravity * 4){
				$this->motionY = -$this->gravity * 4;
			}else{
				$this->motionY -= $this->gravity;
			}
		}
		$this->updateMovement();
		return true;
	}

	public function updateMove(){
		return null;
	}

	public function attackEntity(Entity $player){
		if($this->attackDelay > 10 && (($this->isFriendly() && !($player instanceof Player)) || !$this->isFriendly())){
			$this->attackDelay = 0;

			$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
			$player->attack($ev->getFinalDamage(), $ev);
		}
	}

	public function getDrops(){
		return $this->lastDamageCause instanceof EntityDamageByEntityEvent ? [Item::get(Item::STRING, 0, mt_rand(0, 3))] : [];
	}

}
