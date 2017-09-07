<?php

namespace pocketmine\entity\monster\flying;

use pocketmine\entity\monster\FlyingMonster;
use pocketmine\entity\projectile\FireBall;
use pocketmine\entity\BaseEntity;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\entity\ProjectileSource;
use pocketmine\level\Location;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Ghast extends FlyingMonster implements ProjectileSource{
	const NETWORK_ID = 41;

	public $width = 4;
	public $height = 4;

	public function getSpeed(){
		return 1.2;
	}

	public function initEntity(){
		parent::initEntity();

		$this->fireProof = true;
		$this->setMaxHealth(10);
		$this->setDamage([0, 0, 0, 0]);
	}

	public function getName(){
		return "Ghast";
	}

	public function targetOption(Creature $creature, float $distance){
		return (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->closed && $distance <= 10000;
	}

	public function attackEntity(Entity $player){
		if($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distance($player) <= 100){
			$this->attackDelay = 0;
		
			$f = 2;
			$yaw = $this->yaw + mt_rand(-220, 220) / 10;
			$pitch = $this->pitch + mt_rand(-120, 120) / 10;
			$pos = new Location(
				$this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5),
				$this->getEyeHeight(),
				$this->z +(cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5),
				$yaw,
				$pitch,
				$this->level
			);
			$fireball = BaseEntity::create("FireBall", $pos, $this);
			if(!($fireball instanceof FireBall)){
				return;
			}

			$fireball->setExplode(true);
			$fireball->setMotion(new Vector3(
				-sin(rad2deg($yaw)) * cos(rad2deg($pitch)) * $f * $f,
				-sin(rad2deg($pitch)) * $f * $f,
				cos(rad2deg($yaw)) * cos(rad2deg($pitch)) * $f * $f
			));

			$this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($fireball));
			if($launch->isCancelled()){
				$fireball->kill();
			}else{
				$fireball->spawnToAll();
				$this->level->addSound(new LaunchSound($this), $this->getViewers());
			}
		}
	}

	public function getDrops(){
		return [];
	}

}
