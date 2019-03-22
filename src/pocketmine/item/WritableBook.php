<?php


namespace pocketmine\item;

class WritableBook extends Item {

	public function __construct() {
		parent::__construct(self::WRITABLE_BOOK, 0, 1, "Book & Quill");
	}
	
	public function getMaxStackSize() {
		return 1;
	}

}
