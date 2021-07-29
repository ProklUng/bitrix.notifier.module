<?php

namespace Proklung\Notifier\Bitrix\Contract;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * Interface BitrixNotifierSenderInterface
 * @package Proklung\Notifier\Bitrix\Contract
 *
 * @since 29.07.2021
 */
interface BitrixNotifierSenderInterface
{
    /**
     * Отправить сообщение.
     *
     * @param string $codeEvent Код события.
     * @param array  $arFields  Параметры события.
     *
     * @return void
     * @throws ArgumentException | ObjectPropertyException | SystemException Битриксовые ошибки.
     */
    public function send(string $codeEvent, array $arFields) : void;
}