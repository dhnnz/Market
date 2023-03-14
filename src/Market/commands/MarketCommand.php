<?php

namespace Market\commands;

use Market\forms\FormManager;
use Market\Loader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class MarketCommand extends Command{
    public function __construct(protected Loader $plugin){
        $this->setAliases(["shops", "shop"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        $this->plugin->getFormManager()->main($sender);
    }
}
