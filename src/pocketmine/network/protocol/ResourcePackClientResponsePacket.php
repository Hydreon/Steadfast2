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

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>

class ResourcePackClientResponsePacket extends PEPacket{
	const NETWORK_ID = Info::RESOURCE_PACK_CLIENT_RESPONSE_PACKET;
	const PACKET_NAME = "RESOURCE_PACK_CLIENT_RESPONSE_PACKET";

	const STATUS_REFUSED = 1;
	const STATUS_SEND_PACKS = 2;
	const STATUS_HAVE_ALL_PACKS = 3;
	const STATUS_COMPLETED = 4;

	public $status;
	public $packIds = [];

	public function decode($playerProtocol){
		$this->status = $this->getByte();
		$entryCount = $this->getLShort();
		while($entryCount-- > 0){
			$this->packIds[] = $this->getString();
		}
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putByte($this->status);
		$this->putLShort(count($this->packIds));
		foreach($this->packIds as $id){
			$this->putString($id);
		}
	}
}
