<?php

namespace pocketmine\entity\animal\walking;

use pocketmine\entity\animal\FlyingAnimal;
use pocketmine\entity\Creature;

class Bat extends FlyingAnimal{
    //TODO: This isn't implemented yet
    const NETWORK_ID = 13;

    public $width = 0.3;
    public $height = 0.3;

    public function getName(){
        return "Bat";
    }

    public function initEntity(){
        parent::initEntity();

        //TODO: IDK Bat's health
        //$this->setMaxHealth(8);
    }

    public function targetOption(Creature $creature, float $distance){
        return false;
    }

    public function getDrops(){
        return [];
    }

}