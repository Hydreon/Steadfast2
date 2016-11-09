<?php

namespace proxy\network;

interface AdvancedSourceInterface extends SourceInterface {

	public function blockAddress($address, $timeout = 300);

	public function setNetwork(Network $network);

	public function sendRawPacket($address, $port, $payload);
}
