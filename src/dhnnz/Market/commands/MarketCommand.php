<?php

namespace dhnnz\Market\commands;

use dhnnz\Market\forms\FormManager;
use dhnnz\Market\Loader;
use dhnnz\Market\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

class MarketCommand extends Command implements PluginOwned
{
    public function __construct(protected Loader $plugin)
    {
        $this->setPermission("market.cmd");
        $this->setAliases(["shops", "shop"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$this->testPermission($sender) || !$sender instanceof Player)
            return;
        if (count($args) < 1) {
            $this->getOwningPlugin()->getFormManager()->main($sender);
            return;
        }
        switch ($args[0]) {
            default:
                $id = $args[0];
                if (Utils::getDataWithId($id, $this->getOwningPlugin()->markets) !== null) {
                    $this->getOwningPlugin()->getFormManager()->nextBuy($sender, Utils::getDataWithId($id, $this->getOwningPlugin()->markets));
                }
                break;
        }
    }

    public function getOwningPlugin(): Loader
    {
        return $this->plugin;
    }
}