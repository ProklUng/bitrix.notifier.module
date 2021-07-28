<?php

namespace Proklung\Notifier\Bitrix;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Mail\EventMessageCompiler;
use Bitrix\Main\Mail\Internal\EventMessageAttachmentTable;
use Bitrix\Main\Mail\Internal\EventMessageTable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * Class EventBridge
 * @package Proklung\Notifier\Bitrix
 *
 * @since 28.07.2021
 */
class EventBridge
{
    /**
     * Получить скомпилированный текст письма для события.
     *
     * @param EventInfo $eventInfo DTO события.
     * @param array     $context   Значения полей ($arFields).
     * @param array     $sites     ID сайтов.
     *
     * @return mixed
     */
    public function compileMessage(EventInfo $eventInfo, array $context, array $sites = [])
    {
        $message = EventMessageCompiler::createInstance([
            'EVENT' => $eventInfo->getEventCode(),
            'FIELDS' => $context,
            'MESSAGE' => $eventInfo->getMessageData(),
            'SITE' => $sites,
            'CHARSET' => 'UTF8',
        ]);
        $message->compile();

        return $message->getMailBody();
    }

    /**
     * @param string $codeEvent Код события.
     * @param array  $sites     ID сайтов.
     *
     * @return EventInfo[]
     *
     * @throws ArgumentException | ObjectPropertyException | SystemException Битриксовые ошибки.
     */
    public function getMessageTemplate(string $codeEvent, array $sites = [])
    {
        $arSites = ['s1'];
        if (count($sites) > 0) {
            $arSites = array_merge($arSites, $sites);
        }

        $messageDb = EventMessageTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=EVENT_NAME' => $codeEvent,
                '=EVENT_MESSAGE_SITE.SITE_ID' => $arSites,
            ],
            'group' => ['ID']
        ]);

        $result = [];
        foreach ($messageDb as $arMessage) {
            $eventMessage = EventMessageTable::getRowById($arMessage['ID']);

            $eventMessage['FILES'] = [];
            $attachmentDb = EventMessageAttachmentTable::getList([
                'select' => ['FILE_ID'],
                'filter' => ['=EVENT_MESSAGE_ID' => $arMessage['ID']],
            ]);

            while ($arAttachmentDb = $attachmentDb->fetch()) {
                $eventMessage['FILE'][] = $arAttachmentDb['FILE_ID'];
            }

            $result[] = EventInfo::fromArray($eventMessage);
        }

        return $result;
    }
}
