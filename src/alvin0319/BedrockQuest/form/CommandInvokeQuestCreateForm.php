<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\form;

use alvin0319\BedrockQuest\BedrockQuest;
use alvin0319\BedrockQuest\quest\CommandInvokeQuest;
use pocketmine\form\Form;
use pocketmine\Player;
use function array_shift;
use function count;
use function trim;

final class CommandInvokeQuestCreateForm implements Form{

	protected array $data;

	public function __construct(array $data){
		$this->data = $data;
	}

	public function jsonSerialize() : array{
		return [
			"type" => "custom_form",
			"title" => "CommandInvoke quest create form",
			"content" => [
				[
					"type" => "input",
					"text" => "Command that player need to execute (except \"/\")",
					"placeholder" => "ex) help"
				]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null || count($data) !== 1){
			return;
		}
		$command = array_shift($data);
		$success = false;
		try{
			if(trim($command) === ""){
				$player->sendMessage(BedrockQuest::$prefix . "Invalid command given.");
				return;
			}
			$quest = new CommandInvokeQuest(
				$this->data["name"],
				$this->data["description"],
				$this->data["clearType"],
				[],
				[],
				[],
				0,
				[],
				[],
				$command
			);
			$player->sendMessage(BedrockQuest::$prefix . "Success! Don't forget to add rewards!");
			BedrockQuest::getInstance()->getQuestManager()->registerQuest($quest);
			BedrockQuest::getInstance()->addQuestToCategory($this->data["questCategory"], $quest);
			$success = true;
		}finally{
			if(!$success){
				$player->sendForm($this);
			}
		}
	}
}