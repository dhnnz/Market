<?php

namespace Market\forms;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use Market\Loader;
use Market\utils\Utils;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\Server;

class FormManager
{

    public function __construct(protected Loader $plugin)
    {
    }

    public function main(Player $p)
    {
        $form = new SimpleForm(function (Player $p, $data = null) {
            if ($data === null)
                return;

            match ($data) {
                0 => $this->buyMenu($p),
                1 => $this->sellMenu($p)
            };
        });
        $form->setTitle("Market");
        $form->addButton("Buy");
        $form->addButton("Sell");
        $form->sendToPlayer($p);
        return $form;
    }

    public function buyMenu(Player $p)
    {
        $form = new SimpleForm(function (Player $p, $data = null) {
            if ($data === null)
                return;
            match ($data) {
                0 => $this->marketListing($p),
                1 => $this->main($p)
            };
        });
        $form->setTitle("Buy");
        $form->addButton("Market listing");
        $form->addButton("Back");
        $form->sendToPlayer($p);
        return $form;
    }

    public function marketListing(Player $p, $page = 1)
    {
        $markets = array();
        foreach ($this->plugin->markets as $sellers => $market) {
            if ($market["state"] > 0) {
                array_push($markets, $market);
            }
        }
        $form = new SimpleForm(function (Player $p, $data = null) use ($page, $markets) {
            if ($data === null)
                return;

            if ($data == "close") {}
            if ($data == "next") {
                $this->marketListing($p, $page + 1);
            }
            if ($data == "prev") {
                if ($page > 1) {
                    $this->marketListing($p, $page - 1);
                } else {
                    $this->marketListing($p);
                }
            }
            if ($data !== "next" and $data !== "prev" and $data !== "close") {
                $this->nextBuy($p, $markets[$data]);
            }
        });
        if(count($markets) < 1) $form->setContent("There are no listings available at this time.");
        $form->addButton("Close", -1, "textures/blocks/barrier", label:"close");
        $form->setTitle("Market Listings - Page $page");
        $total_pages = ceil(count($markets) / 5);
        $start = ($page - 1) * 5;
        $end = min(($start + 5), count($markets));
        for ($i = $start; $i < $end; $i++) {
            $item = $p->getInventory()->getItemInHand()->jsonDeserialize($markets[$i]["itemJson"]);
            $form->addButton("§f" . $item->getName() . ": " . $item->getCount() . "\n§fPrice: §5" . number_format((float) $markets[$i]["price"]) . "§f Sell by " . $markets[$i]["seller"], label: $i);
        }
        if ($page < $total_pages) {
            $form->addButton("Next", label: "next");
        }
        if ($page > 1) {
            $form->addButton("Prev", label: "prev");
        }
        $form->sendToPlayer($p);
        return $form;
    }

    public function myListings(Player $p)
    {
        $form = new SimpleForm(function (Player $p, $data = null) {
            if ($data === null)
                return;
            $this->selectListing($p, Utils::getDataWithId($data, $this->plugin->markets));
        });
        $form->setTitle("Sell");
        foreach ($this->plugin->markets as $sellers => $market) {
            if ($market["seller"] == $p->getName()) {
                $item = $p->getInventory()->getItemInHand()->jsonDeserialize($market["itemJson"]);
                $form->addButton("§f" . $item->getName() . ": " . $item->getCount() . "\n§fPrice: §5" . number_format((float) $market["price"]) . "§f Sell by " . $market["seller"], label: $market["id"]);
            }
        }
        $form->sendToPlayer($p);
        return $form;
    }

    public function selectListing(Player $p, array $dataMarket)
    {
        $item = $p->getInventory()->getItemInHand()->jsonDeserialize($dataMarket["itemJson"]);
        $form = new SimpleForm(function (Player $p, $data = null) use ($dataMarket) {
            if ($data === null)
                return;
            switch ($data) {
                case "remove":
                    $p->sendMessage("§aMarket > §fListing successfully removed.");
                    unset($this->plugin->markets[array_search($dataMarket, $this->plugin->markets)]);
                    $this->myListings($p);
                    break;
                case 0:
                    $this->plugin->markets[array_search($dataMarket, $this->plugin->markets)]["state"] = 1;
                    $p->sendMessage("§aMarket > §fYour Listing Has Been Successfully Published");
                    break;
                case 1:
                    $this->plugin->markets[array_search($dataMarket, $this->plugin->markets)]["state"] = 0;
                    $p->sendMessage("§aMarket > §fYour Listing Has Been Successfully Made Private");
                    break;
                default:
                    break;
            }
        });
        $form->setTitle($item->getName() . "-" . $dataMarket["id"]);
        $form->setContent("ID: " . $dataMarket["id"] . "\nItem name: " . $item->getName() . "\nItem ID: " . $item->getId() . "\nItem Meta: " . $item->getMeta() . "\nPrice: " . number_format((float) $dataMarket["price"]) . "\nSeller: " . $dataMarket["seller"]);
        $form->addButton("Remove Listing", label: "remove");
        $form->addButton(($dataMarket["state"] > 0) ? "Private listing" : "Publish listing", label: $dataMarket["state"]);
        $form->sendToPlayer($p);
        return $form;
    }

