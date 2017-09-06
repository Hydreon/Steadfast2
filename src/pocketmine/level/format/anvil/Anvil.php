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

namespace pocketmine\level\format\anvil;

use pocketmine\level\format\FullChunk;
use pocketmine\level\format\mcregion\McRegion;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\ByteArray;
use pocketmine\nbt\tag\Compound;
use pocketmine\utils\ChunkException;
use pocketmine\utils\Binary;
use pocketmine\nbt\NBT;
use pocketmine\tile\Spawnable;
use pocketmine\level\format\generic\EmptyChunkSection;

class Anvil extends McRegion {
	
	const REGION_FILE_EXTENSION = "mca";
	
	protected $chunkClass = Chunk::class;
	protected $regionLoaderClass = RegionLoader::class;
	protected static $chunkSectionClass = ChunkSection::class;

	/** @var RegionLoader[] */
	protected $regions = [];

	/** @var Chunk[] */
	protected $chunks = [];

	public static function getProviderName() {
		return "anvil";
	}

	public static function usesChunkSection() {
		return true;
	}
	
	public function requestChunkTask($x, $z, $protocols, $subClientsId) {
		$chunk = $this->getChunk($x, $z, false);
		if(!($chunk instanceof $this->chunkClass)){
			throw new ChunkException("Invalid Chunk sent");
		}
		
		$tiles = "";
		$nbt = new NBT(NBT::LITTLE_ENDIAN);		
		foreach($chunk->getTiles() as $tile){
			if($tile instanceof Spawnable){
				$nbt->setData($tile->getSpawnCompound());
				$tiles .= $nbt->write();
			}
		}
		
		$data = array();
		$data['chunkX'] = $x;
		$data['chunkZ'] = $z;
		$data['protocols'] = $protocols;
		$data['subClientsId'] = $subClientsId;
		$data['tiles'] = $tiles;
		$data['isAnvil'] = true;
		$data['chunk'] = $this->getChunkData($chunk);
		
		$this->getLevel()->chunkMaker->pushMainToThreadPacket(serialize($data));
		return null;
	}
	
	protected function getChunkData($chunk) {
		$data = [
			'sections' => [],
			'heightMap' => pack("v*", ...$chunk->getHeightMapArray()),
			'biomeColor' => $this->convertBiomeColors($chunk->getBiomeColorArray())	
		];
		$sections = [];
		foreach ($chunk->getSections() as $section) {
			if ($section instanceof EmptyChunkSection) {
				continue;
			}
			$chunkData = [];
			$chunkData['empty'] = false;
			$chunkData['blocks'] = $section->getIdArray();
			$chunkData['data'] = $section->getDataArray();
			$chunkData['blockLight'] = $section->getLightArray();
			$chunkData['skyLight'] = $section->getSkyLightArray();
			$sections[$section->getY()] = $chunkData;
		}
		$sortedSections = [];
		for ($y = 0; $y < $this->chunkClass::SECTION_COUNT; ++$y) {
			if (count($sections) == 0) {
				break;
			}
			if (isset($sections[$y])) {
				$sortedSections[$y] = $sections[$y];
				unset($sections[$y]);				
			} else {
				$sortedSections[$y] = ['empty' => true];
			}
		}
		$data['sections'] = $sortedSections;
		return $data;
	}
		
	/**
	 * @param $x
	 * @param $z
	 *
	 * @return RegionLoader
	 */
	protected function getRegion($x, $z) {
		return isset($this->regions[$index = Level::chunkHash($x, $z)]) ? $this->regions[$index] : null;
	}

	/**
	 * @param int  $chunkX
	 * @param int  $chunkZ
	 * @param bool $create
	 *
	 * @return Chunk
	 */
	public function getChunk($chunkX, $chunkZ, $create = false) {
		return parent::getChunk($chunkX, $chunkZ, $create);
	}

	public function setChunk($chunkX, $chunkZ, FullChunk $chunk) {
		if (!($chunk instanceof $this->chunkClass)) {
			throw new ChunkException("Invalid Chunk class");
		}

		$chunk->setProvider($this);

		self::getRegionIndex($chunkX, $chunkZ, $regionX, $regionZ);
		$this->loadRegion($regionX, $regionZ);

		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);
		$this->chunks[Level::chunkHash($chunkX, $chunkZ)] = $chunk;
	}

	public function getEmptyChunk($chunkX, $chunkZ){
		return $this->chunkClass::getEmptyChunk($chunkX, $chunkZ, $this);
	}

	public static function createChunkSection($Y) {
		return new static::$chunkSectionClass(new Compound(null, [
			"Y" => new ByteTag("Y", $Y),
			"Blocks" => new ByteArray("Blocks", str_repeat("\x00", 4096)),
			"Data" => new ByteArray("Data", str_repeat("\x00", 2048)),
			"SkyLight" => new ByteArray("SkyLight", str_repeat("\xff", 2048)),
			"BlockLight" => new ByteArray("BlockLight", str_repeat("\x00", 2048))
		]));
	}

	public function isChunkGenerated($chunkX, $chunkZ) {
		if (($region = $this->getRegion($chunkX >> 5, $chunkZ >> 5)) instanceof $this->regionLoaderClass) {
			return $region->chunkExists($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32) and $this->getChunk($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32, true)->isGenerated();
		}

		return false;
	}

	protected function loadRegion($x, $z) {
		if (isset($this->regions[$index = Level::chunkHash($x, $z)])) {
			return true;
		}

		$this->regions[$index] = new $this->regionLoaderClass($this, $x, $z);

		return true;
	}
	
	public static function getMaxY() {
		return 256;
	}
	
	public static function getYMask() {
		return 0xff;
	}
}
