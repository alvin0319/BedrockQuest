<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\form;

use alvin0319\BedrockQuest\quest\Quest;
use alvin0319\BedrockQuest\SmeltQuest;
use pocketmine\form\Form;
use pocketmine\Player;
use function array_map;

final class QuestRemoveForm implements Form{

	/** @var Quest[] */
	protected array $quests = [];

	public function jsonSerialize() : array{
		$this->quests = SmeltQuest::getInstance()->getQuestManager()->getQuestList();
		return [
			"type" => "form",
			"title" => "Quest remove",
			"content" => "",
			"buttons" => array_map(fn(Quest $quest) => ["text" => (string) $quest->getName()], $this->quests)
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null){
			return;
		}
		if(!isset($this->quests[$data])){
			return;
		}
		$player->sendForm(new QuestRemoveConfirmForm($this->quests[$data]));
	}
}