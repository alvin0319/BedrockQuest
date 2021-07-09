<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\Player;
use function array_merge;

abstract class BlockQuest extends Quest{
	use CountableTrait;

	protected Block $block;

	protected int $count;

	protected bool $allowAllBlocks = false;

	public function __construct(
		string $name,
		string $description,
		int $clearType,
		array $playingPlayers,
		array $completedPlayers,
		array $records,
		int $rewardMoney,
		array $rewards,
		int $blockId,
		int $blockMeta,
		int $count,
		array $queue,
		array $executeCommands = [],
		bool $allowAllBlocks = false,
		array $additionalRewardMessages = []
	){
		parent::__construct($name, $description, $clearType, $playingPlayers, $completedPlayers, $records, $rewardMoney, $rewards, $executeCommands, $additionalRewardMessages);
		$this->block = BlockFactory::get($blockId, $blockMeta);
		$this->count = $count;
		$this->queue = $queue;
		$this->allowAllBlocks = $allowAllBlocks;
	}

	public function getBlock() : Block{
		return clone $this->block;
	}

	public function canComplete(Player $player) : bool{
		return $this->getQueue($player) >= $this->count;
	}

	public function getAllowAllBlocks() : bool{ return $this->allowAllBlocks; }

	public function getProgress(Player $player) : float{
		$queue = $this->getQueue($player);
		if($queue === 0){
			return 0;
		}
		return (float) ($queue / $this->count) * 100;
	}

	/**
	 * Returns the needed block count that player should place/break block
	 * @return int
	 */
	public function getCount() : int{
		return $this->count;
	}

	public function jsonSerialize() : array{
		return array_merge(
			parent::jsonSerialize(),
			["blockId" => $this->block->getId(), "blockMeta" => $this->block->getDamage(), "count" => $this->count, "blockQueue" => $this->queue, "allowAllBlocks" => $this->allowAllBlocks]
		);
	}
}