<?php


namespace GamePlugin\Economy;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

class EconomyGet
{
function addMoneyFun($player, int $NumberMoney){
    $name = $player->getName();
    BedrockEconomyAPI::legacy()->addToPlayerBalance(
        $name,
        $NumberMoney,
        ClosureContext::create(
            function (bool $wasUpdated)use ($player, $NumberMoney) {
                if($wasUpdated) {
                    $player->sendMessage("§2На твой баланс было зачисленно $NumberMoney денег!");
                }else{
                    $player->sendMessage("§4Произошла ошибка пополнения счёта!");
                }
            },
        )
    );
}

function dellMoneyFun($player, int $NumberMoney){
    $name = $player->getName();
    BedrockEconomyAPI::legacy()->subtractFromPlayerBalance(
        $name,
        $NumberMoney,
        ClosureContext::create(
            function (bool $wasUpdated) use($player, $NumberMoney) {
                if($wasUpdated){
                    $player->sendMessage("§4Прости, но с твоего счёта пришлось снять $NumberMoney денег :(");
                }else{
                    $player->sendMessage("§4Произошла ошибка снятия денег с вашего счёта!");
                }
            },
        )
    );
}
function MoneyTransfer($playerOne, $playerTwo, int $AddMoney, int $DellMoney){
    $namePlayerOne = $playerOne->getName();
    $namePlayerTwo = $playerTwo->getName();
    BedrockEconomyAPI::legacy()->transferFromPlayerBalance(
        $namePlayerOne, // Sender
        $namePlayerTwo,  // Receiver
        $AddMoney,    // Amount
        ClosureContext::create(
            function (bool $successful) use ($playerOne, $playerTwo, $AddMoney, $DellMoney){
                if($successful){
                    $playerOne->sendMessage("§4Вы потеряли $DellMoney денег.");
                    $playerTwo->sendMessage("§2Вы получили $AddMoney денег, поздравляю вас!");
                }else{
                    $playerTwo->sendMessage("§4Произошла неизвестная ошибка при снятия средств с вашего счёт!");
                    $playerTwo->sendMessage("§4Произошла неизвестная ошибка при переводе средств на ваш счёт!");
                }
            },
        )
    );
}
}