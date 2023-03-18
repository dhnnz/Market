<?php

namespace dhnnz\Market\economy;

use pocketmine\player\Player;

abstract class Economy{

    public function __construct(){}

    abstract public function buy(Player $player, int $amount, callable $callable): void;
}