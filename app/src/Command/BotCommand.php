<?php

declare(strict_types=1);

namespace Wiwi\Bot\Command;

use Discord\Discord as Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Wiwi\Bot\Discord\Command\CommandInterface;
use Wiwi\Bot\Discord\Discord;
use Wiwi\Bot\Discord\Interaction\InteractionInterface;

#[AsCommand(
    name: 'wiwi:bot',
    description: 'Run Discord bot',
)]
final class BotCommand extends Command
{
    public function __construct(
        /** @var CommandInterface[] $commands */
        #[TaggedIterator(CommandInterface::class)]
        private readonly iterable $commands,
        /** @var InteractionInterface[] $interactions */
        #[TaggedIterator(InteractionInterface::class)]
        private readonly iterable $interactions,
        private readonly Discord $discord,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->discord->on('init', function (Client $discord) {
            foreach ($this->commands as $command) {
                $command->callback($discord);
            }

            foreach ($this->interactions as $command) {
                $command->callback($discord);
            }
        });

        $this->discord->run();

        return Command::SUCCESS;
    }
}
