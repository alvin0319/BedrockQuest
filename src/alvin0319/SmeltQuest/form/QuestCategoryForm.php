<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\form;

use alvin0319\SmeltQuest\SmeltQuest;
use pocketmine\form\Form;
use pocketmine\Player;
use function array_map;

final class QuestCategoryForm implements Form{

	protected array $categories = [];

	public function jsonSerialize() : array{
		$this->categories = SmeltQuest::getInstance()->getCategories();
		return [
			"type" => "form",
			"title" => SmeltQuest::$lang->translateString("form.category.title"),
			"content" => SmeltQuest::$lang->translateString("form.category.content"),
			"buttons" => array_map(function(string $name) : array{
				return ["text" => "§l{$name}"];
			}, $this->categories)
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null){
			return;
		}
		if(!isset($this->categories[$data])){
			return;
		}
		$player->sendForm(new QuestCategoryInfoForm($player, SmeltQuest::getInstance()->getCategory($this->categories[$data])));
	}
}