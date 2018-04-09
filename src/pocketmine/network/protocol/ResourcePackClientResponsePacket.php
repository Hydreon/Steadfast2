<?php

/*
 *       _____ _                 _ ______        _   ___
 *      / ____| |               | |  ____|      | | |__ \
 *     | (___ | |_ ___  __ _  __| | |__ __ _ ___| |_   ) |
 *      \___ \| __/ _ \/ _` |/ _` |  __/ _` / __| __| / /
 *      ____) | ||  __/ (_| | (_| | | | (_| \__ \ |_ / /_
 *     |_____/ \__\___|\__,_|\__,_|_|  \__,_|___/\__|____|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Hydreon
 * @link http://hydreon.com/
 */

declare(strict_types=1);

namespace pocketmine\network\protocol;

class ResourcePackClientResponsePacket extends PEPacket {
    const NETWORK_ID = Info::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET;

    const STATUS_REFUSED = 1;
    const STATUS_SEND_PACKS = 2;
    const STATUS_HAVE_ALL_PACKS = 3;
    const STATUS_COMPLETED = 4;

    public $status;
    public $packIds = [];

    public function isAvailableBeforeLogin() : bool{
        return true;
    }

    public function decode($playerProtocol) {
        $this->getHeader($playerProtocol);
        $this->status = $this->getByte();
        $entryCount = $this->getLShort();
        while ($entryCount-- > 0) {
            $this->packIds[] = $this->getString();
        }
    }

    public function encode($playerProtocol) {

    }

}
