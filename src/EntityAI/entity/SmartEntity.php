<?php

namespace EntityAI\entity;

use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\entity\Human;
use pocketmine\entity\Attribute;
use pocketmine\math\Vector3;
use pocketmine\block\Block;

class SmartEntity extends Human
{
	private $isPathfinding = false;
	private $startPoint, $currentNode = [0, 0];
	private $endPoint = [10, 10];
	private $openList = [];

	public function onUpdate(int $currentTick) : bool{
		if($this->closed){
			return false;
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		if($tickDiff <= 0){
			if(!$this->justCreated){
				$this->server->getLogger()->debug("Expected tick difference of at least 1, got $tickDiff for " . get_class($this));
			}
			return true;
		}
		$this->lastUpdate = $currentTick;
		if(!$this->isAlive()){
			if($this->onDeathUpdate($tickDiff)){
				$this->flagForDespawn();
			}
			return true;
		}
		$this->timings->startTiming();
		if($this->hasMovementUpdate()){
			$this->tryChangeMovement();
			if(abs($this->motion->x) <= self::MOTION_THRESHOLD){
				$this->motion->x = 0;
			}
			if(abs($this->motion->y) <= self::MOTION_THRESHOLD){
				$this->motion->y = 0;
			}
			if(abs($this->motion->z) <= self::MOTION_THRESHOLD){
				$this->motion->z = 0;
			}
			if($this->motion->x != 0 or $this->motion->y != 0 or $this->motion->z != 0){
				$this->move($this->motion->x, $this->motion->y, $this->motion->z);
				echo "Entity moved\n";
				if($this->isPathfinding) $this->findNextNode();
			}
			$this->forceMovementUpdate = false;
		}
		$this->updateMovement();
		Timings::$timerEntityBaseTick->startTiming();
		$hasUpdate = $this->entityBaseTick($tickDiff);
		Timings::$timerEntityBaseTick->stopTiming();
		$this->timings->stopTiming();
		return ($hasUpdate or $this->hasMovementUpdate());
	}

	public function findNextNode()
	{
		$x = $this->currentNode[0];
		$z = $this->currentNode[1];
        $this->openList[] = [$x + 1, $z];
        $this->openList[] = [$x + 1, $z + 1];
        $this->openList[] = [$x, $z + 1];
        $this->openList[] = [$x - 1, $z + 1];
        $this->openList[] = [$x - 1, $z];
        $this->openList[] = [$x - 1, $z - 1];
        $this->openList[] = [$x, $z - 1];
        $this->openList[] = [$x + 1, $z - 1];
        for($i=0; $i < 8; $i++) {
            $entry = $this->openList[$i];
            $xDist = abs($entry[0] - $this->endPoint[0]);
            $zDist = abs($entry[1] - $this->endPoint[1]);
            $d = 10;
            $h = $d * ($xDist + $zDist);
            $this->openList[$i]["h"] = $h;
        }
        $lowest = 0;
        for($i=0; $i < 8; $i++) {
            $nextEntry = ($i === 7) ? $this->openList[7] : $this->openList[$i + 1];
            $h = $this->openList[$i]["h"];
            $nextH = $nextEntry["h"];
            if($h < $nextH) {
                $lowest = $i;
                printf("New lowest = %d\n", $h);
                $x = $this->openList[$lowest][0];
                $z = $this->openList[$lowest][1];
                $_x = $x - $this->currentNode[0];
                $_z = $z - $this->currentNode[1];
                $this->currentNode = [$x, $z];

                $this->move($_x, 0, $_z);
                $this->getLevel()->setBlock(new Vector3($this->getX(), $this->getY()-1, $this->getZ()), Block::get(7));
                break 1;
            }
        }
        $this->openList = [];
        if(empty(array_diff($this->currentNode, $this->endPoint))) {
			echo "Goal reached.\n";
			$this->isPathfinding = false;
        }
	}

	public function beginPathfinding()
	{
		$this->isPathfinding = true;
		$attr = $this->attributeMap->getAttribute(Attribute::MOVEMENT_SPEED);
		$attr->setValue($attr->getValue() * 1.3, false, true);
		$this->findNextNode();
	}

	// this will be handled by an NPCChatEvent later
	public function talk(string $message='')
	{
		foreach(Server::getInstance()->getOnlinePlayers() as $p) {
			$p->sendMessage(sprintf(EntityManager::CHAT_FORMAT, $this->getName(), $message));
		}
	}

	//debug
	public function isNPC() :bool{ return true; }
}