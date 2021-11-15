<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\form;

use alvin0319\BedrockQuest\BedrockQuest;
use alvin0319\BedrockQuest\quest\Quest;
use pocketmine\form\Form;
use pocketmine\Player;

final class QuestRemoveConfirmForm implements Form{

	protected Quest $quest;

	public function __construct(Quest $quest){
		$this->quest = $quest;
	}

	public function jsonSerialize() : array{
		return [
			"type" => "modal",
			"title" => "Quest remove confirm",
			"content" => "Are you sure to delete {$this->quest->getName()} quest?\n\nThis action cannot be reverted.",
			"button1" => "Yes",
			"button2" => "No"
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null){
			return;
		}
		if(!$data){
			return;
		}
		BedrockQuest::getInstance()->getQuestManager()->unregisterQuest($this->quest);
		BedrockQuest::getInstance()->removeQuestFromCategory(BedrockQuest::getInstance()->getCategoryFromQuest($this->quest), $this->quest);
		$player->sendMessage(BedrockQuest::$prefix . "You have sucessfully removed {$this->quest->getName()} quest.");
	}
}