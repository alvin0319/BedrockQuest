<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\command;

use alvin0319\SmeltQuest\form\QuestCategoryForm;
use alvin0319\SmeltQuest\form\QuestCreateForm;
use alvin0319\SmeltQuest\form\QuestRemoveForm;
use alvin0319\SmeltQuest\SmeltQuest;
use muqsit\invmenu\InvMenu;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Player;
use function array_shift;
use function count;
use function implode;
use function in_array;
use function is_numeric;
use function trim;

final class QuestCommand extends Command{

	public function __construct(){
		parent::__construct("quest", "Open the Quest UI");
		$this->setPermission("smeltquest.command.use");
		$this->setAliases(["q"]);
		$this->setUsage("/{$this->getName()} <create|remove|reward|category|addcmd>");
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
				$sender->sendForm(new QuestCreateForm());
				break;
			case "remove":
				$sender->sendForm(new QuestRemoveForm());
				break;
			case "reward":
				if(count($args) < 2){
					$sender->sendMessage(SmeltQuest::$prefix . "Usage: /{$commandLabel} reward <quest> <item | money> <money(optional)>");
					return false;
				}
				$questName = array_shift($args);
				$rewardType = array_shift($args);
				if(($quest = SmeltQuest::getInstance()->getQuestManager()->getQuest($questName)) === null){
					$sender->sendMessage(SmeltQuest::$prefix . "Quest {$questName} does not exist.");
					return false;
				}
				switch($rewardType){
					case "item":
						$menu = InvMenu::create(InvMenu::TYPE_CHEST);
						$menu->setName("Quest {$quest->getName()} Rewards");
						$menu->getInventory()->addItem(...$quest->getRewards());
						$menu->setInventoryCloseListener(function(Player $player) use ($quest, $menu) : void{
							$quest->setRewards($menu->getInventory()->getContents(false));
							$player->sendMessage(SmeltQuest::$prefix . "Rewards were successfully updated!");
						});
						$menu->send($sender);
						break;
					case "money":
						if(count($args) < 1){
							$sender->sendMessage(SmeltQuest::$prefix . "Usage: /{$commandLabel} reward <quest> <money> <money(Int)>");
							return false;
						}
						$money = array_shift($args);
						if(!is_numeric($money) || ($money = (int) $money) < 0){
							$sender->sendMessage(SmeltQuest::$prefix . "Money must be at least 0.");
							return false;
						}
						$quest->setRewardMoney($money);
						$sender->sendMessage(SmeltQuest::$prefix . "Reward money has been set to {$money}.");
						break;
					default:
						$sender->sendMessage(SmeltQuest::$prefix . "Unknown reward type {$rewardType}");
				}
				break;
			case "category":
				if(count($args) < 1){
					$sender->sendMessage(SmeltQuest::$prefix . "Usage: /{$commandLabel} category <category>");
					return false;
				}
				$category = array_shift($args);
				if(SmeltQuest::getInstance()->getCategory($category) !== null){
					$sender->sendMessage(SmeltQuest::$prefix . "Category is exist!");
					return false;
				}
				SmeltQuest::getInstance()->createCategory($category);
				$sender->sendMessage(SmeltQuest::$prefix . "Category {$category} created!");
				break;
			case "addcmd":
				if(count($args) < 4){
					$sender->sendMessage(SmeltQuest::$prefix . "Usage: /{$commandLabel} addcmd <quest> <cmd> <console|player> <rewardMessage>");
					$sender->sendMessage(SmeltQuest::$prefix . "Supported tags: @player");
					return false;
				}

				$questName = array_shift($args);
				$command = array_shift($args);
				$consoleOrPlayer = array_shift($args);
				$rewardMessage = implode(" ", $args);

				$quest = SmeltQuest::getInstance()->getQuestManager()->getQuest($questName);
				if($quest === null){
					$sender->sendMessage(SmeltQuest::$prefix . "Quest {$questName} not found.");
					return false;
				}

				if(!in_array($consoleOrPlayer, ["console", "player"])){
					$sender->sendMessage(SmeltQuest::$prefix . "Invalid command dispatch type (expected \"console\", \"player\", got \"$consoleOrPlayer\")");
					return false;
				}

				$quest->addExecuteCommand($command, $consoleOrPlayer);
				if(trim($rewardMessage) != ""){
					$quest->setAdditionalRewardMessage($rewardMessage);
				}

				$sender->sendMessage(SmeltQuest::$prefix . "Command {$command} has been added to quest.");
				break;
			default:
				throw new InvalidCommandSyntaxException();
		}
		return true;
	}
}