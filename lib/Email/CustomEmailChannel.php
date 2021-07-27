<?php

namespace Proklung\Notifier\Email;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Channel\ChannelInterface;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

/**
 * Class CustomEmailChannel
 * @package Proklung\Notifier\Email
 */
class CustomEmailChannel implements ChannelInterface
{
    /**
     * @var TransportInterface|null $transport
     */
    private $transport;

    /**
     * @var MessageBusInterface|null $bus
     */
    private $bus;

    /**
     * @var string|\Symfony\Component\Mime\Address|null $from
     */
    private $from;

    /**
     * @var Envelope|null $envelope
     */
    private $envelope;

    /**
     * CustomEmailChannel constructor.
     *
     * @param TransportInterface|null  $transport
     * @param MessageBusInterface|null $bus
     * @param string|null              $from
     * @param Envelope|null            $envelope
     */
    public function __construct(
        ?TransportInterface $transport = null,
        ?MessageBusInterface $bus = null,
        ?string $from = null,
        ?Envelope $envelope = null
    )
    {
        if (null === $transport && null === $bus) {
            throw new LogicException(sprintf('"%s" needs a Transport or a Bus but both cannot be "null".', static::class));
        }

        $this->transport = $transport;
        $this->bus = $bus;
        $this->from = $from ?: ($envelope ? $envelope->getSender() : null);
        $this->envelope = $envelope;
    }

    /**
     * @inheritdoc
     */
    public function notify(Notification $notification, RecipientInterface $recipient, string $transportName = null): void
    {
        $message = null;
        if ($notification instanceof EmailNotificationInterface) {
            $message = $notification->asEmailMessage($recipient, $transportName);
        }

        $message = $message ?: CustomEmailMessage::fromNotification($notification, $recipient, (string)$this->from);
        $email = $message->getMessage();
        if ($email instanceof Email) {
            if (!$email->getFrom()) {
                if (null === $this->from) {
                    throw new LogicException(sprintf('To send the "%s" notification by email, you should either configure a global "from" or set it in the "asEmailMessage()" method.', get_debug_type($notification)));
                }

                $email->from($this->from);
            }

            if (!$email->getTo()) {
                $email->to($recipient->getEmail());
            }
        }

        if (null !== $this->envelope) {
            $message->envelope($this->envelope);
        }

        if (null !== $transportName) {
            $message->transport($transportName);
        }

        if (null === $this->bus) {
            $this->transport->send($message->getMessage(), $message->getEnvelope());
        } else {
            $this->bus->dispatch(new SendEmailMessage($message->getMessage(), $message->getEnvelope()));
        }
    }

    /**
     * @inheritdoc
     */
    public function supports(Notification $notification, RecipientInterface $recipient): bool
    {
        return $recipient instanceof EmailRecipientInterface;
    }
}