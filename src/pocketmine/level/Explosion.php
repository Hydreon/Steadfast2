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

namespace pocketmine\level;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\Network;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\Server;
use pocketmine\utils\Random;
use pocketmine\block\Air;
use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\network\protocol\LevelSoundEventPacket;

class Explosion{

	private $rays = 16; //Rays
	public $level;
	public $source;
	public $size;
	/**
	 * @var Block[]
	 */
	public $affectedBlocks = [];
	public $stepLen = 0.3;
	/** @var Entity|Block */
	protected $what;

	public function __construct(Position $center, $size, $what = null){
		$this->level = $center->getLevel();
		$this->source = $center;
		$this->size = max($size, 0);
		$this->what = $what;
	}

	/**
	 * @deprecated
	 * @return bool
	 */
	public function explode(){
		if($this->explodeA()){
			return $this->explodeB();
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function explodeA(){
		if($this->size < 0.1){
			return false;
		}

		$vector = new Vector3(0, 0, 0);
		$vBlock = new Vector3(0, 0, 0);

		$mRays = intval($this->rays - 1);
		for($i = 0; $i < $this->rays; ++$i){
			for($j = 0; $j < $this->rays; ++$j){
				for($k = 0; $k < $this->rays; ++$k){
					if($i === 0 or $i === $mRays or $j === 0 or $j === $mRays or $k === 0 or $k === $mRays){
						$vector->setComponents($i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1);
						$vector->setComponents(($vector->x / ($len = $vector->length())) * $this->stepLen, ($vector->y / $len) * $this->stepLen, ($vector->z / $len) * $this->stepLen);
						$pointerX = $this->source->x;
						$pointerY = $this->source->y;
						$pointerZ = $this->source->z;

						for($blastForce = $this->size * (mt_rand(700, 1300) / 1000); $blastForce > 0; $blastForce -= $this->stepLen * 0.75){
							$x = (int) $pointerX;
							$y = (int) $pointerY;
							$z = (int) $pointerZ;
							$vBlock->x = $pointerX >= $x ? $x : $x - 1;
							$vBlock->y = $pointerY >= $y ? $y : $y - 1;
							$vBlock->z = $pointerZ >= $z ? $z : $z - 1;
							if($vBlock->y < 0 or $vBlock->y >= $this->level->getMaxY()){
								break;
							}
							$block = $this->level->getBlock($vBlock);

							if($block->getId() !== 0 && $block->getId() !== Block::BEDROCK){
								$blastForce -= ($block->getHardness() / 5 + 0.3) * $this->stepLen;
								if($blastForce > 0){
									if(!isset($this->affectedBlocks[$index = Level::blockHash($block->x, $block->y, $block->z)])){
										$this->affectedBlocks[$index] = $block;
									}
								}
							}
							$pointerX += $vector->x;
							$pointerY += $vector->y;
							$pointerZ += $vector->z;
						}
					}
				}
			}
		}

		return true;
	}

	public function explodeB(){
		$send = [];
		$source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
		$yield = (1 / $this->size) * 100;
		$explosionSize = $this->size * 2;
		$minX = Math::floorFloat($this->source->x - $explosionSize - 1);
		$maxX = Math::floorFloat($this->source->x + $explosionSize + 1);
		$minY = Math::floorFloat($this->source->y - $explosionSize - 1);
		$maxY = Math::floorFloat($this->source->y + $explosionSize + 1);
		$minZ = Math::floorFloat($this->source->z - $explosionSize - 1);
		$maxZ = Math::floorFloat($this->source->z + $explosionSize + 1);

		$explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

		if($this->what instanceof Entity){
			$this->level->getServer()->getPluginManager()->callEvent($ev = new EntityExplodeEvent($this->what, $this->source, $this->affectedBlocks, $yield));
			if($ev->isCancelled()){
				return false;
			}else{
				$yield = $ev->getYield();
				$this->affectedBlocks = $ev->getBlockList();
			}
		}

		$list = $this->level->getNearbyEntities($explosionBB, $this->what instanceof Entity ? $this->what : null);
		foreach($list as $entity){
			$this->tryToDamageEntity($entity);
		}


		$air = Item::get(Item::AIR);

		foreach($this->affectedBlocks as $block){
			if($block->getId() === Block::TNT){
				$mot = (new Random())->nextSignedFloat() * M_PI * 2;
				$tnt = Entity::createEntity("PrimedTNT", $this->level->getChunk($block->x >> 4, $block->z >> 4), new Compound("", [
					"Pos" => new Enum("Pos", [
						new DoubleTag("", $block->x + 0.5),
						new DoubleTag("", $block->y),
						new DoubleTag("", $block->z + 0.5)
					]),
					"Motion" => new Enum("Motion", [
						new DoubleTag("", -sin($mot) * 0.02),
						new DoubleTag("", 0.2),
						new DoubleTag("", -cos($mot) * 0.02)
					]),
					"Rotation" => new Enum("Rotation", [
						new FloatTag("", 0),
						new FloatTag("", 0)
					]),
					"Fuse" => new ByteTag("Fuse", mt_rand(10, 30))
				]));
				if($this->what instanceof Entity){
					$tnt->setOwner($this->what);
				}
				$tnt->spawnToAll();
			}elseif(mt_rand(0, 100) < $yield){
				foreach($block->getDrops($air) as $drop){
					$this->level->dropItem($block->add(0.5, 0.5, 0.5), Item::get(...$drop));
				}
			}
			$this->level->setBlock(new Vector3($block->x, $block->y, $block->z), new Air());
			$send[] = new Vector3($block->x - $source->x, $block->y - $source->y, $block->z - $source->z);
		}
		$pk = new ExplodePacket();
		$pk->x = $this->source->x;
		$pk->y = $this->source->y;
		$pk->z = $this->source->z;
		$pk->radius = $this->size;
		$pk->records = $send;
		Server::broadcastPacket($this->level->getUsingChunk($source->x >> 4, $source->z >> 4), $pk);		
		$this->level->addParticle(new HugeExplodeParticle(new Vector3($this->source->x,  $this->source->y, $this->source->z)));	
		$pk1 = new LevelSoundEventPacket();
		$pk1->eventId = 45;
		$pk1->x = $this->source->x;
		$pk1->y = $this->source->y;
		$pk1->z = $this->source->z;
		$pk1->blockId = -1;
		$pk1->entityType = 1;
		Server::broadcastPacket($this->level->getUsingChunk($source->x >> 4, $source->z >> 4), $pk1);
		
		return true;
	}

	protected function tryToDamageEntity($entity) {
		$explosionSize = $this->size * 2;
		$distance = $entity->distance($this->source) / $explosionSize;
		if ($distance <= 1) {
			$motion = $entity->subtract($this->source)->normalize();
			$impact = 1 - $distance;
			$damage = (int) ((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1);

			if ($this->what instanceof Entity) {
				$ev = new EntityDamageByEntityEvent($this->what, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage);
			} else if ($this->what instanceof Block) {
				$ev = new EntityDamageByBlockEvent($this->what, $entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
			} else {
				$ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
			}

			$entity->attack($ev->getFinalDamage(), $ev);
			$entity->setMotion($motion->multiply($impact));
		}
	}
}
