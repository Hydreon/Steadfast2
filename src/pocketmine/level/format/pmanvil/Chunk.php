<?php

namespace pocketmine\level\format\pmanvil;

class Chunk extends \pocketmine\level\format\anvil\Chunk {

	protected static $chunkClass = Chunk::class;
	protected static $chunkSectionClass = ChunkSection::class;
	protected static $providerClass = PMAnvil::class;

}
