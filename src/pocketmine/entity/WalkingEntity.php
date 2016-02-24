<?php

namespace pocketmine\entity;

use pocketmine\entity\animal\Animal;
use pocketmine\entity\monster\walking\PigZombie;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\entity\Creature;
use pocketmine\block\Air;
use pocketmine\block\Liquid;

abstract class WalkingEntity extends BaseEntity {

	protected function checkTarget($update = false) {
		if(!$update){
			if ($this->isKnockback()) {
				return;
			}

			$target = $this->baseTarget;
			if (!$target instanceof Creature or ! $this->targetOption($target, $this->distanceSquared($target))) {
				$near = PHP_INT_MAX;
				foreach ($this->getLevel()->getEntities() as $creature) {
					if ($creature === $this || !($creature instanceof Creature) || $creature instanceof Animal) {
						continue;
					}

					if (
							$creature instanceof BaseEntity && $creature->isFriendly() == $this->isFriendly()
					) {
						continue;
					}

					$distance = $this->distanceSquared($creature);
					if (
							$distance <= 100 && $this instanceof PigZombie && $this->isAngry() && $creature instanceof PigZombie && !$creature->isAngry()
					) {
						$creature->setAngry(1000);
					}

					if ($distance > $near or ! $this->targetOption($creature, $distance)) {
						continue;
					}
					$near = $distance;

					$this->moveTime = 0;
					$this->baseTarget = $creature;
				}
			}

			if (
					$this->baseTarget instanceof Creature && $this->baseTarget->isAlive()
			) {
				return;
			}
		}

		if ($update || $this->moveTime <= 0 || !($this->baseTarget instanceof Vector3)) {
			$x = mt_rand(20, 100);
			$z = mt_rand(20, 100);
			$this->moveTime = mt_rand(300, 1200);
			$this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
		}
	}

	public function updateMove() {
		if (!$this->isMovement()) {
			return null;
		}

        if($this->isKnockback()){
            $target = null;
        } else{
        
			$before = $this->baseTarget;
			$this->checkTarget();
			if ($this->baseTarget instanceof Creature or $before !== $this->baseTarget) {
				$x = $this->baseTarget->x - $this->x;
				$y = $this->baseTarget->y - $this->y;
				$z = $this->baseTarget->z - $this->z;
				if ($x ** 2 + $z ** 2 < 0.7) {
					$this->motionX = 0;
					$this->motionZ = 0;
				} else {
					$diff = abs($x) + abs($z);
					$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
					$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
				}
				$this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
				$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
			}

			$target = $this->baseTarget;
		}
		$isJump = false;
		$dx = $this->motionX;		
		$dz = $this->motionZ;

		$newX = Math::floorFloat($this->x + $dx);
		$newZ = Math::floorFloat($this->z + $dz);

		$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y), $newZ));
		if (!($block instanceof Air) && !($block instanceof Liquid)) {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y + 1), $newZ));
			if (!($block instanceof Air) && !($block instanceof Liquid)) {
				$this->motionY = 0;
				$this->checkTarget(true);
				return;
			} else {
				if(!$block->canBeFlowedInto){
					$isJump = true;
					$this->motionY =  1;
				} else{
					$this->motionY =  0;
				}
			}
		} else {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y - 1), $newZ));
			if (!($block instanceof Air) && !($block instanceof Liquid)) {
				$blockY = Math::floorFloat($this->y);
				if($this->y - $this->gravity * 4 > $blockY){
					$this->motionY = -$this->gravity * 4;
				} else{
					$this->motionY = ($this->y - $blockY) > 0 ? ($this->y - $blockY) : 0;
				}
			} else {
				$this->motionY = -$this->gravity * 4;
			}

		}
		$this->move($dx, $this->motionY, $dz);
		$this->updateMovement();
		return $target;
	}

}
