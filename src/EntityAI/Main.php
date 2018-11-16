<?php

declare(strict_types=1);

namespace EntityAI;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat as T;

use EntityAI\entity\EntityManager;

class Main extends PluginBase
{
	private $entity = null;

	/** @var EntityManager */
	private $entityManager = null;

	public function onEnable() :void
	{
		$this->entityManager = new EntityManager($this);
		$this->getLogger()->info(T::LIGHT_PURPLE."Entity AI loaded.");
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) :bool
	{
		switch($command->getName()) {
			case "npc":
				if(!isset($args[0])) {
					$entity = $this->entityManager->spawnNPC(
						$sender->getSkin()->getSkinData(),
						$sender->getLocation(),
						$sender->getName()
					);

					if($entity !== null) {
						$this->entity = $entity;
						$sender->sendMessage(T::GREEN."NPC spawned.");
						// $entity->talk("hewwo there big boi uwu");
						// $entity->talk("do u rp? OwO");
					} else {
						$sender->sendMessage(T::RED."There was an error.");
						return false;
					}
				} else {
					switch ($args[0]) {
						case 'pf':
   							$this->entity->beginPathfinding();
							break;
					}
				}
				return true;
			default:
				return false;
		}
	}
}
