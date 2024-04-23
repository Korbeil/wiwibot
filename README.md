# wiwibot

```php
app/bin/console wiwi:register # to register commands within Discord
app/bin/console wiwi:bot # to start the bot
```

## Docker

```bash
docker build --tag 'wiwibot' .
docker run -d --restart unless-stopped wiwibot:latest
```
