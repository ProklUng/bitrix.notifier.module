<?php

namespace Proklung\Notifier\Bitrix;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Sms\Message;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Sms\TemplateTable;
use Bitrix\Main\SystemException;
use RuntimeException;

/**
 * Class EventBridgeSms
 * @package Proklung\Notifier\Bitrix
 *
 * @since 28.07.2021
 */
class EventBridgeSms
{
    /**
     * Получить скомпилированный текст письма для события.
     *
     * @param Collection|null $templates Шаблоны.
     * @param array           $context   Значения полей ($arFields).
     *
     * @return Message
     * @throws RuntimeException
     */
    public function compileMessage($templates, array $context) : Message
    {
        if (!$templates) {
            throw new RuntimeException('Template SMS object cannot be NULL');
        }

        $result = [];
        foreach ($templates as $smsTemplate) {
            $result[] = $smsTemplate;
        }

        return Message::createFromTemplate(current($result), $context);
    }

    /**
     * @param string      $eventName Код события.
     * @param string      $siteId    ID сайта.
     * @param string|null $langId    ID языка.
     *
     * @return Collection|null
     *
     * @throws ArgumentException | ObjectPropertyException | SystemException Битриксовые ошибки.
     */
    public function fetchTemplates(string $eventName, string $siteId, ?string $langId = null)
    {
        $filter = Query::filter()
            ->where('ACTIVE', 'Y')
            ->where('SITES.LID', $siteId);

            $filter->where('EVENT_NAME', $eventName);

        if ($langId !== null) {
            $filter->where(Query::filter()
                ->logic('or')
                ->where('LANGUAGE_ID', $langId)
                ->where('LANGUAGE_ID', '')
                ->whereNull('LANGUAGE_ID'));
        }

        $result = TemplateTable::getList([
            'select' => ['*', 'SITES.SITE_NAME', 'SITES.SERVER_NAME', 'SITES.LID'],
            'filter' => $filter,
        ]);

        return $result->fetchCollection();
    }
}
