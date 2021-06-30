<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\form;

use pocketmine\form\Form;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use function array_map;
use function array_values;
use function str_replace;

final class KillEntityQuestCreateForm implements Form{

	protected array $data;

	public function __construct(array $data){
		$this->data = $data;
	}

	public function jsonSerialize() : array{
		return [
			"type" => "custom_form",
			"title" => "KillEntity quest create form",
			"content" => [
				[
					"type" => "Dropdown",
					"text" => "Entity Network id",
					"options" => array_map(fn(string $name) => str_replace("minecraft:", "", $name), array_values(AddActorPacket::LEGACY_ID_MAP_BC))
				],

			]
		];
	}
}