    public function nextBuy(Player $p, array $dataMarket)
    {
        $item = $p->getInventory()->getItemInHand()->jsonDeserialize($dataMarket["itemJson"]);
        $form = new ModalForm(function (Player $p, $data) use ($dataMarket, $item) {
            if ($data === null)
                return;
            if ($data) {
                if($dataMarket["seller"] === $p->getName()) return $p->sendMessage("§aMarket > §cPlease note that sellers are not allowed to purchase their own items for sale.");
                if ($p->getInventory()->canAddItem($item)) {
                    // EconomyAPI plugin
                    $economyApi = EconomyAPI::getInstance();
                    if ($economyApi->myMoney($p) < intval($dataMarket["price"])) {
                        return $p->sendMessage("§aMarket > §cInsufficient funds to purchase this listing.");
                    }
                    $economyApi->reduceMoney($p, intval($dataMarket["price"]));
                    $p->getInventory()->addItem($item);
                    $seller = $p->getServer()->getPlayerExact($dataMarket["seller"]);
                    if ($seller instanceof Player) {
                        $seller->sendMessage("§aMarket > §fplayer §a" . $p->getName() . "§f buy §a" . $item->getName() . "§f id §a" . $dataMarket["id"]);
                    }
                    $seller->sendMessage("§aMarket > §fsuccesfully buy §a" . $item->getName());
                    unset($this->plugin->markets[array_search($dataMarket, $this->plugin->markets)]);
                } else {
                    $p->sendMessage("§aMarket > §cYour inventory full!");
                }
            }
        });
        $form->setTitle("Buy");
        $form->setContent("ID: " . $dataMarket["id"] . "\nItem name: " . $item->getName() . "\nItem ID: " . $item->getId() . "\nItem Meta: " . $item->getMeta() . "\nPrice: " . number_format((float) $dataMarket["price"]) . "\nSeller: " . $dataMarket["seller"]);
        $form->setButton1("Buy");
        $form->setButton2("Back");
        $form->sendToPlayer($p);
        return $form;
    }

    public function sellMenu(Player $p)
    {
        $form = new SimpleForm(function (Player $p, $data = null) {
            if ($data === null)
                return;
            match ($data) {
                0 => $this->nextSell($p),
                1 => $this->myListings($p),
                default => $this->main($p)
            };
        });
        $form->setTitle("Sell");
        $form->addButton("Sell item");
        $form->addButton("My listing");
        $form->sendToPlayer($p);
        return $form;
    }

    public function nextSell(Player $p, $label = "")
    {
        $items = array();
        foreach ($p->getInventory()->getContents() as $item) {
            array_push($items, $item->getName());
        }
        $form = new CustomForm(function (Player $p, $data = null) {
            if ($data === null)
                return;
            if (!is_numeric($data["count"])) {
                $this->nextSell($p, "§cCount must be type int");
                return;
            }
            if (!is_numeric($data["price"])) {
                $this->nextSell($p, "§cPrice must be type int");
                return;
            }
            $items = array();
            foreach ($p->getInventory()->getContents() as $item) {
                array_push($items, $item);
            }
            $itemSelected = $items[$data["items"]];
            if ($data["count"] > $itemSelected->getCount())
                $this->nextSell($p, "§cNot enough items");
            $itemSelected->setCount($data["count"]);
            $this->plugin->registerMarket(
                $p,
                array(
                    "seller" => $p->getName(),
                    "itemJson" => $itemSelected->jsonSerialize(),
                    "price" => $data["price"],
                    "id" => uniqid(),
                    "state" => !$data["state"] ? 0 : 1,
                    "created" => date("Y-m-d h:i:s")
                )
            );
            Utils::removeItem($p, $itemSelected);
            $p->sendMessage("§aMarket > §fItem §a" . $itemSelected->getName() . "§f added to Market");
        });
        $form->setTitle("Sell");
        $form->addLabel($label);
        $form->addDropdown("Please select an item to sell", $items, label: "items");
        $form->addInput("Please enter quantity", label: "count");
        $form->addInput("Please enter the selling price", label: "price");
        $form->addToggle("", label: "state", default: false);
        $form->sendToPlayer($p);
        return $form;
    }
}