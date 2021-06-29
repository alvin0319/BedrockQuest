<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\form;

use alvin0319\SmeltQuest\quest\Quest;
use alvin0319\SmeltQuest\SmeltQuest;
use pocketmine\form\Form;
use pocketmine\Player;
use function array_map;

final class QuestCategoryInfoForm implements Form{

	protected Player $player;

	/** @var Quest[] */
	protected array $quests = [];

	public function __construct(Player $player, array $quests){
		$this->player = $player;
		$this->quests = $quests;
	}

	public function jsonSerialize() : array{
		return [
			"type" => "form",
			"title" => SmeltQuest::$lang->translateString("form.category.info.title"),
			"content" => SmeltQuest::$lang->translateString("form.category.info.content"),
			"buttons" => array_map(function(Quest $quest) : array{
				return ["text" => (string) $quest->getName() . "\n" . ($quest->canStart($this->player)) ? "§cIncomplete" : "§aCompleted"];
			}, $this->quests)
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null){
			return;
		}
		if(!isset($this->quests[$data])){
			return;
		}
		$player->sendForm(new QuestInfoForm($player, $this->quests[$data]));
	}
}