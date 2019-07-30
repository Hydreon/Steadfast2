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

namespace pocketmine\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\multiversion\Entity as EntityIds;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\Server;

class SplashPotion extends Projectile
{
    const NETWORK_ID = 86;

    public $width = 0.25;
    public $height = 0.25;

    protected $drag = 0.01;
    protected $gravity = 0.05;

    public function __construct(FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null, int $potionId = 0)
    {
        parent::__construct($chunk, $nbt, $shootingEntity);

        $this->setPotionId($potionId);
    }

    public function setPotionId(int $meta)
    {
        $this->setDataProperty(self::DATA_POTION_AUX_VALUE, self::DATA_TYPE_SHORT, $meta);
    }

    public function getPotionId(): int
    {
        return $this->getDataProperty(self::DATA_POTION_AUX_VALUE) ?? 0;
    }

    public function onUpdate($currentTick)
    {
        if ($this->closed) {
            return false;
        }

        //$this->timings->startTiming();

        $hasUpdate = parent::onUpdate($currentTick);

        if ($this->onGround || $this->hadCollision) {
            if($this->shootingEntity instanceof Player){
                $this->shootingEntity->sendSound("SOUND_GLASS", ['x' => $this->getX(), 'y' => $this->getY(), 'z' => $this->getZ()] ,EntityIds::ID_NONE, -1 ,$this->getViewers());
            }

            $color = \pocketmine\item\SplashPotion::getColor($this->getPotionId());

            $pk = new LevelEventPacket;
            $pk->evid = LevelEventPacket::EVENT_PARTICLE_SPLASH;
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->data = ($color[0] << 16) + ($color[1] << 8) + $color[2];
            Server::broadcastPacket($this->getViewers(), $pk);

            foreach ($this->level->getNearbyEntities($this->boundingBox->grow(4, 4, 4), $this) as $entity) { //todo: someone has to check this https://minecraft.gamepedia.com/Splash_Potion
                if ($entity->distanceSquared($this) <= 16) {
                    foreach (\pocketmine\item\SplashPotion::getEffectsById($this->getPotionId()) as $effect) {
                        $entity->addEffect($effect);
                    }
                }
            }

            $this->kill();
        }

        return $hasUpdate;
    }

    public function spawnTo(Player $player)
    {
        if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
            $this->hasSpawned[$player->getId()] = $player;
            $pk = new AddEntityPacket();
            $pk->type = self::NETWORK_ID;
            $pk->eid = $this->getId();
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->speedX = $this->motionX;
            $pk->speedY = $this->motionY;
            $pk->speedZ = $this->motionZ;
            $pk->metadata = $this->dataProperties;
            $player->dataPacket($pk);
        }
    }

}