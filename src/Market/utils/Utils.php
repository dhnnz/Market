<?php

namespace Market\utils;

use pocketmine\item\Item;
use pocketmine\player\Player;

class Utils{

    public static function removeItem(Player $player, Item $slot): bool
    {
        $inventory = $player->getInventory();
        for ($i = 0, $size = $inventory->getSize(); $i < $size; ++$i) {
            $item = $inventory->getItem($i);
            if ($item->isNull())
                continue;

            if ($slot->equals($item)) {
                $amount = min($item->getCount(), $slot->getCount());
                $slot->setCount($slot->getCount() - $amount);
                $item->setCount($item->getCount() - $amount);
                $inventory->setItem($i, $item);
                if ($slot->getCount() <= 0) {
                    return true;
                }
            }
        }
        return false;
    }
}