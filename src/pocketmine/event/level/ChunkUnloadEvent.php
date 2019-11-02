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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\level;

use pocketmine\event\Cancellable;
use pocketmine\level\format\FullChunk;

/**
 * Called when a Chunk is unloaded
 */
class ChunkUnloadEvent extends ChunkEvent implements Cancellable{
	public static $handlerList = null;

	private $safe;
	private $shouldSave = true;

	public function __construct(FullChunk $chunk, bool $safe) {
		parent::__construct($chunk);
		$this->safe = $safe;
	}

	/**
	 * @return bool
	 */
	public function isSafe(){
		return $this->safe;
	}

	/**
	 * @return bool
	 */
	public function shouldSave(){
		return $this->shouldSave;
	}

	/**
	 * @param bool $shouldSave
	 */
	public function setShouldSave(bool $shouldSave){
		$this->shouldSave = $shouldSave;
	}
}