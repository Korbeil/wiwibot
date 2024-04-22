<?php

declare(strict_types=1);

namespace Wiwi\Bot\Discord\Interaction;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Symfony\Component\HttpClient\CurlHttpClient;

final class YouTubeInteraction implements InteractionInterface
{
    private ?string $lastVideoId = null;

    public function __construct(
        private readonly string $youtubeToken,
        private readonly string $youtubeChannelId,
        private readonly string $youtubeDiscordRole,
        private readonly string $youtubeDiscordChannel,
    ) {
    }

    public function callback(Discord $discord): void
    {
        $youtubeClient = new CurlHttpClient([
            'base_uri' => 'https://www.googleapis.com/',
            'query' => [
                'key' => $this->youtubeToken,
                'channelId' => $this->youtubeChannelId,
            ],
        ]);

        $discord->getLoop()->addPeriodicTimer(15 * 60, function () use ($youtubeClient, $discord) {
            $response = $youtubeClient->request('GET', '/youtube/v3/search', [
                'query' => [
                    'part' => 'snippet',
                    'maxResults' => 1,
                    'order' => 'date',
                    'type' => 'video',
                ],
            ]);

            $channelLastVideoData = $response->toArray();
            $channelLastVideoData = $channelLastVideoData['items'][0];

            if (null === $this->lastVideoId) {
                $this->lastVideoId = $channelLastVideoData['id']['videoId'];
            } elseif ($channelLastVideoData['id']['videoId'] !== $this->lastVideoId) {
                // new video !
                $message = sprintf(
                    '<@&%s> %s vient d\'upload %s, rendez-vous sur https://www.youtube.com/watch?v=%s',
                    $this->youtubeDiscordRole,
                    $channelLastVideoData['snippet']['channelTitle'],
                    $channelLastVideoData['snippet']['title'],
                    $channelLastVideoData['id']['videoId']
                );

                $discord->getChannel($this->youtubeDiscordChannel)->sendMessage(MessageBuilder::new()->setContent($message));
                $this->lastVideoId = $channelLastVideoData['id']['videoId'];
            }
        });
    }
}
