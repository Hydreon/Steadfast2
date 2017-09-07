<?php

namespace pocketmine\level\format\pmanvil;

use pocketmine\level\format\anvil\Anvil;
use pocketmine\nbt\NBT;
use pocketmine\tile\Spawnable;
use pocketmine\utils\ChunkException;

class PMAnvil extends Anvil {

	const REGION_FILE_EXTENSION = "mcapm";

	protected $chunkClass = Chunk::class;
	protected $regionLoaderClass = RegionLoader::class;
	protected static $chunkSectionClass = ChunkSection::class;

	public static function getProviderName() {
		return "pmanvil";
	}

	public function requestChunkTask($x, $z, $protocols, $subClientsId) {
		$chunk = $this->getChunk($x, $z, false);
		if (!($chunk instanceof $this->chunkClass)) {
			throw new ChunkException("Invalid Chunk sent");
		}
		$tiles = "";
		$nbt = new NBT(NBT::LITTLE_ENDIAN);
		foreach ($chunk->getTiles() as $tile) {
			if ($tile instanceof Spawnable) {
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
		$data['isSorted'] = true;
		$data['chunk'] = $this->getChunkData($chunk);
		$this->getLevel()->chunkMaker->pushMainToThreadPacket(serialize($data));
		return null;
	}

}
