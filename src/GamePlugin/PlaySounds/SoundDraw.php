<?php

namespace GamePlugin\PlaySounds;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class SoundDraw {

	public function PlaySoundDraw($player): void {
		$pos = $player->getPosition();
	    $packet = new PlaySoundPacket();
	    $packet->soundName = "Popadanyi";
	    $packet->x = $pos->getX();
	    $packet->y = $pos->getY();
	    $packet->z = $pos->getZ();
	    $packet->volume = 0.5;
	    $packet->pitch = 1;
	    $player->getNetworkSession()->sendDataPacket($packet);
	}
}
?>
