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

class Anvil extends McRegion {

	/** @var RegionLoader[] */
	protected $regions = [];

	/** @var Chunk[] */
	protected $chunks = [];

	public static function getProviderName() {
		return "anvil";
	}

	public static function getProviderOrder() {
		return self::ORDER_YZX;
	}

	public static function usesChunkSection() {
		return true;
	}

	public static function isValid($path) {
		$isValid = (file_exists($path . "/level.dat") and is_dir($path . "/region/"));

		if ($isValid) {
			$files = glob($path . "/region/*.mc*");
			foreach ($files as $f) {
				if (strpos($f, ".mcr") !== false) { //McRegion
					$isValid = false;
					break;
				}
			}
		}

		return $isValid;
	}

	public function requestChunkTask($x, $z) {
		$chunk = $this->getChunk($x, $z, false);
		if(!($chunk instanceof Chunk)){
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
		$data['tiles'] = $tiles;
		$data['chunk'] = $this->getChunkData($chunk);
		
		$this->getLevel()->chunkMaker->pushMainToThreadPacket(serialize($data));
		return null;
	}
		
		
	private function getChunkData($chunk) {
		$orderedIds = "";
		$orderedData = "";
		$orderedSkyLight = "";
		$orderedLight = "";

		$ids = "";
		$meta = "";
		$blockLight = "";
		$skyLight = "";

		foreach($chunk->getSections() as $section){
			$ids .= $section->getIdArray();
			$meta .= $section->getDataArray();
			$blockLight .= $section->getLightArray();
			$skyLight .= $section->getSkyLightArray();
		}

		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				$orderedIds .= $this->getColumn($ids, $x, $z);
				$orderedData .= $this->getHalfColumn($meta, $x, $z);
				$orderedSkyLight .= $this->getHalfColumn($skyLight, $x, $z);
				$orderedLight .= $this->getHalfColumn($blockLight, $x, $z);
			}
		}
		$heightmap = pack("C*", ...$chunk->getHeightMapArray());
		$biomeColors = pack("N*", ...$chunk->getBiomeColorArray());
		return
			Binary::writeInt($chunk->getX()) . 
			Binary::writeInt($chunk->getZ()) .
			$orderedIds .
			$orderedData .
			$orderedSkyLight .
			$orderedLight .
			$heightmap .
			$biomeColors .
			chr(($chunk->isPopulated() ? 1 << 1 : 0) + ($chunk->isGenerated() ? 1 : 0));
	}
	
	private function getColumn($data, $x, $z){
		$column = "";
		$i = ($z << 4) + $x;
		for($y = 0; $y < 128; ++$y){
			$column .= $data{($y << 8) + $i};
		}
		return $column;
	}
	
	private function getHalfColumn($data, $x, $z){
		$column = "";
		$i = ($z << 3) + ($x >> 1);
		if(($x & 1) === 0){
			for($y = 0; $y < 128; $y += 2){
				$column .= ($data{($y << 7) + $i} & "\x0f") | chr((ord($data{(($y + 1) << 7) + $i}) & 0x0f) << 4);
			}
		}else{
			for($y = 0; $y < 128; $y += 2){
				$column .= chr((ord($data{($y << 7) + $i}) & 0xf0) >> 4) | ($data{(($y + 1) << 7) + $i} & "\xf0");
			}
		}
		return $column;
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
		if (!($chunk instanceof Chunk)) {
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
		return Chunk::getEmptyChunk($chunkX, $chunkZ, $this);
	}

	public static function createChunkSection($Y) {
		return new ChunkSection(new Compound(null, [
			"Y" => new ByteTag("Y", $Y),
			"Blocks" => new ByteArray("Blocks", str_repeat("\x00", 4096)),
			"Data" => new ByteArray("Data", str_repeat("\x00", 2048)),
			"SkyLight" => new ByteArray("SkyLight", str_repeat("\xff", 2048)),
			"BlockLight" => new ByteArray("BlockLight", str_repeat("\x00", 2048))
		]));
	}

	public function isChunkGenerated($chunkX, $chunkZ) {
		if (($region = $this->getRegion($chunkX >> 5, $chunkZ >> 5)) instanceof RegionLoader) {
			return $region->chunkExists($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32) and $this->getChunk($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32, true)->isGenerated();
		}

		return false;
	}

	protected function loadRegion($x, $z) {
		if (isset($this->regions[$index = Level::chunkHash($x, $z)])) {
			return true;
		}

		$this->regions[$index] = new RegionLoader($this, $x, $z);

		return true;
	}
}
