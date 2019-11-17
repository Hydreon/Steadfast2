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

use pocketmine\Player;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;

class BatchPacket extends PEPacket
{

    const NETWORK_ID = -1;

    const PACKET_NAME = "BATCH_PACKET";

    public $payload;

    /** @var int */
    protected $compressionLevel = 7;

    public function decode($playerProtocol)
    {
        $this->payload = $this->get(strlen($this->getBuffer()) - $this->getOffset());
    }

    public function encode($playerProtocol)
    {
        $this->setBuffer($this->payload);
    }

    /**
     * @param DataPacket $packet
     */
    public function addPacket(DataPacket $packet)
    {
        $this->payload .= Binary::writeUnsignedVarInt(strlen($packet->buffer)) . $packet->buffer;
    }

    /**
     * @return \Generator
     */
    public function getPackets()
    {
        $stream = new BinaryStream($this->payload);
        $count = 0;
        while (!$stream->feof()) {
            if ($count++ >= 500) {
                throw new \UnexpectedValueException("Too many packets in a single batch");
            }
            yield $stream->getString();
        }
    }

    public function getCompressionLevel(): int
    {
        return $this->compressionLevel;
    }

    public function setCompressionLevel(int $level)
    {
        $this->compressionLevel = $level;
    }

}