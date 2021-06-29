<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

use alvin0319\SmeltQuest\event\QuestClearEvent;
use alvin0319\SmeltQuest\SmeltQuest;
use alvin0319\SmeltQuest\util\TimeUtil;
use JsonSerializable;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\Item;
use pocketmine\Player;
use function array_map;
use function implode;
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

	/** @var float[] */
	protected array $records = [];

	protected int $rewardMoney = 0;
	/** @var Item[] */
	protected array $rewards = [];

	public function __construct(
		string $name,
		string $description,
		int $clearType,
		array $playingPlayers,
		array $completedPlayers,
		array $records,
		int $rewardMoney,
		array $rewards
	){
		$this->name = $name;
		$this->description = $description;
		$this->clearType = $clearType;
		$this->playingPlayers = $playingPlayers;
		$this->completedPlayers = $completedPlayers;
		$this->records = $records;
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

	public static function getIdentifier() : string{
		return "";
	}

	public function getRecords() : array{ return $this->records; }

	public function getProgress(Player $player) : float{
		return 0.0;
	}

	public function canStart(Player $player) : bool{
		if(isset($this->completedPlayers[$player->getLowerCaseName()])){
			if($this->clearType === self::TYPE_ONCE){
				return false;
			}
			if($this->clearType === self::TYPE_DAILY){
				return microtime(true) - $this->completedPlayers[$player->getLowerCaseName()] >= 60 * 60 * 24;
			}
		}
		return !isset($this->playingPlayers[$player->getLowerCaseName()]);
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

	public function canComplete(Player $player) : bool{
		return true;
	}

	public function complete(Player $player) : void{
		if(!$this->isStarted($player)){
			return;
		}
		$ev = new QuestClearEvent($player, $this, $this->getRewards(), $this->getRewardMoney());
		$ev->call();
		if($ev->isCancelled()){
			return;
		}

		unset($this->playingPlayers[$player->getLowerCaseName()]);

		$this->completedPlayers[$player->getLowerCaseName()] = microtime(true);
		$timeTook = microtime(true) - $this->playingPlayers[$player->getLowerCaseName()];

		$player->getInventory()->addItem(...$ev->getRewards());

		EconomyAPI::getInstance()->addMoney($player, $ev->getRewardMoney());

		$player->sendMessage(SmeltQuest::$prefix . SmeltQuest::$lang->translateString("quest.message.completed", [$this->getName()]));

		$player->sendMessage(SmeltQuest::$prefix . SmeltQuest::$lang->translateString("quest.message.completed.reward", [
				$ev->getRewardMoney(),
				implode(", ", array_map(fn(Item $item) => $item->getName() . " x" . $item->getCount(), $ev->getRewards()))
			]));

		if(isset($this->records[$player->getLowerCaseName()])){
			if($timeTook <= $this->records[$player->getLowerCaseName()]){
				return;
			}
		}

		$previousRecord = $this->records[$player->getName()] ?? 0;

		$previousTimestamp = TimeUtil::convertTime($previousRecord);

		$player->sendMessage(SmeltQuest::$prefix . SmeltQuest::$lang->translateString("quest.message.completed.newrecord", [
				SmeltQuest::$lang->translateString("time.format", [
					$previousTimestamp[0],
					$previousTimestamp[1],
					$previousTimestamp[2]
				])
			]));
	}

	public function jsonSerialize() : array{
		return [
			"name" => $this->name,
			"description" => $this->description,
			"clearType" => $this->clearType,
			"playingPlayers" => $this->playingPlayers,
			"completedPlayers" => $this->completedPlayers,
			"records" => $this->records,
			"rewardMoney" => $this->rewardMoney,
			"rewards" => array_map(fn(Item $item) => $item->jsonSerialize(), $this->rewards),
			"identifier" => self::getIdentifier()
		];
	}

	abstract public static function jsonDeserialize(array $data);
}