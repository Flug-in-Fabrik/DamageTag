<?php

namespace SpaceLuis;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\entity\Entity;
use pocketmine\utils\UUID;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;

class Main extends PluginBase implements Listener {
	
	private $eid = [ ];
	
	private static $instance = null;
	
	public function onLoad() {
		if (self::$instance == null) {
			self::$instance = $this;
		}
	}
	
	public static function getInstance() {
		return static::$instance;
	}
	public function onEnable() {
		$this->getServer ()->getPluginManager ()->registerEvents ($this, $this);
	}
	
	public function onEntityAttack(EntityDamageEvent $event) {
		if (! $event->isCancelled ())
			if ($event instanceof EntityDamageByEntityEvent) {
				$damager = $event->getDamager ();
				if ($damager instanceof Player) {
					$this->AddTag($event->getEntity (), $event->getBaseDamage());
				}
			}
	}
	
	public function AddTag(Position $pos, $damage, $critical = true) {
		$pk = new AddPlayerPacket ();
		$id = Entity::$entityCount++;
		$pk->entityRuntimeId = $id;
		$pk->entityUniqueId = $id;
		$this->eid [$id] = true;
		$pk->position = $pos->add(0,1,0);
		$uuid = UUID::fromRandom();
		$pk->uuid = $uuid;
		$pk->item = Item::get(0,0,0);
		if ($critical) {
			$data = "§b{$damage}";
		} else {
			$data = "§c{$damage}";
		}
		$pk->username = $data;
		$flags = (1 << Entity::DATA_FLAG_IMMOBILE);
		
		$pk->metadata = [
				Entity::DATA_FLAGS => [
						Entity::DATA_TYPE_LONG,
						$flags
				],
				Entity::DATA_SCALE => [
						Entity::DATA_TYPE_FLOAT,
						0.01
				]
		];
		
		foreach ($this->getServer ()->getOnlinePlayers() as $players){
			$players->dataPacket ($pk);
		}
		
		$this->getScheduler ()->scheduleDelayedTask (new RemoveDamageTagTask ($this, $id), 20);
	}
	public function removeTag($eid) {
		if (isset($this->eid [$eid])) {
			$pk = new RemoveActorPacket ();
			$pk->entityUniqueId = $eid;
			foreach($this->getServer ()->getOnlinePlayers() as $players){
				$players->dataPacket($pk);
			}
			unset($this->eid[$eid]);
		}
	}
}
class RemoveDamageTagTask extends Task{
    
	private $plugin, $eid;
	public function __construct(Main $plugin, $eid) {
		$this->plugin = $plugin;
		$this->eid = $eid;
	}
	public function onRun(int $currentTick) {
		$this->plugin->removeTag($this->eid);
	}
}
