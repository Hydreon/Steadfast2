<?php

namespace pocketmine;

use pocketmine\level\Level;
use pocketmine\entity\monster\Monster;
use pocketmine\entity\animal\Animal;
use pocketmine\level\Position;
use pocketmine\entity\BaseEntity;
use pocketmine\block\Liquid;
use pocketmine\math\Vector3;

class SpawnerCreature {

	const MAX_MOB_IN_CHUNK = 1;
	const MAX_ANIMAL_IN_CHUNK = 1;

	public static function generateEntity($server, $useAnimal, $useMonster) {
		if (!$useAnimal && !$useMonster) {
			return;
		}
		$level = $server->getDefaultLevel();
		$chunks = array();
		foreach ($server->getOnlinePlayers() as $entityhuman) {
			if ($entityhuman->isAlive()) {
				$chunkX = floor($entityhuman->getX() >> 4);
				$chunkZ = floor($entityhuman->getZ() >> 4);
				$radius = 4;
				for ($dx = -$radius; $dx <= $radius; $dx++) {
					for ($dz = -$radius; $dz <= $radius; $dz++) {
						if (rand(1, 8) == 3) {
							$chunks[] = Level::chunkHash($chunkX + $dx, $chunkZ + $dz);
						}
					}
				}
			}
		}
		$chunksClone = $chunks;

		$animalInChunk = array();
		$monsterInChunk = array();
		$anamalCount = 0;
		$monsterCount = 0;

		foreach ($level->getEntities() as $entity) {
			if ($entity instanceof Monster) {
				$monsterCount++;
				$hash = Level::chunkHash($entity->getX() >> 4, $entity->getZ() >> 4);
				if (!isset($monsterInChunk[$hash])) {
					$monsterInChunk[$hash] = 1;
				} else {
					$monsterInChunk[$hash] ++;
				}
			}
			if ($entity instanceof Animal) {
				$hash = Level::chunkHash(floor($entity->getX() >> 4), floor($entity->getZ()) >> 4);
				if (!isset($animalInChunk[$hash])) {
					$animalInChunk[$hash] = 1;
				} else {
					$animalInChunk[$hash] ++;
				}
				$anamalCount++;
			}
		}


		if ($useAnimal) {
			while ($anamalCount < $server->getAnimalLimit() && count($chunks) > 0) {
				$key = array_rand($chunks);
				$index = $chunks[$key];
				unset($chunks[$key]);
				if (isset($animalInChunk[$index]) && $animalInChunk[$index] >= self::MAX_ANIMAL_IN_CHUNK) {
					continue;
				}
				$animals = array("Cow", "Pig", "Sheep", "Chicken", "Wolf", "Ocelot", "Mooshroom", "Rabbit", "IronGolem", "SnowGolem");
				$pos = self::getPosition($index, $level);
				if ($pos) {
					BaseEntity::create($animals[array_rand($animals)], $pos);
					$anamalCount++;
				}
			}
		}


		$time = $level->getTime() % 30000;
		$isNight = $time > 16000 && $time < 29000;
		if ($useMonster && $isNight) {
			$chunks = $chunksClone;
			while ($monsterCount < $server->getMonsterLimit() && count($chunks) > 0) {
				$key = array_rand($chunks);
				$index = $chunks[$key];
				unset($chunks[$key]);
				if (isset($monsterInChunk[$index]) && $monsterInChunk[$index] >= self::MAX_MOB_IN_CHUNK) {
					continue;
				}
				$monsters = array("Zombie", "Creeper", "Skeleton", "Spider", "PigZombie", "Enderman", "CaveSpider", "ZombieVillager", "Ghast", "Blaze");
				$pos = self::getPosition($index, $level);
				if ($pos) {
					BaseEntity::create($monsters[array_rand($monsters)], $pos);
					$monsterCount++;
				}
			}
		}
	}

	private static function getPosition($index, $level) {
		$chunkX = null;
		$chunkZ = null;
		Level::getXZ($index, $chunkX, $chunkZ);
		$chunk = $level->getChunk($chunkX, $chunkZ, false);
		if (!is_null($chunk) && $chunk->isPopulated()) {
			$x = ($chunkX << 4) + rand(0, 15);
			$z = ($chunkZ << 4) + rand(0, 15);
			$y = $level->getHighestBlockAt($x, $z) + 2;
			if($level->getBlock(new Vector3($x, $y - 2, $z)) instanceof Liquid){
				return false;
			}
			return new Position($x, $y, $z, $level);
		}
		return false;
	}

}
