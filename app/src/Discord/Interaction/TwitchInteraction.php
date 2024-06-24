<?php

declare(strict_types=1);

namespace Wiwi\Bot\Discord\Interaction;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use FastRoute\Dispatcher;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Promise\PromiseInterface;
use React\Socket\SocketServer;
use FastRoute\RouteCollector;
use React\Http\Message\Response;
use function FastRoute\simpleDispatcher;
use function React\Promise\resolve;

final readonly class TwitchInteraction implements InteractionInterface
{
    private const DEFAULT_HEADERS = [
        'Server' => '',
        'Access-Control-Allow-Origin'  => '*',
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
    ) {
    }

    public function callback(Discord $discord): void
    {
        // @see https://agiroloki.medium.com/streaming-reactphp-in-reactjs-2cda05de3b73
        $http = new HttpServer(function (ServerRequestInterface $request) use ($discord): PromiseInterface {
            $dispatcher = simpleDispatcher(
                function (RouteCollector $route) use ($request, $discord) {
                    $route->get('/hook/eventsub/online', fn () => $this->eventOnline($request, $discord));

                    return $route;
                },
            );
            $response = $dispatcher->dispatch(
                $request->getMethod(),
                $request->getUri()->getPath(),
            );
            $info = $response[0];

            if ($info === Dispatcher::METHOD_NOT_ALLOWED) {
                return resolve(
                    new Response(
                        StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED,
                        array_merge(self::JSON_TYPE, self::DEFAULT_HEADERS),
                        json_encode(['message' => 'Invalid request method']),
                    ),
                );
            }

            if ($info === Dispatcher::NOT_FOUND) {
                return resolve(
                    new Response(
                        StatusCodeInterface::STATUS_NOT_FOUND,
                        array_merge(self::JSON_TYPE, self::DEFAULT_HEADERS),
                        json_encode(['message' => 'Route not found']),
                    ),
                );
            }

            return $response[1]();
        });

        $socket = new SocketServer(sprintf('0.0.0.0:%d', $this->reactHttpServerPort));
        $http->listen($socket);
    }

    private function eventOnline(ServerRequestInterface $request, Discord $discord): PromiseInterface
    {
        $contents = $request->getBody()->getContents();
        $webhook = json_decode($contents, true);

        if ('stream.online' === $webhook['type']) {
            $discord->getChannel('1229375406797357076')->sendMessage(MessageBuilder::new()->setContent('STREAMER IS LIVE'));
        }

        return resolve(
            new Response(
                StatusCodeInterface::STATUS_OK,
                \array_merge(self::TEXT_TYPE, self::DEFAULT_HEADERS),
            ),
        );
    }
}
