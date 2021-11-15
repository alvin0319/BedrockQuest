<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\form;

use alvin0319\BedrockQuest\BedrockQuest;
use alvin0319\BedrockQuest\quest\KillPlayerQuest;
use pocketmine\form\Form;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;
use function array_keys;
use function array_shift;
use function count;
use function is_numeric;

final class KillPlayerQuestCreateForm implements Form{

	protected array $data;

	public function __construct(array $data){
		$this->data = $data;
	}

	public function jsonSerialize() : array{
		return [
			"type" => "custom_form",
			"title" => "KillPlayer quest create form",
			"content" => [
				[
					"type" => "input",
					"text" => "Count of entities that player need to kill"
				]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null || count($data) !== 1){
			return;
		}
		$success = false;
		try{
			$count = array_shift($data);
			if(!is_numeric($count) || ($count = (int) $count) < 1){
				$player->sendMessage(BedrockQuest::$prefix . "Count must be at least 1.");
				return;
			}
			$map = array_keys(AddActorPacket::LEGACY_ID_MAP_BC);
			$quest = new KillPlayerQuest(
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
				[]
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