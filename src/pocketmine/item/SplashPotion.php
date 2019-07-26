<?php

namespace pocketmine\item;

use pocketmine\entity\Effect;
use function count;
use function numfmt_parse;

class SplashPotion extends Item
{
	public function __construct($meta = 0, $count = 1)
    {
        parent::__construct(self::SPLASH_POTION, $meta, $count, "Splash Potion"); //Custom naming could be handled by the client itself
    }

	public static function getColor(int $meta): array
    {
        if(($effect = Effect::getEffect(self::getEffectId($meta))) !== null){
            return $effect->getColor();
        }

        return [0, 0, 0];
    }
	
	public static function getEffectId(int $meta): int
    {
        return Potion::POTIONS[$meta][0] ?? 0;
    }
	
	public function getMaxStackSize(): int
    {
        return 1;
    }

	/**
     * @param int $id
     * @return Effect[]
     */
	public static function getEffectsById(int $id): array
    {
        $effects = [];

        $potion = Potion::POTIONS[$id] ?? [];
        if(count($potion) === 3){
            $effect = Effect::getEffect($potion[0]);
            $effect->setDuration($potion[1]);
            $effect->setAmplifier($potion[2]);

            $effects[] = $effect;
        }

        return $effects;
    }
}