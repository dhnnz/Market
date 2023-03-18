<?php

namespace dhnnz\Market\economy\types;

use dhnnz\Market\economy\Economy;
use dhnnz\Market\Loader;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\version\LegacyBEAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

use pocketmine\player\Player;

class BedrockEconomy extends Economy
{
    /** @var LegacyBEAPI $bedrockEconomyAPI */
    private $bedrockEconomyAPI;

    public function __construct()
    {
        $this->bedrockEconomyAPI = BedrockEconomyAPI::legacy();
    }

    public function buy(Player $player, int $amount, callable $callable): void
    {
        $this->bedrockEconomyAPI->subtractFromPlayerBalance(
            $player->getName(),
            $amount,
            ClosureContext::create(
                function (bool $wasUpdated) use ($callable): void {
                    if ($wasUpdated) {
                        $callable(Loader::STATUS_SUCCESS);
                    } else {
                        $callable(Loader::STATUS_ENOUGH);
                    }
                }
            )
        );
    }
}