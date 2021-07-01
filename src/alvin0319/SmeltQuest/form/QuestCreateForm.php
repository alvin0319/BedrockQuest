<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\form;

use alvin0319\SmeltQuest\quest\BlockBreakQuest;
use alvin0319\SmeltQuest\quest\BlockPlaceQuest;
use alvin0319\SmeltQuest\quest\CommandInvokeQuest;
use alvin0319\SmeltQuest\quest\KillEntityQuest;
use alvin0319\SmeltQuest\quest\KillPlayerQuest;
use alvin0319\SmeltQuest\quest\QuestManager;
use alvin0319\SmeltQuest\SmeltQuest;
use pocketmine\form\Form;
use pocketmine\Player;
use function count;
use function trim;

final class QuestCreateForm implements Form{

	public function jsonSerialize() : array{
		return [
			"type" => "custom_form",
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
					"text" => "Quest category",
					"options" => SmeltQuest::getInstance()->getCategories()
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
		if($data === null || count($data) !== 5){
			return;
		}
		[$name, $description, $questCategoryInt, $questTypeInt, $clearType] = $data;

		if(trim($name) === "" || SmeltQuest::getInstance()->getQuestManager()->getQuest($name) !== null){
			$player->sendMessage(SmeltQuest::$prefix . "Quest name is empty or duplicate with another quest.");
			return;
		}
		$questType = QuestManager::getQuests()[$questTypeInt];
		$questData = [
			"name" => $name,
			"description" => $description,
			"clearType" => $clearType,
			"questType" => $questType,
			"questCategory" => SmeltQuest::getInstance()->getCategories()[$questCategoryInt]
		];
		switch($questType){
			case BlockBreakQuest::getIdentifier():
			case BlockPlaceQuest::getIdentifier():
				$player->sendForm(new BlockQuestCreateForm($questData));
				break;
			case KillEntityQuest::getIdentifier():
				$player->sendForm(new KillEntityQuestCreateForm($questData));
				break;
			case KillPlayerQuest::getIdentifier():
				$player->sendForm(new KillPlayerQuestCreateForm($questData));
				break;
			case CommandInvokeQuest::getIdentifier():
				$player->sendForm(new CommandInvokeQuestCreateForm($questData));
				break;
		}
	}
}