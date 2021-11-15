<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\quest;

use alvin0319\BedrockQuest\event\QuestClearEvent;
use alvin0319\BedrockQuest\BedrockQuest;
use alvin0319\BedrockQuest\util\TimeUtil;
use JsonSerializable;
use onebone\economyapi\EconomyAPI;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use function array_map;
use function array_pop;
use function array_search;
use function count;
use function implode;
use function microtime;
use function round;
use function str_replace;

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

	protected array $executeCommands = [];
	/** @var string[] */
	protected array $additionalRewardMessage = [];

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
		array $additionalRewardMessage = []
	){
		$this->name = $name;
		$this->description = $description;
		$this->clearType = $clearType;
		$this->playingPlayers = $playingPlayers;
		$this->completedPlayers = $completedPlayers;
		$this->records = $records;
		$this->rewardMoney = $rewardMoney;
		$this->rewards = array_map(fn(array $data) => Item::jsonDeserialize($data), $rewards);
		$this->executeCommands = $executeCommands;
		$this->additionalRewardMessage = $additionalRewardMessage;
	}

	public function getName() : string{ return $this->name; }

	public function getDescription() : string{ return $this->description; }

	public function getPlayingPlayers() : array{ return $this->playingPlayers; }

	public function getCompletedPlayers() : array{ return $this->completedPlayers; }

	public function getRewardMoney() : int{ return $this->rewardMoney; }

	public function getExecuteCommands() : array{ return $this->executeCommands; }

	public function addExecuteCommand(string $command, string $consoleOrPlayer) : void{
		$this->executeCommands[$command] = $consoleOrPlayer;
	}

	public function removeExecuteCommand(string $command) : void{
		$key = array_search($command, $this->executeCommands);
		if($key !== false){
			unset($this->executeCommands[$key]);
		}
	}

	public function getAdditionalRewardMessages() : string{
		return implode("\n", $this->additionalRewardMessage);
	}

	public function setAdditionalRewardMessage(string $additionalRewardMessage) : void{
		$this->additionalRewardMessage[] = $additionalRewardMessage;
	}

	/** @return Item[] */
	public function getRewards() : array{ return $this->rewards; }

	abstract public function getGoal() : string;

	public static function getIdentifier() : string{
		return "";
	}

	public function setRewards(array $rewards) : void{
		(function(Item ...$item) : void{ })(...$rewards);
		$this->rewards = $rewards;
	}

	public function setRewardMoney(int $rewardMoney) : void{ $this->rewardMoney = $rewardMoney; }

	public function getRecords() : array{ return $this->records; }

	public function getProgress(Player $player) : float{
		return 0.0;
	}

	public function onProgressAdded(Player $player) : void{
		$player->sendPopup("{$this->getName()}: " . round($this->getProgress($player), 2) . "%%");
	}

	public function onPlayerRemoved(Player $player) : void{
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
		$player->sendMessage(BedrockQuest::$prefix . BedrockQuest::$lang->translateString("quest.message.started", [$this->getName()]));
		$player->sendMessage(BedrockQuest::$prefix . BedrockQuest::$lang->translateString("quest.message.started.goal", [$this->getGoal()]));
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

		$this->completedPlayers[$player->getLowerCaseName()] = microtime(true);

		$timeTook = microtime(true) - $this->playingPlayers[$player->getLowerCaseName()];

		unset($this->playingPlayers[$player->getLowerCaseName()]);

		$player->getInventory()?->addItem(...$ev->getRewards());

		EconomyAPI::getInstance()->addMoney($player, $ev->getRewardMoney());

		foreach($this->executeCommands as $command => $consoleOrPlayer){
			if($consoleOrPlayer === "console"){
				$player->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("@player", $player->getName(), $command));
			}else{
				$player->getServer()->dispatchCommand($player, str_replace("@player", $player->getName(), $command));
			}
		}

		$player->sendMessage(BedrockQuest::$prefix . BedrockQuest::$lang->translateString("quest.message.completed", [$this->getName()]));

		$rewards = [];
		if($ev->getRewards() > 0){
			$rewards[] = implode(", ", array_map(fn(Item $item) => $item->getName() . " x" . $item->getCount(), $ev->getRewards()));
		}
		if($ev->getRewardMoney() > 0){
			$rewards[] = "$" . $ev->getRewardMoney();
		}
		if($ev->getQuest()->getAdditionalRewardMessages() !== ""){
			$rewards[] = $ev->getQuest()->getAdditionalRewardMessages();
		}

		$last = null;
		if(count($rewards) > 1){
			$last = array_pop($rewards);
		}
		$message = implode(",", $rewards);
		if($last !== null){
			$message .= " and " . $last;
		}

		$player->sendMessage(BedrockQuest::$prefix . BedrockQuest::$lang->translateString("quest.message.completed.reward", [$message]));

		$this->onPlayerRemoved($player);

		if(isset($this->records[$player->getLowerCaseName()])){
			if($timeTook <= $this->records[$player->getLowerCaseName()]){
				return;
			}
		}

		$previousRecord = $this->records[$player->getName()] ?? 0;

		$previousTimestamp = TimeUtil::convertTime((int) $previousRecord);

		$timeTookTimestamp = TimeUtil::convertTime((int) $timeTook);

		$this->records[$player->getName()] = $timeTook;

		$player->sendMessage(BedrockQuest::$prefix . BedrockQuest::$lang->translateString("quest.message.completed.newrecord", [
				BedrockQuest::$lang->translateString("time.format", [
					$previousTimestamp[1],
					$previousTimestamp[2],
					$previousTimestamp[3]
				]),
				BedrockQuest::$lang->translateString("time.format", [
					$timeTookTimestamp[1],
					$timeTookTimestamp[2],
					$timeTookTimestamp[3]
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
			"identifier" => static::getIdentifier(),
			"executeCommands" => $this->executeCommands,
			"additionalRewardMessage" => $this->additionalRewardMessage
		];
	}

	abstract public static function jsonDeserialize(array $data);
}