<?php

namespace dhnnz\Market\forms;

use dhnnz\Market\libs\jojoe77777\FormAPI\CustomForm;
use dhnnz\Market\libs\jojoe77777\FormAPI\ModalForm;
use dhnnz\Market\libs\jojoe77777\FormAPI\SimpleForm;
use dhnnz\Market\Loader;
use dhnnz\Market\utils\Utils;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\lang\Translatable;
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

    public function marketListing(Player $p, $page = 1, $markets = null, $search = "")
    {
        if ($markets === null) {
            $markets = array();
            foreach ($this->plugin->markets as $sellers => $market) {
                if ($market["state"] > 0) {
                    array_push($markets, $market);
                }
            }
        }
        $form = new SimpleForm(function (Player $p, $data = null) use ($page, $markets) {
            if ($data === null)
                return;

            if ($data == "close") {
            }
            if ($data == "search")
                $this->searchListing($p);
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
            if ($data !== "next" and $data !== "prev" and $data !== "close" and $data !== "search") {
                $this->nextBuy($p, $markets[$data]);
            }
        });
        $form->addButton("§cClose", 0, "textures/blocks/barrier", label: "close");
        $form->addButton("§fSearch", label: "search");
        $searchText = ($search !== "") ? "Search: $search\n" : "";
        $listingsText = (count($markets) < 1) ? "There are no listings available at this time." : "";

        $form->setContent($searchText . $listingsText);
        $form->setTitle("Market Listings - Page $page");
        $total_pages = ceil(count($markets) / 5);
        $start = ($page - 1) * 5;
        $end = min(($start + 5), count($markets));
        for ($i = $start; $i < $end; $i++) {
            $item = $p->getInventory()->getItemInHand()->jsonDeserialize($markets[$i]["itemJson"]);
            $form->addButton("§f" . $item->getName() . ": " . $item->getCount() . "\n§fPrice: §5" . number_format((float) $markets[$i]["price"]) . "§f Sell by " . $markets[$i]["seller"], label: $i);
        }
        if ($page < $total_pages) {
            $form->addButton("Next", 0, "textures/ui/arrow_right", label: "next");
        }
        if ($page > 1) {
            $form->addButton("Prev", 0, "textures/ui/arrow_left", label: "prev");
        }
        $form->sendToPlayer($p);
        return $form;
    }

    public function searchListing(Player $p)
    {
        $form = new CustomForm(function (Player $p, $data = null) {
            if ($data === null)
                return;
            $search = $data["search"];
            $markets = [];

            foreach ($this->plugin->markets as $market) {
                if ($market["state"] > 0) {
                    $item = ItemFactory::getInstance()->get(1)->jsonDeserialize($market["itemJson"]);
                    $seller = $market["seller"];

                    foreach (explode(" ", $search) as $word) {
                        if (stripos($item->getName(), $word) !== false || stripos($seller, $word) !== false) {
                            if (!in_array($market, $markets)) {
                                $markets[] = $market;
                            }
                        }
                    }
                }
            }

            $this->marketListing($p, markets: $markets, search: $search);
        });
        $form->setTitle("Search");
        $form->addInput("search:", label: "search");
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
        $form = new ModalForm(function (Player $p, $data = false) use ($dataMarket, $item) {
            if ($data === null)
                return;
            if ($data) {
                if ($dataMarket["seller"] === $p->getName())
                    return $p->sendMessage("§aMarket > §cPlease note that sellers are not allowed to purchase their own items for sale.");
                if ($p->getInventory()->canAddItem($item)) {
                    $provider = $this->plugin->getEconomy();
                    $provider->buy(
                        $p, $dataMarket["seller"], $dataMarket["price"],
                        function (int $status) use ($p, $item, $dataMarket) {
                            if ($status !== Loader::STATUS_SUCCESS)
                                return $p->sendMessage("§aMarket > §cInsufficient funds to purchase this listing.");
                            $p->getInventory()->addItem($item);
                            $seller = $p->getServer()->getPlayerExact($dataMarket["seller"]);
                            if ($seller instanceof Player) {

                                $seller->sendMessage("§aMarket > §fplayer §a" . $p->getName() . "§f buy §a" . $item->getName() . "§f id §a" . $dataMarket["id"]);
                            }
                            $p->sendMessage("§aMarket > §fsuccesfully buy §a" . $item->getName());
                            unset($this->plugin->markets[array_search($dataMarket, $this->plugin->markets)]);
                        }
                    );
                } else {
                    $p->sendMessage("§aMarket > §cYour inventory full!");
                }
            }
            return;
        });
        $arrayEnchant = array_map(function ($enchant) {
            return [
                ($enchant->getType()->getName() instanceof Translatable) ? $enchant->getType()->getName()->getText() : $enchant->getType()->getName(),
                $enchant->getLevel()
            ];
        }, $item->getEnchantments());
        $enchantments = array_map(function ($enchant) {
            return sprintf('- %s (Level %d)', $enchant[0], $enchant[1]);
        }, $arrayEnchant);
        $form->setTitle("Buy");
        $form->setContent("ID: " . $dataMarket["id"] . "\nItem name: " . $item->getName() . "\nItem ID: " . $item->getId() . "\nItem Meta: " . $item->getMeta() . "\nPrice: " . number_format((float) $dataMarket["price"]) . "\nSeller: " . $dataMarket["seller"] . "\nEnchantment (" . count($enchantments) . "):\n" . implode("\n", $enchantments));
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
            if (intval($data["count"]) > $itemSelected->getCount()){
                $this->nextSell($p, "§cNot enough items");
                return;
            }
            $itemSelected->setCount(intval($data["count"]));
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