# wiwibot

```php
app/bin/console wiwi:register # to register commands within Discord
app/bin/console wiwi:bot # to start the bot
```

## Docker

```bash
docker build --no-cache --build-arg CACHE_DATE="$(date)" --tag 'wiwibot-wiwibot' .
docker compose up -d
```
