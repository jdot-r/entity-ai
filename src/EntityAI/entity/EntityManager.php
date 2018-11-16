<?php

namespace EntityAI\entity;

use pocketmine\entity\Human;
use pocketmine\nbt\tag\{ByteArrayTag, CompoundTag, StringTag};
use pocketmine\utils\TextFormat as T;
use pocketmine\math\Vector3;

use EntityAI\Main;

class EntityManager
{
	const CHAT_FORMAT = T::DARK_GRAY.'%s [NPC] > '.T::GOLD.'%s';

	private $plugin;

	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
	}

	public function spawnNPC($skinData, $location, string $name) :?SmartEntity
	{
		$nbt = Human::createBaseNBT($location->asPosition());
		$nbt->setTag(
			new CompoundTag("Skin", [
				new ByteArrayTag("Data", $skinData),
				new StringTag("Name", $name)
			])
		);

		$nbt->setName($name);

		$entity = new SmartEntity($location->getLevel(), $nbt);
		$entity->setPosition($location->asPosition());
		$entity->setRotation($location->getYaw(), $location->getPitch());
		$entity->setNameTag($name.' [NPC]');
		$entity->setCanSaveWithChunk(false);
		$entity->spawnToAll();
		return $entity;
	}
}