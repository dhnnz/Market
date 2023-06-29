<?php

namespace dhnnz\Market\utils;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;
use RuntimeException;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

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

    public static function ItemSerialize(Item $item) : string{
		$result = zlib_encode((new BigEndianNbtSerializer())->write(new TreeRoot($item->nbtSerialize())), ZLIB_ENCODING_GZIP);
		if($result === false){
			/** @noinspection PhpUnhandledExceptionInspection */
			throw new RuntimeException("Failed to serialize item " . json_encode($item, JSON_THROW_ON_ERROR));
		}

		return $result;
	}

	public static function ItemDeserialize(string $string) : Item{
		return Item::nbtDeserialize((new BigEndianNbtSerializer())->read(zlib_decode($string))->mustGetCompoundTag());
	}
}