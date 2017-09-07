<?php

namespace pocketmine\entity\monster\walking;

use pocketmine\entity\monster\WalkingMonster;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;

class Creeper extends WalkingMonster implements Explosive{
	const NETWORK_ID = 33;

	public $width = 0.72;
	public $height = 1.8;

	private $bombTime = 0;

	public function getSpeed(){
		return 0.9;
	}

	public function initEntity(){
		parent::initEntity();

		if(isset($this->namedtag->BombTime)){
			$this->bombTime = (int) $this->namedtag["BombTime"];
		}
	}

	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->BombTime = new IntTag("BombTime", $this->bombTime);
	}

	public function getName(){
		return "Creeper";
	}

	public function explode(){
		$this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 2.8));

		if(!$ev->isCancelled()){
			$explosion = new Explosion($this, $ev->getForce(), $this);
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
			$this->close();
		}
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
			return true;
		}

		if($this->isKnockback()){
			$this->move($this->motionX * $tickDiff, $this->motionY, $this->motionZ * $tickDiff);
			$this->motionY -= 0.15 * $tickDiff;
			$this->updateMovement();
			return true;
		}

		$before = $this->baseTarget;
		$this->checkTarget();

		if($this->baseTarget instanceof Creature || $before != $this->baseTarget){
			$x = $this->baseTarget->x - $this->x;
			$y = $this->baseTarget->y - $this->y;
			$z = $this->baseTarget->z - $this->z;

			$target = $this->baseTarget;
			$distance = sqrt(pow($this->x - $target->x, 2) + pow($this->z - $target->z, 2));
			if($distance <= 4.5){
				if($target instanceof Creature){
					$this->bombTime += $tickDiff;
					if($this->bombTime >= 64){
						$this->explode();
						return false;
					}
				}else if(pow($this->x - $target->x, 2) + pow($this->z - $target->z, 2) <= 1){
					$this->moveTime = 0;
				}
			}else{
				$this->bombTime -= $tickDiff;
				if($this->bombTime < 0){
					$this->bombTime = 0;
				}

				$diff = abs($x) + abs($z);
				$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
				$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
			}
			$this->yaw = rad2deg(-atan2($this->motionX, $this->motionZ));
			$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
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
				$x = $be->x > $af->x ? 1 : -1;
			}
			if($be->y - $af->y != 0){
				$z = $be->y > $af->y ? 1 : -1;
			}

			$vec = new Vector3(Math::floorFloat($be->x), $this->y, Math::floorFloat($be->y));
			$block = $this->level->getBlock($vec->add($x, 0, $z));
			$block2 = $this->level->getBlock($vec->add($x, 1, $z));
			if(!$block->canPassThrough()){
				$bb = $block2->getBoundingBox();
				if(
					$this->motionY > -$this->gravity * 4
					&& ($block2->canPassThrough() || ($bb == null || $bb->maxY - $this->y <= 1))
				){
					$isJump = true;
					if($this->motionY >= 0.3){
						$this->motionY += $this->gravity;
					}else{
						$this->motionY = 0.3;
					}
				}elseif($this->level->getBlock($vec)->getId() == Item::LADDER){
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
		}else if(!$isJump){
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

	}

	public function getDrops(){
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
			switch(mt_rand(0, 2)){
				case 0:
					return [Item::get(Item::FLINT, 0, 1)];
				case 1:
					return [Item::get(Item::GUNPOWDER, 0, 1)];
				case 2:
					return [Item::get(Item::REDSTONE_DUST, 0, 1)];
			}
		}
		return [];
	}

}