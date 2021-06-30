<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\form;

use alvin0319\SmeltQuest\quest\BlockBreakQuest;
use alvin0319\SmeltQuest\quest\BlockPlaceQuest;
use alvin0319\SmeltQuest\quest\QuestManager;
use alvin0319\SmeltQuest\SmeltQuest;
use pocketmine\form\Form;
use pocketmine\Player;
use function count;
use function trim;

final class QuestCreateForm implements Form{

	public function jsonSerialize() : array{
		return [
			"type" => "form",
			"title" => "Quest creation",
			"content" => [
				[
					"type" => "input",
					"text" => "Name of quest"
				],
				[
					"type" => "input",
					"text" => "Short description of quest"
				],
				[
					"type" => "dropdown",
					"text" => "Quest type",
					"options" => QuestManager::getQuests()
				],
				[
					"type" => "dropdown",
					"text" => "Quest clear type",
					"options" => ["Repeat", "Daily", "Once"]
				]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null || count($data) !== 4){
			return;
		}
		[$name, $description, $questTypeInt, $clearTYpe] = $data;

		if(trim($name) === "" || SmeltQuest::getInstance()->getQuestManager()->getQuest($name) !== null){
			$player->sendMessage(SmeltQuest::$prefix . "Quest name is empty or duplicate with another quest.");
			return;
		}
		$questType = QuestManager::getQuests()[$questTypeInt];
		switch($questType){
			case BlockBreakQuest::getIdentifier():
			case BlockPlaceQuest::getIdentifier():

		}
	}
}