<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\form;

use alvin0319\BedrockQuest\BedrockQuest;
use alvin0319\BedrockQuest\quest\KillEntityQuest;
use pocketmine\form\Form;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function is_numeric;
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
					"type" => "dropdown",
					"text" => "Entity Network id",
					"options" => array_map(fn(string $name) => str_replace("minecraft:", "", $name), array_values(AddActorPacket::LEGACY_ID_MAP_BC))
				],
				[
					"type" => "input",
					"text" => "Count of entities that player need to kill"
				]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null || count($data) !== 2){
			return;
		}
		$success = false;
		try{
			[$entityNetId, $count] = $data;
			if(!is_numeric($count) || ($count = (int) $count) < 1){
				$player->sendMessage(BedrockQuest::$prefix . "Count must be at least 1.");
				return;
			}
			$map = array_keys(AddActorPacket::LEGACY_ID_MAP_BC);
			$entityNetId = $map[$entityNetId];
			$quest = new KillEntityQuest(
				$this->data["name"],
				$this->data["description"],
				$this->data["clearType"],
				[],
				[],
				[],
				0,
				[],
				$count,
				[],
				[],
				$entityNetId
			);
			BedrockQuest::getInstance()->getQuestManager()->registerQuest($quest);
			BedrockQuest::getInstance()->addQuestToCategory($this->data["questCategory"], $quest);
			$player->sendMessage(BedrockQuest::$prefix . "Success! Don't forget to add rewards!");
			$success = true;
		}finally{
			if(!$success){
				$player->sendForm($this);
			}
		}
	}
}