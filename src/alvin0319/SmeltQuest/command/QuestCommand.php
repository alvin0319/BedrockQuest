<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\command;

use alvin0319\SmeltQuest\form\QuestCategoryForm;
use alvin0319\SmeltQuest\SmeltQuest;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use function array_shift;
use function count;

final class QuestCommand extends Command{

	public function __construct(){
		parent::__construct("quest", "Open the Quest UI");
		$this->setDescription("smeltquest.command.use");
		$this->setAliases(["q"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(!$sender instanceof Player){
			$sender->sendMessage(SmeltQuest::$prefix . "You cannot run this command on console.");
			return false;
		}
		if(!$sender->hasPermission("smeltquest.command.manage")){
			$sender->sendForm(new QuestCategoryForm());
			return true;
		}
		if(count($args) < 1){
			$sender->sendForm(new QuestCategoryForm());
			return true;
		}
		switch(array_shift($args)){
			case "create":

		}
	}
}