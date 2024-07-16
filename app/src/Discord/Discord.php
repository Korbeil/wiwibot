<?php

declare(strict_types=1);

namespace Wiwi\Bot\Discord;

use Discord\Discord as Client;
use Discord\WebSockets\Intents;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

final class Discord extends Client
{
    public function __construct(
        #[Target('discordLogger')] LoggerInterface $logger,
        string $discordToken,
        LoopInterface $loop,
    ) {
        parent::__construct([
            'token' => $discordToken,
            'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT | Intents::GUILD_MEMBERS,
            'logger' => $logger,
            'loop' => $loop,
        ]);
    }
}
