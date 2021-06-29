<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

use alvin0319\SmeltQuest\SmeltQuest;
use JsonSerializable;
use pocketmine\item\Item;
use pocketmine\Player;
use function array_map;
use function microtime;

abstract class Quest implements JsonSerializable{

	public const TYPE_REPEAT = 0;
	public const TYPE_DAILY = 1;
	public const TYPE_ONCE = 2;

	protected string $name;
	protected string $description;
	protected int $clearType;

	protected array $playingPlayers = [];
	protected array $completedPlayers = [];

	protected int $rewardMoney = 0;
	/** @var Item[] */
	protected array $rewards = [];

	public function __construct(string $name, string $description, int $clearType, array $playingPlayers, array $completedPlayers, int $rewardMoney, array $rewards){
		$this->name = $name;
		$this->description = $description;
		$this->clearType = $clearType;
		$this->playingPlayers = $playingPlayers;
		$this->completedPlayers = $completedPlayers;
		$this->rewardMoney = $rewardMoney;
		$this->rewards = array_map(fn(array $data) => Item::jsonDeserialize($data), $rewards);
	}

	public function getName() : string{ return $this->name; }

	public function getDescription() : string{ return $this->description; }

	public function getPlayingPlayers() : array{ return $this->playingPlayers; }

	public function getCompletedPlayers() : array{ return $this->completedPlayers; }

	public function getRewardMoney() : int{ return $this->rewardMoney; }

	/** @return Item[] */
	public function getRewards() : array{ return $this->rewards; }

	abstract public function getGoal() : string;

	public function canStart(Player $player) : bool{
		if(isset($this->completedPlayers[$player->getLowerCaseName()])){
			if($this->clearType === self::TYPE_ONCE){
				return false;
			}
			if($this->clearType === self::TYPE_DAILY){
				return microtime(true) - $this->completedPlayers[$player->getLowerCaseName()] >= 60 * 60 * 24;
			}
		}
		return true;
	}

	public function isStarted(Player $player) : bool{ return isset($this->playingPlayers[$player->getLowerCaseName()]); }

	public function start(Player $player) : void{
		if(!$this->canStart($player)){
			return;
		}
		$this->playingPlayers[$player->getLowerCaseName()] = microtime(true);
		$player->sendMessage(SmeltQuest::$prefix . SmeltQuest::$lang->translateString("quest.message.started", [$this->getName()]));
		$player->sendMessage(SmeltQuest::$prefix . SmeltQuest::$lang->translateString("quest.message.started.goal", [$this->getGoal()]));
	}

	public function end(Player $player) : void{
		if(!$this->isStarted($player)){
			return;
		}
		$this->completedPlayers[$player->getLowerCaseName()] = microtime(true);
	}
}