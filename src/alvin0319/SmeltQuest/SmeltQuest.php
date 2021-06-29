<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest;

use alvin0319\SmeltQuest\quest\Quest;
use alvin0319\SmeltQuest\quest\QuestManager;
use alvin0319\SmeltQuest\session\QuestSession;
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
use function array_map;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function yaml_emit;
use function yaml_parse;

final class SmeltQuest extends PluginBase implements Listener{
	use SingletonTrait;

	public static string $prefix = "";

	public static BaseLang $lang;

	protected QuestManager $questManager;
	/** @var QuestSession[] */
	protected array $sessions = [];

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
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

		if(!is_dir($dir = $this->getDataFolder() . "sessions/")){
			mkdir($dir, 0777);
		}
	}

	public function onDisable() : void{
		$this->questManager->saveQuests();
		foreach($this->sessions as $name => $session){
			$this->saveSession($session);
		}
		$this->sessions = [];
	}

	public function saveSession(QuestSession $session) : void{
		file_put_contents($this->getDataFolder() . "sessions/{$session->getPlayer()->getName()}.yml", yaml_emit(array_map(fn(Quest $quest) => $quest->getName(), $session->getQuests())));
	}

	public function loadSession(Player $player) : QuestSession{
		if(isset($this->sessions[$player->getName()])){
			return $this->sessions[$player->getName()];
		}
		$data = [];
		if(file_exists($file = $this->getDataFolder() . "sessions/{$player->getName()}.yml")){
			$d = yaml_parse(file_get_contents($file));
			foreach($d as $name){
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