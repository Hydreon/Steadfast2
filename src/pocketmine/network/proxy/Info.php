<?php

namespace pocketmine\network\proxy;

interface Info {

	const CURRENT_PROTOCOL = 1;
	const CONNECT_PACKET = 0x01;
	const DISCONNECT_PACKET = 0x02;
	const REDIRECT_PACKET = 0x03;
	const PING_PACKET = 0x04;
	const DISCONNECT_COMPLETE_PACKET = 0x05;
}
