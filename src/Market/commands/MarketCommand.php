<?php

namespace Market\commands;

use Market\forms\FormManager;
use Market\Loader;
use Market\utils\Utils;
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
        if(count($args) < 1)
            $this->plugin->getFormManager()->main($sender);
        switch($args[0]){
            default:
              $id = $args[0];
              if(Utils::getDataWithId($id, $this->plugin->markets) !== null){
                $this->plugin->getFormManager()->nextBuy($sender, Utils::getDataWithId($id, $this->plugin->markets));
              }
              break;
        }
    }
}
