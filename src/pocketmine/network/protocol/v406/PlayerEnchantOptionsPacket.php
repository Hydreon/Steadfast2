<?php

namespace pocketmine\network\protocol\v392;

use pocketmine\network\protocol\PEPacket;
use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\Info;

class PlayerEnchantOptionsPacket extends PEPacket {

	const NETWORK_ID = Info::PLAYER_ENCHANT_OPTIONS_PACKET;
	const PACKET_NAME = "PLAYER_ENCHANT_OPTIONS_PACKET";
	

	public $groups;
	public $items;

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt(0); // array size

        foreach ($this->items as $item) {
            $this->putVarInt(0); // cost
            $this->putSlot($item);;
            $this->putString("name"); // enchant name
            $this->putVarInt(0);  // SimpleServerNetId<struct RecipeNetIdTag, unsinged int, 0>
        }

	}

}