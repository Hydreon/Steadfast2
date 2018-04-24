<?php

namespace pocketmine\entity\monster\walking;

use pocketmine\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\item\GoldSword;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\entity\Creature;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\Player;
use pocketmine\level\Level;

class PigZombie extends WalkingMonster{
	const NETWORK_ID = 36;

	private $angry = 0;

	public $width = 0.72;
	public $height = 1.8;
	public $eyeHeight = 1.62;

	public function getSpeed(){
		return 1.15;
	}

	public function initEntity(){
		parent::initEntity();

		if(isset($this->namedtag->Angry)){
			$this->angry = (int) $this->namedtag["Angry"];
		}

		$this->fireProof = true;
		$this->setDamage([0, 5, 9, 13]);
	}

	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->Angry = new IntTag("Angry", $this->angry);
	}

	public function getName(){
		return "PigZombie";
	}

	public function isAngry(){
		return $this->angry > 0;
	}

	public function setAngry(int $val){
		$this->angry = $val;
	}

	public function targetOption(Creature $creature, float $distance){
		return $this->isAngry() && parent::targetOption($creature, $distance);
	}

	public function attack($damage, EntityDamageEvent $source){
		parent::attack($damage, $source);

		if(!$source->isCancelled()){
			$this->setAngry(1000);
		}
	}
	
	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$pk = new AddEntityPacket();
			$pk->eid = $this->getID();
			$pk->type = static::NETWORK_ID;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = 0;
			$pk->speedY = 0;
			$pk->speedZ = 0;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);

			$this->hasSpawned[$player->getId()] = $player;

			$pk = new MobEquipmentPacket();
			$pk->eid = $this->getId();
			$pk->item = new GoldSword();
			$pk->slot = 0;
			$pk->selectedSlot = 0;
			$player->dataPacket($pk);
		}
	}

	public function attackEntity(Entity $player){
		if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.44){
			$this->attackDelay = 0;

			$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
			$player->attack($ev->getFinalDamage(), $ev);
		}
	}

	public function getDrops(){
		$drops = [];
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
			switch(mt_rand(0, 2)){
				case 0:
					$drops[] = Item::get(Item::FLINT, 0, 1);
					break;
				case 1:
					$drops[] = Item::get(Item::GUNPOWDER, 0, 1);
					break;
				case 2:
					$drops[] = Item::get(Item::REDSTONE_DUST, 0, 1);
					break;
			}
		}
		return $drops;
	}

}
