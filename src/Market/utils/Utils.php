<?php

namespace Market\utils;

use pocketmine\item\Item;
use pocketmine\player\Player;

class Utils{

    public static function removeItem(Player $player, Item $itemSelected): bool
    {
        $inventory = $player->getInventory();
        for ($i = 0, $size = $inventory->getSize(); $i < $size; ++$i) {
            $item = $inventory->getItem($i);
            if ($item->isNull())
                continue;

            if ($itemSelected->equals($item)) {
                $amount = min($item->getCount(), $itemSelected->getCount());
                $itemSelected->setCount($itemSelected->getCount() - $amount);
                $item->setCount($item->getCount() - $amount);
                $inventory->setItem($i, $item);
                if ($itemSelected->getCount() <= 0) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function getDataWithId($id, array $markets){
        foreach($markets as $i => $dataMarket){
            if($dataMarket["id"] === $id){
                return $dataMarket;
            }
        }
        return null;
    }
}