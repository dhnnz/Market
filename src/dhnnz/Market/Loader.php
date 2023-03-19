<?php

namespace dhnnz\Market;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\version\LegacyBEAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use dhnnz\Market\commands\MarketCommand;
use dhnnz\Market\economy\Economy;
use dhnnz\Market\economy\types\BedrockEconomy;
use dhnnz\Market\economy\types\EconomyAPI as TypesEconomyAPI;
use dhnnz\Market\forms\FormManager;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class Loader extends PluginBase
{
    use SingletonTrait;

    const STATUS_ENOUGH = 0;
    const STATUS_SUCCESS = 1;

    public array $markets = [];
    public array $historys = [];
    public Economy $economy;

    public function onEnable(): void
    {
        $this->setInstance($this);
        $type = $this->getEconomyType();
        $this->registerEconomy($type);
        $this->markets = (new Config($this->getDataFolder() . "markets.json", Config::JSON, []))->getAll();
        $this->historys = (new Config($this->getDataFolder() . "historys.json", Config::JSON, []))->getAll();

        $this->getServer()->getCommandMap()->register("Markets", new MarketCommand($this), "market");
    }

    public function onDisable(): void
    {
        ($config = new Config($this->getDataFolder() . "markets.json", Config::JSON, []))->setAll($this->markets);
        $config->save();
    }

    public function registerMarket(Player $seller, array $data)
    {
        array_push($this->markets, $data);
        return;
    }

    public function registerEconomy(string $name = "EconomyAPI")
    {
        match ($name) {
            "BedrockEconomy" => $economy = new BedrockEconomy(),
            "EconomyAPI" => $economy = new TypesEconomyAPI(),
        };
        $this->economy = $economy;
    }

    public function getFormManager(): FormManager
    {
        return new FormManager($this);
    }

    public function getEconomyType()
    {
        $economy = null;
        $plugin = $this->getServer()->getPluginManager();
        if ($plugin->getPlugin("BedrockEconomy") !== null)
            $economy = "BedrockEconomy";
        if ($plugin->getPlugin("EconomyAPI") !== null)
            $economy = "EconomyAPI";
        return $economy;
    }

    public function getEconomy(): Economy
    {
        return $this->economy;
    }
}