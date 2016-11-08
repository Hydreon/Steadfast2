<?php

namespace proxy\network;

use proxy\network\protocol\DataPacket;
use proxy\Player;

interface SourceInterface {

	public function putPacket(Player $player, DataPacket $packet, $needACK = false, $immediate = true);

	public function close(Player $player, $reason = "unknown reason");

	public function setName($name);

	public function process();

	public function shutdown();

	public function emergencyShutdown();
}
