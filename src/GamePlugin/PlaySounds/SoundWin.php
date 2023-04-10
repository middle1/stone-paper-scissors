<?php
namespace GamePlugin\PlaySounds;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class SoundWin {

	public function PlaySoundWin($player): void {
		$pos = $player->getPosition();
		$packet = new PlaySoundPacket();
		$packet->soundName = "Win";
		$packet->x = $pos->getX();
		$packet->y = $pos->getY();
		$packet->z = $pos->getZ();
		$packet->volume = 1;
		$packet->pitch = 1;
		$player->getNetworkSession()->sendDataPacket($packet);
	}
}
?>