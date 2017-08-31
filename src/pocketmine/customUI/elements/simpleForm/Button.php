<?php

namespace pocketmine\customUI\elements\simpleForm;

use pocketmine\customUI\elements\UIElement;

abstract class Button extends UIElement {
	
	/** for in-client side images */
	const IMAGE_TYPE_PATH = 'path';
	/** for other images */
	const IMAGE_TYPE_URL = 'url';
	
	/** @va string May contains 'path' or 'url' */
	protected $imageType = '';
	
	/** @va string */
	protected $imagePath = '';

	/**
	 * 
	 * @param string $text Button text
	 */
	public function __construct($text) {
		$this->text = $text;
	}
	
	/**
	 * Add image to button
	 * 
	 * @param string $imageType
	 * @param string $imagePath
	 * @throws \Exception
	 */
	public function addImage($imageType, $imagePath) {
		if ($imageType != self::IMAGE_TYPE_PATH && $imageType != self::IMAGE_TYPE_URL) {
			throw new \Exception(__CLASS__.'::'.__METHOD__.' Invalid image type');
		}
		$this->imageType = $imageType;
		$this->imagePath = $imagePath;
	}

	/**
	 * Return array. Calls only in SimpleForm class
	 * 
	 * @return array
	 */
	final public function getDataToJson() {
		$data = [ 'text' => $this->text ];
		if ($this->imageType != '') {
			$data['image'] = [
				'type' => $this->imageType,
				'data' => $this->imagePath
			];
		}
		return $data;
	}

}
