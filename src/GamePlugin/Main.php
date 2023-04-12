<?php

namespace GamePlugin;

require_once __DIR__ . '/PlaySounds/SoundDraw.php';

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use FormAPI\ {
	SimpleForm, CustomForm
};
use pocketmine\player\Player;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use GamePlugin\PlaySounds\ {
	SoundDefeat, SoundWin, SoundDraw
};
use GamePlugin\Config\PlayerDataConfig;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use ReflectionClass;

class Main extends PluginBase implements Listener
{
	private $SettingsCfg;
	public $win;
	public $defeat;
	private $InteractHandler;
	private $PlayerDataConfig;

	public function onEnable() : void
	{
		$pluginManager = $this->getServer()->getPluginManager();
		$EconomyPlugin = $pluginManager->getPlugin("BedrockEconomy");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->saveResource("AllSounds.mcpack", true);

		$manager = $this->getServer()->getResourcePackManager();
		$pack = new ZippedResourcePack($this->getDataFolder() . "AllSounds.mcpack");
		$this->saveResource("AllPlayers.yml");
		$this->saveResource("settings.yml");
		$this->AllPlayers = new Config($this->getDataFolder() . "AllPlayers.yml", Config::YAML);
		$this->SettingsCfg = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

		$reflection = new ReflectionClass($manager);

		$property = $reflection->getProperty("resourcePacks");
		$property->setAccessible(true);

		$currentResourcePacks = $property->getValue($manager);
		$currentResourcePacks[] = $pack;
		$property->setValue($manager, $currentResourcePacks);

		$property = $reflection->getProperty("uuidList");
		$property->setAccessible(true);
		$currentUUIDPacks = $property->getValue($manager);
		$currentUUIDPacks[strtolower($pack->getPackId())] = $pack;
		$property->setValue($manager, $currentUUIDPacks);

		$property = $reflection->getProperty("serverForceResources");
		$property->setAccessible(true);
		$property->setValue($manager, true);

		$this->InteractHandler = new InteractHandler($this, $this->SettingsCfg, $this->getServer());
		$this->getServer()->getPluginManager()->registerEvents($this->InteractHandler, $this);
	}

	public function Math(int $win, int $defeat) : float
	{
		if ($defeat <= 0) {
			return 0;
		}
		$results = $win / $defeat;
		$results = number_format($results, 2, '.', '');
		return $results;
	}

	public function getConfigApi($player, string $Data)
	{
		$name = $player->getName();
		if ($name == null) {
			return "error data";
		}
		$this->PlayerDataConfig = new PlayerDataConfig($name, $this->AllPlayers);

		if (!$this->AllPlayers->exists($name)) {
			return "Этот игрок ещё не разу не играл в \"камень, ножницы, бумага\"";
		}

		switch ($Data) {
			case 'Wins':
				return $this->PlayerDataConfig->getWins();
				break;

			case 'Defeats':
				return $this->PlayerDataConfig->getDefeats();
				break;

			case 'Draws':
				return $this->PlayerDataConfig->getDraws();
				break;

			default:
				return "error";
				break;
		}
	}

	public function onCommand(CommandSender $player, Command $cmd, string $label, array $args) : bool
	{
		if ($cmd->getName() == "game") {
			if (!$player instanceof Player) {
				$this->getServer()->getLogger->info("This command can only be executed by a player.");
				return true;
			}
			$maxMoney = $this->SettingsCfg->getNested("MaxAmountMoney");
			$minMoney = $this->SettingsCfg->getNested("MinAmountMoney");
			$name = $player->getName();
			$name = strtolower($name);
			$this->AllPlayers->reload();
			$AllPlayersCfg = $this->getConfig()->getAll();
			if ($this->AllPlayers->get($name, null) === null) {
				$this->AllPlayers->set(
					$name,
					array(
						"Wins" => 0,
						"Defeat" => 0,
						"Draws" => 0
					)
				);
				$this->AllPlayers->save();
			}

			$form = new CustomForm(
				function (Player $player, ? array $dataInput) {
					$maxMoney = $this->SettingsCfg->getNested("MaxAmountMoney");
					$minMoney = $this->SettingsCfg->getNested("MinAmountMoney");
					$stone = "1";
					$paper = "2";
					$scissors = "3";
					$genid = rand($stone, $scissors);

					$result = $dataInput;

					if ($result === null) {
						return true;
					}
					if ($dataInput[0] > $maxMoney || $dataInput[0] <= $minMoney) {
						$player->sendMessage("Ставка должна быть от {$minMoney} до {$maxMoney}, ваша ставка -> " . $dataInput[0]);
						return true;
					}
					$this->Form($player, $dataInput, $genid);
				}
			);
			$form->addInput("Ставка", "Введите ставку до $maxMoney");
			$form->sendToPlayer($player);
		}
		return true;
	}

