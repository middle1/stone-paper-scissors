<?php

namespace GamePlugin\Config;

use pocketmine\utils\Config;

class PlayerDataConfig{

	private $name;
    private $config;

    public function __construct(string $name, Config $config) {
        $this->config = $config;
        $this->name = $name;
    }

    public function getWins() : int {
        return $this->config->getNested("{$this->name}.Wins");
    }

    public function getDefeats() : int {
        return $this->config->getNested("{$this->name}.Defeat");
    }
    public function getDraws() : int {
        return $this->config->getNested("{$this->name}.Defeat");
    }
}
?>