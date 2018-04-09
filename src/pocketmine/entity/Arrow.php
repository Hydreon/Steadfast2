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

namespace pocketmine\entity;

use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\Player;
use pocketmine\Server;

class Arrow extends Projectile{
    const NETWORK_ID = 80;
    public $width = 0.5;
    public $length = 0.5;
    public $height = 0.5;
    protected $gravity = 0.03;
    protected $drag = 0.01;
    protected $damage = 2;
    protected $isCritical;

    public function __construct(FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null, $critical = false){
        $this->isCritical = (bool) $critical;
        parent::__construct($chunk, $nbt, $shootingEntity);
    }

    public function onUpdate($currentTick){
        if($this->closed){
            return false;
        }
        //$this->timings->startTiming();
        $hasUpdate = parent::onUpdate($currentTick);
        if(!$this->hadCollision and $this->isCritical){
            $this->level->addParticle(new CriticalParticle($this->add(
                $this->width / 2 + mt_rand(-100, 100) / 500,
                $this->height / 2 + mt_rand(-100, 100) / 500,
                $this->width / 2 + mt_rand(-100, 100) / 500)));
        }elseif($this->onGround){
            $this->isCritical = false;
        }
        if($this->age > 1200){
            $this->kill();
            $hasUpdate = true;
        } elseif ($this->y < 1) {
            $this->kill();
            $hasUpdate = true;
        }
        //$this->timings->stopTiming();
        return $hasUpdate;
    }

    public function getBoundingBox() {
        $bb = clone parent::getBoundingBox();
        return $bb;
    }

    public function move($dx, $dy, $dz) {
        $this->blocksAround = null;
        if ($dx == 0 && $dz == 0 && $dy == 0) {
            return true;
        }

        if ($this->keepMovement) {
            $this->boundingBox->offset($dx, $dy, $dz);
            $this->setPosition(new Vector3(($this->boundingBox->minX + $this->boundingBox->maxX) / 2, $this->boundingBox->minY, ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2));
            return true;
        }
        $pos = new Vector3($this->x + $dx, $this->y + $dy, $this->z + $dz);
        if (!$this->setPosition($pos)) {
            return false;
        }
        $bb = clone $this->boundingBox;
        $this->onGround = count($this->level->getCollisionBlocks($bb)) > 0;
        $this->isCollided = $this->onGround;
        $this->updateFallState($dy, $this->onGround);
        return true;
    }

    public function onCollideWithPlayer(Player $player){
        $item = ItemItem::get(ItemItem::ARROW);
        if($player->isSurvival() and !$player->getInventory()->canAddItem($item)){
            return;
        }

        $this->server->getPluginManager()->callEvent($ev = new InventoryPickupArrowEvent($player->getInventory(), $this));
        if($ev->isCancelled()){
            return;
        }

        $pk = new TakeItemEntityPacket();
        $pk->eid = $player->getId();
        $pk->target = $this->getId();
        Server::broadcastPacket($this->getViewers(), $pk);

        $player->getInventory()->addItem(clone $item);
        $this->kill();
    }
}