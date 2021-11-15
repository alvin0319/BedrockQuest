<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest;

use alvin0319\BedrockQuest\command\QuestCommand;
use alvin0319\BedrockQuest\quest\Quest;
use alvin0319\BedrockQuest\quest\QuestManager;
use alvin0319\BedrockQuest\session\QuestSession;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\lang\BaseLang;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use function array_keys;
use function array_search;
use function array_values;
use function class_exists;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function yaml_emit;
use function yaml_parse;

final class BedrockQuest extends PluginBase implements Listener{
	use SingletonTrait;

	public static string $prefix = "";

	public static BaseLang $lang;

	protected QuestManager $questManager;
	/** @var QuestSession[] */
	protected array $sessions = [];

	/** @var string[][] */
	protected array $categories = [];

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
		if(!class_exists(InvMenu::class)){
			$this->getLogger()->info("InvMenu not found");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->questManager = new QuestManager($this);
		$this->saveDefaultConfig();
		$lang = $this->getConfig()->get("lang", "eng");
		$this->saveResource($file = $lang . ".ini");
		if(!file_exists($this->getDataFolder() . $file)){
			$this->getLogger()->critical("Invalid language $lang");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		self::$lang = new BaseLang($lang, $this->getDataFolder());
		self::$prefix = self::$lang->get("language.prefix");

		$this->getLogger()->info("Selected $lang as base language");

		if(!is_dir($dir = $this->getDataFolder() . "sessions/") && !mkdir($dir) && !is_dir($dir)){
			throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
		}

		if(file_exists($file = $this->getDataFolder() . "categories.yml")){
			$this->categories = yaml_parse(file_get_contents($file));
		}

		$this->getServer()->getCommandMap()->register("smeltcommand", new QuestCommand());
	}

	public function onDisable() : void{
		$this->questManager->saveQuests();
		foreach($this->sessions as $name => $session){
			$this->saveSession($session);
		}
		$this->sessions = [];
		file_put_contents($this->getDataFolder() . "categories.yml", yaml_emit($this->categories));
	}

	public function getQuestManager() : QuestManager{
		return $this->questManager;
	}

	public function createCategory(string $name) : void{
		$this->categories[$name] = [];
	}

	public function addQuestToCategory(string $name, Quest $quest) : void{
		$this->categories[$name][] = $quest->getName();
	}

	public function removeQuestFromCategory(string $name, Quest $quest) : void{
		unset($this->categories[$name][array_search($quest->getName(), $this->categories[$name])]);
		$this->categories[$name] = array_values($this->categories[$name]);
	}

	public function getCategoryFromQuest(Quest $quest) : ?string{
		foreach($this->categories as $name => $quests){
			foreach($quests as $q){
				if($q === $quest->getName()){
					return $name;
				}
			}
		}
		return null;
	}

	public function getCategories() : array{ return array_keys($this->categories); }

	/**
	 * @param string $name
	 *
	 * @return Quest[]
	 */
	public function getCategory(string $name) : ?array{
		if(!isset($this->categories[$name])){
			return null;
		}
		$arr = [];
		foreach($this->categories[$name] as $questName){
			$quest = $this->questManager->getQuest($questName);
			if($quest !== null){
				$arr[] = $quest;
			}
		}
		return $arr;
	}

	public function saveSession(QuestSession $session) : void{
		$quests = [];
		foreach($session->getQuests() as $quest){
			$quests[$quest->getName()] = $quest->getName();
		}
		file_put_contents($this->getDataFolder() . "sessions/{$session->getPlayer()->getName()}.yml", yaml_emit($quests));
	}

	public function loadSession(Player $player) : QuestSession{
		if(isset($this->sessions[$player->getName()])){
			return $this->sessions[$player->getName()];
		}
		$data = [];
		if(file_exists($file = $this->getDataFolder() . "sessions/{$player->getName()}.yml")){
			$d = yaml_parse(file_get_contents($file));
			foreach($d as $name => $name_){
				$quest = $this->questManager->getQuest($name);
				if($quest !== null){
					$data[$quest->getName()] = $quest;
				}
			}
		}
		return $this->sessions[$player->getName()] = new QuestSession($player, $data);
	}

	public function getSession(Player $player) : QuestSession{
		return $this->sessions[$player->getName()] ?? $this->loadSession($player);
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();

		$this->loadSession($player);
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$this->saveSession($this->getSession($player));
	}

	/**
	 * @param BlockBreakEvent $event
	 *
	 * @ignoreCancelled
	 * @priority MONITOR
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$this->getSession($event->getPlayer())->onBlockBreak($event);
	}

	/**
	 * @param BlockPlaceEvent $event
	 *
	 * @ignoreCancelled
	 * @priority MONITOR
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$this->getSession($event->getPlayer())->onBlockPlace($event);
	}

	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$player = $event->getPlayer();
		$cause = $player->getLastDamageCause();
		if(!$cause instanceof EntityDamageByEntityEvent){
			return;
		}
		$damager = $cause->getDamager();
		if(!$damager instanceof Player){
			return;
		}
		$this->getSession($damager)->onPlayerKilled($event);
	}

	/**
	 * @param EntityDeathEvent $event
	 *
	 * @ignoreCancelled
	 * @priority MONITOR
	 */
	public function onEntityDeath(EntityDeathEvent $event) : void{
		$cause = $event->getEntity()->getLastDamageCause();
		if(!$cause instanceof EntityDamageByEntityEvent){
			return;
		}
		$damager = $cause->getDamager();
		if(!$damager instanceof Player){
			return;
		}
		$this->getSession($damager)->onEntityKilled($event);
	}

	/**
	 * @param PlayerCommandPreprocessEvent $event
	 *
	 * @ignoreCancelled
	 * @priority MONITOR
	 */
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
		$this->getSession($event->getPlayer())->onCommandInvoke($event);
	}
}