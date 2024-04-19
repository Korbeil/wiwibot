<?php

declare(strict_types=1);

namespace Wiwi\Bot\Discord\Interaction;

use Carbon\Carbon;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\User;
use Discord\WebSockets\Event;

final class TimeOutInteraction implements InteractionInterface
{
    /** @var array<string, Carbon> */
    private array $timeOutMembers = [];

    public function addMember(User $user, Carbon $until): void
    {
        $this->timeOutMembers[$user->id] = $until;
    }

    public function callback(Discord $discord): void
    {
        $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
            if ($message->author->bot) {
                return;
            }

            if (array_key_exists($message->author->id, $this->timeOutMembers)) {
                $until = $this->timeOutMembers[$message->author->id];

                if (Carbon::now() > $until) {
                    unset($this->timeOutMembers[$message->author->id]);

                    return;
                }

                $message->reply(MessageBuilder::new()->setContent('j di TG'))->then(function () use ($message) {
                    $message->delete();
                });
            }
        });
    }
}
