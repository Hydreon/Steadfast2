<?php


namespace pocketmine\network\protocol;


interface NetworkSession
{
    public function handleDataPacket(PEPacket $packet);
    public function handlePlaySound(\pocketmine\network\protocol\v120\PlaySoundPacket $packet) : bool;
    public function handleStopSound(\pocketmine\network\protocol\v120\StopSoundPacket $packet) : bool;
}