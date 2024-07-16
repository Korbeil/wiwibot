<?php

declare(strict_types=1);

namespace Wiwi\Bot\Discord\Interaction;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

use React\Socket\SocketServer;

final readonly class TwitchInteraction implements InteractionInterface
{
    private const DEFAULT_HEADERS = [
        'Server' => '',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
    ];
    private const JSON_TYPE = [
        'Content-Type' => 'application/json',
    ];
    private const TEXT_TYPE = [
        'Content-Type' => 'text/plain',
    ];

    public function __construct(
        private int $reactHttpServerPort,
        private LoopInterface $loop,
        private LoggerInterface $logger,
        private string $twitchDiscordRole,
        private string $twitchDiscordChannel
    ) {
    }

    public function callback(Discord $discord): void
    {
        // @see https://agiroloki.medium.com/streaming-reactphp-in-reactjs-2cda05de3b73
        $http = new HttpServer(function (ServerRequestInterface $request) use ($discord): PromiseInterface {
            $dispatcher = simpleDispatcher(
                function (RouteCollector $collector) use ($request, $discord) {
                    $collector->addRoute('POST', '/hook/eventsub/online', fn () => $this->eventOnline($request, $discord));

                    return $collector;
                },
            );
            $response = $dispatcher->dispatch(
                $request->getMethod(),
                $request->getUri()->getPath(),
            );
            $info = $response[0];

            if (Dispatcher::METHOD_NOT_ALLOWED === $info) {
                return resolve(
                    new Response(
                        StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED,
                        array_merge(self::JSON_TYPE, self::DEFAULT_HEADERS),
                        json_encode(['message' => 'Invalid request method']),
                    ),
                );
            }

            if (Dispatcher::NOT_FOUND === $info) {
                return resolve(
                    new Response(
                        StatusCodeInterface::STATUS_NOT_FOUND,
                        array_merge(self::JSON_TYPE, self::DEFAULT_HEADERS),
                        json_encode(['message' => 'Route not found: ' . $request->getUri()->getPath()]),
                    ),
                );
            }

            return $response[1]();
        });

        $socket = new SocketServer(sprintf('0.0.0.0:%d', $this->reactHttpServerPort), loop: $this->loop);

        $http->on('error', function (\Exception $throwable) {
            $this->logger->critical($throwable->getMessage());
        });

        $http->listen($socket);
    }

    private function eventOnline(ServerRequestInterface $request, Discord $discord): PromiseInterface
    {
        $contents = $request->getBody()->getContents();
        $webhookContents = json_decode($contents, true);

        // webhook verification
        if ('webhook_callback_verification' === $request->getHeaderLine('Twitch-Eventsub-Message-Type')) {
            return resolve(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    \array_merge(self::TEXT_TYPE, self::DEFAULT_HEADERS),
                    $webhookContents['challenge']
                ),
            );
        } elseif (array_key_exists('subscription', $webhookContents) && 'stream.online' === $webhookContents['subscription']) {
            $discord->getChannel($this->twitchDiscordChannel)->sendMessage(MessageBuilder::new()->setContent(
                sprintf('<@&%s> WiwiTV part en live sur https://www.twitch.tv/wiwitv ! Viens foutre le bordel et raconter ta vie !', $this->twitchDiscordRole)
            ));
        }

        return resolve(
            new Response(
                StatusCodeInterface::STATUS_OK,
                \array_merge(self::TEXT_TYPE, self::DEFAULT_HEADERS),
                'OK'
            ),
        );
    }
}
