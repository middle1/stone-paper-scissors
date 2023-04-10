<?php
namespace GamePlugin;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Config;
use pocketmine\Server;

class InteractHandler implements Listener {
    private $plugin;

    public function __construct(Main $plugin, Config $SettingsCfg, Server $server) {
        $this->plugin = $plugin;
        $this->SettingsCfg = $SettingsCfg;
        $this->server = $server;
    }

     public function onInteract(PlayerInteractEvent $event): void
    {
        $FuncInteract = $this->SettingsCfg->getNested("FuncInteractOff");
        $FuncInteractLevel = $this->SettingsCfg->getNested("FuncInteract.Debugging.Level");

        if ($FuncInteract == false) {
            $Block = $this->SettingsCfg->getNested("FuncInteract.Block.id");
            $PosX = $this->SettingsCfg->getNested("FuncInteract.positions.posX");
            $PosY = $this->SettingsCfg->getNested("FuncInteract.positions.posY");
            $PosZ = $this->SettingsCfg->getNested("FuncInteract.positions.posZ");
            $Command = $this->SettingsCfg->getNested("FuncInteract.Command");

            $player = $event->getPlayer();
            $block = $event->getBlock();
            $blockPos = $event->getBlock()->getPosition();
            $x = $blockPos->getX();
            $y = $blockPos->getY();
            $z = $blockPos->getZ();
            $pos = new \pocketmine\math\Vector3($x, $y, $z);
            $blockId = $block->getId();
            $world = $player->getPosition()->getWorld();

            if ($FuncInteractLevel == 1 || $FuncInteractLevel == 2) {
                $player->sendMessage("[&4&bLogger&r] Ваши координаты: X: {$x}, Y: {$y}, Z: {$z}");
                $this->server->getLogger()->info("[ConsoleLoger] Ваши координаты: X: {$x}, Y: {$y}, Z: {$z}");
            }
            if ($FuncInteractLevel == 2) {
                $player->sendMessage("[&4&bLogger&r] id нажатого только что блока {$blockId}");
                $this->server->getLogger()->info("[ConsoleLoger] id нажатого только что блока {$blockId}");
            }
            if ($FuncInteract == false && $blockId == $Block && $x == $PosX && $y == $PosY && $z == $PosZ) {
                $this->server->dispatchCommand($player, $Command);
            }
        }
    }
}

?>