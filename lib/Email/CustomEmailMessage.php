<?php

namespace Proklung\Notifier\Email;

use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

/**
 * Class CustomEmailMessage
 * @package Proklung\Notifier\Email
 */
class CustomEmailMessage implements MessageInterface
{
    /**
     * @var RawMessage $message
     */
    private $message;

    /**
     * @var Envelope|null $envelope
     */
    private $envelope;

    /**
     * CustomEmailMessage constructor.
     *
     * @param RawMessage    $message
     * @param Envelope|null $envelope
     */
    public function __construct(RawMessage $message, ?Envelope $envelope = null)
    {
        $this->message = $message;
        $this->envelope = $envelope;
    }

    /**
     * @param Notification            $notification
     * @param EmailRecipientInterface $recipient
     * @param string|null             $from         Поле from из конфига.
     *
     * @return static
     */
    public static function fromNotification(
        Notification $notification,
        EmailRecipientInterface $recipient,
        ?string $from = null
    ): self {
        if ('' === $recipient->getEmail()) {
            throw new InvalidArgumentException(sprintf('"%s" needs an email, it cannot be empty.', __CLASS__));
        }

        // Если не передали поле from снаружи, то берем из EmailRecipientInterface
        if (!$from) {
            $from = $recipient->getEmail();
        }

        $content = $notification->getContent() ?: $notification->getSubject();

        if (!class_exists(NotificationEmail::class)) {
            $email = (new Email())
                ->from($from)
                ->to($recipient->getEmail())
                ->subject($notification->getSubject())
                ->text($content)
            ;
        } else {
            $email = (new TemplatedEmail())
                ->from($from)
                ->to($recipient->getEmail())
                ->subject($notification->getSubject())
                ->htmlTemplate($content)
                ->text($content)
            ;

            if ($exception = $notification->getException()) {
                $email->exception($exception);
            }
        }

        return new self($email);
    }

    /**
     * @return RawMessage
     */
    public function getMessage(): RawMessage
    {
        return $this->message;
    }

    /**
     * @return Envelope|null
     */
    public function getEnvelope(): ?Envelope
    {
        return $this->envelope;
    }

    /**
     * @return $this
     */
    public function envelope(Envelope $envelope): self
    {
        $this->envelope = $envelope;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSubject(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getRecipientId(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): ?MessageOptionsInterface
    {
        return null;
    }

    /**
     * @param string|null $transport
     *
     * @return $this
     */
    public function transport(?string $transport): self
    {
        if (!$this->message instanceof Email) {
            throw new LogicException('Cannot set a Transport on a RawMessage instance.');
        }
        if (null === $transport) {
            return $this;
        }

        $this->message->getHeaders()->addTextHeader('X-Transport', $transport);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTransport(): ?string
    {
        return $this->message instanceof Email ? $this->message->getHeaders()->getHeaderBody('X-Transport') : null;
    }
}