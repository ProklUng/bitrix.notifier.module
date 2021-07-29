<?php

namespace Proklung\Notifier\Bitrix\Utils;

use Bitrix\Main\Mail\Internal\EventTable;
use Bitrix\Main\Type\DateTime;
use Exception;

/**
 * Class EventTableUpdater
 * @package Proklung\Notifier\Bitrix\Utils
 *
 * @since 29.07.2021
 */
class EventTableUpdater
{
    /**
     * Добавить запись в b_event о событии. Статический фасад.
     *
     * @param string  $eventCode Код события.
     * @param array   $fields    Поля.
     * @param integer $messageId Признак, что отправлено через Notifier.
     * @param string  $success   Признак успешности отправки.
     *
     * @return void
     */
    public static function create(
        string $eventCode,
        array $fields,
        int $messageId = 99999,
        string $success = 'Y'
    ) {
        $self = new static();

        $self->add($eventCode, $fields, $messageId, $success);
    }

    /**
     * Добавить запись в b_event о событии.
     *
     * @param string  $eventCode Код события.
     * @param array   $fields    Поля.
     * @param integer $messageId Признак, что отправлено через Notifier.
     * @param string  $success   Признак успешности отправки.
     *
     * @return void
     */
    public function add(
        string $eventCode,
        array $fields,
        int $messageId = 99999,
        string $success = 'Y'
    ) : void
    {
        // Эмуляция поведения Битрикса при обработке событий.
        try {
            EventTable::add(
                [
                    'EVENT_NAME' => $eventCode,
                    'SUCCESS_EXEC' => $success,
                    'MESSAGE_ID' => $messageId, // Признак, что отправлено через Notifier
                    'DUPLICATE' => 'N',
                    'LID' => SITE_ID,
                    'LANGUAGE_ID' => LANGUAGE_ID,
                    'DATE_INSERT' => new DateTime,
                    'DATE_EXEC' => new DateTime,
                    'C_FIELDS' => $fields,
                ]
            );
        } catch (Exception $e) {
            // Silence. Не самый важный момент.
        }
    }
}