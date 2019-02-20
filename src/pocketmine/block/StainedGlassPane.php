<?php

namespace pocketmine\block;

class StainedGlassPane extends GlassPane {

	protected $id = self::STAINED_GLASS_PANE;

	public function __construct($meta = 0) {
		$this->setDamage($meta);
	}

	public function getName() {
		return $this->getColorNameByMeta($this->meta) . 'Stained Glass Pane';
	}

}
