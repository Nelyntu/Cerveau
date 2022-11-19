quality: ps rector

ps:
	vendor/bin/phpstan analyse src --level 9

rector:
	vendor/bin/rector process src