	public function Form($player, $dataInput, $genid)
	{
		$this->dataInput = $dataInput;
		$this->genid = $genid;
		$name = $player->getName();

		BedrockEconomyAPI::legacy()->getPlayerBalance(
			$name,
			ClosureContext::create(
				function (int $balance) use ($dataInput, $player) {
					if ($balance < $dataInput[0]) {
						$player->sendMessage("Не хватает деняг, ваш баланс $balance меньше чем ваш ввод $dataInput[0]");
						return true;
					}

					$form = new SimpleForm(
						function (Player $player, ?int $data) {
							$name = $player->getName();
							$name = strtolower($name);
							$result = $data;
							if ($result === null) {
								return true;
							}
							switch ($result) {
								case 0:
									if ($this->genid == 1) {
										$DrawSound = new SoundDraw();
										$player->sendTitle("Ничья");
										$DrawSound->PlaySoundDraw($player);
										$this->AllPlayers->setNested("{$name}.Draws", $this->AllPlayers->getNested("{$name}.Draws") + 1);
										$this->AllPlayers->save();
										return true;
									}
									if ($this->genid == 2) {
										$WinSound = new SoundWin();
										$player->sendTitle("Победа за вами!");
										$this->getServer()->dispatchCommand(
											new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()),
											'addbalance ' . $name . ' ' . $this->dataInput[0]
										);
										$WinSound->PlaySoundWin($player);
										$this->AllPlayers->setNested("{$name}.Wins", $this->AllPlayers->getNested("{$name}.Wins") + 1);
										$this->AllPlayers->save();
										return true;
									}
									if ($this->genid == 3) {
										$DefeatSound = new SoundDefeat();
										$player->sendTitle("Поражение");
										$this->getServer()->dispatchCommand(
											new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()),
											'removebalance ' . $name . ' ' . $this->dataInput[0]
										);
										$DefeatSound->PlaySoundDefeat($player);
										$this->AllPlayers->setNested("{$name}.Defeat", $this->AllPlayers->getNested("{$name}.Defeat") + 1);
										$this->AllPlayers->save();
										return true;
									}
									break;

								case 1:
									if ($this->genid == 1) {
										$DefeatSound = new SoundDefeat();
										$player->sendTitle("Поражение");
										$this->getServer()->dispatchCommand(
											new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()),
											'removebalance ' . $name . ' ' . $this->dataInput[0]
										);
										$DefeatSound->PlaySoundDefeat($player);
										$this->AllPlayers->setNested("{$name}.Defeat", $this->AllPlayers->getNested("{$name}.Defeat") + 1);
										$this->AllPlayers->save();
										return true;
									}
									if ($this->genid == 2) {
										$DrawSound = new SoundDraw();
										$player->sendTitle("Ничья");
										$DrawSound->PlaySoundDraw($player);
										$this->AllPlayers->setNested("{$name}.Draws", $this->AllPlayers->getNested("{$name}.Draws") + 1);
										$this->AllPlayers->save();
										return true;
									}
									if ($this->genid == 3) {
										$WinSound = new SoundWin();
										$player->sendTitle("Победа за вами");
										$this->getServer()->dispatchCommand(
											new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()),
											'addbalance ' . $name . ' ' . $this->dataInput[0]
										);
										$WinSound->PlaySoundWin($player);
										$this->AllPlayers->setNested("{$name}.Wins", $this->AllPlayers->getNested("{$name}.Wins") + 1);
										$this->AllPlayers->save();
										return true;
									}
									break;

								case 2:
									if ($this->genid == 1) {
										$WinSound = new SoundWin();
										$player->sendTitle("Победа за вами");
										$this->getServer()->dispatchCommand(
											new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()),
											'addbalance ' . $name . ' ' . $this->dataInput[0]
										);
										$WinSound->PlaySoundWin($player);
										$this->AllPlayers->setNested("{$name}.Wins", $this->AllPlayers->getNested("{$name}.Wins") + 1);
										$this->AllPlayers->save();
										return true;
									}
									if ($this->genid == 2) {
										$DefeatSound = new SoundDefeat();
										$player->sendTitle("Поражение");
										$this->getServer()->dispatchCommand(
											new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()),
											'removebalance ' . $name . ' ' . $this->dataInput[0]
										);
										$DefeatSound->PlaySoundDefeat($player);
										$this->AllPlayers->setNested("{$name}.Defeat", $this->AllPlayers->getNested("{$name}.Defeat") + 1);
										$this->AllPlayers->save();
										return true;
									}
									if ($this->genid == 3) {
										$DrawSound = new SoundDraw();
										$player->sendTitle("Ничья");
										$DrawSound->PlaySoundDraw($player);
										$this->AllPlayers->setNested("{$name}.Draws", $this->AllPlayers->getNested("{$name}.Draws") + 1);
										$this->AllPlayers->save();
										return true;
									}
									break;
							}
						}
					);

					$defeats = $this->getConfigApi($player, "Defeats");
					$wins = $this->getConfigApi($player, "Wins");

					$form->setTitle("Ваш выбор");
					$form->addButton("Камень", 0, "textures/blocks/stone");
					$form->addButton("Ножници", 0, "textures/items/shears");
					$form->addButton("Бумага", 0, "textures/items/paper");
					$form->setContent(
						"Пораженний -> " . $defeats . "\nПобеды -> " . $wins . "\nВаш D/W -> " . $this->Math($wins, $defeats)
					);
					$form->sendToPlayer($player);
				},
			)
		);
	}
}
