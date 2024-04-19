<?php

declare(strict_types=1);

namespace Wiwi\Bot\Discord\Command;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Command\Option as CommandOption;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Request\Option;
use Discord\Parts\User\Member;
use Wiwi\Bot\Discord\Interaction\OpaqueInteraction;
use Wiwi\Bot\Discord\Interaction\TimeOutInteraction;

final class TimeOutCommand implements CommandInterface
{
    private const COMMAND_NAME = 'tg';
    private const WIWI_ID = '204916723676086272';

    /** @var array<string, Carbon> */
    private array $usePerMembers = [];

    public function __construct(
        private readonly TimeOutInteraction $timeOutInteraction,
        private readonly OpaqueInteraction $opaqueInteraction,
    ) {
    }

    public function configure(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName(self::COMMAND_NAME)
            ->setDescription('TG ????')
            ->addOption(
                (new CommandOption($discord))
                    ->setName('user')
                    ->setDescription('L\'utilisateur')
                    ->setType(CommandOption::USER)
            );
    }

    public function callback(Discord $discord): void
    {
        $discord->listenCommand(self::COMMAND_NAME, function (Interaction $interaction) {
            if ($interaction->user->bot) {
                return;
            }

            $now = Carbon::now();
            if (array_key_exists($interaction->member->user->id, $this->usePerMembers)
                && $this->usePerMembers[$interaction->member->user->id]->day === $now->day) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Déjà lancé aujourd\'hui, retente ta chance demain !'), true);

                return;
            }
            $this->usePerMembers[$interaction->member->user->id] = $now;

            /** @var Option[] $interactionOptions */
            $interactionOptions = $interaction->data?->options;
            /** @var string|null $targetUser */
            $targetUserId = $interactionOptions['user']->value ?? self::WIWI_ID;

            $random = mt_rand(0, 100);
            $until = $now->add(new CarbonInterval(seconds: 40));

            if ($random < 10) {
                // shinny User
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('WOW on compren R à ce que tu di'));
                $this->opaqueInteraction->addMember($interaction->member->user, $now->add(new CarbonInterval(minutes: 2)));
            } elseif ($random < 55) {
                // remover Wiwi
                $interaction->respondWithMessage(MessageBuilder::new()->setContent(sprintf('ta raison, <@%s> TG', self::WIWI_ID)));
                $interaction->guild->members->fetch($targetUserId)->then(function (Member $member) use ($until) {
                    $this->opaqueInteraction->addMember($member->user, $until);
                });
            } else {
                // timeout User
                $this->timeOutInteraction->addMember($interaction->member->user, $until);
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Nn toi tg'));
            }
        });
    }
}
