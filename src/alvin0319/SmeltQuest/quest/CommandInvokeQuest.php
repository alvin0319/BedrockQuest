<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

use pocketmine\command\Command;
use pocketmine\Server;
use function array_merge;
use function preg_match_all;
use function stripslashes;

final class CommandInvokeQuest extends Quest{

	protected string $command;

	protected ?Command $tempCommand = null;

	protected array $tempArgs = [];

	public function __construct(
		string $name,
		string $description,
		int $clearType,
		array $playingPlayers,
		array $completedPlayers,
		array $records,
		int $rewardMoney,
		array $rewards,
		array $executeCommands = [],
		string $command = "",
		array $additionalRewardMessage = []
	){
		parent::__construct($name, $description, $clearType, $playingPlayers, $completedPlayers, $records, $rewardMoney, $rewards, $executeCommands, $additionalRewardMessage);
		$this->command = $command;
	}

	public static function getIdentifier() : string{
		return "CommandInvoke";
	}

	public function getGoal() : string{
		return "Use /{$this->command}";
	}

	public function getCommand() : string{ return $this->command; }

	public function getCommandObj() : ?Command{
		if($this->tempCommand !== null){
			return $this->tempCommand;
		}
		$args = [];
		preg_match_all('/"((?:\\\\.|[^\\\\"])*)"|(\S+)/u', $this->getCommand(), $matches);
		foreach($matches[0] as $k => $_){
			for($i = 1; $i <= 2; ++$i){
				if($matches[$i][$k] !== ""){
					$args[$k] = stripslashes($matches[$i][$k]);
					break;
				}
			}
		}
		$sentCommandLabel = "";
		$target = Server::getInstance()->getCommandMap()->matchCommand($sentCommandLabel, $args);
		$this->tempArgs = $args;
		return $this->tempCommand = $target;
	}

	public function getArgs() : array{
		return $this->tempArgs;
	}

	public function jsonSerialize() : array{
		return array_merge(
			parent::jsonSerialize(),
			["command" => $this->command]
		);
	}

	public static function jsonDeserialize(array $data) : CommandInvokeQuest{
		return new CommandInvokeQuest(
			$data["name"],
			$data["description"],
			$data["clearType"],
			$data["playingPlayers"],
			$data["completedPlayers"],
			$data["records"],
			$data["rewardMoney"],
			$data["rewards"],
			$data["executeCommands"],
			$data["command"],
			$data["additionalRewardMessage"]
		);
	}
}