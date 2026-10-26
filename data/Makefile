.PHONY: build tests translations workers workers@restart workers@stop workers@logs workers@status

build:
	composer install --no-interaction --prefer-dist --optimize-autoloader
	npm install --no-fund
	npm run build
	php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

tests:
	APP_ENV=test php bin/console doctrine:database:drop --force || true
	APP_ENV=test php bin/console doctrine:database:create
	APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	APP_ENV=test php bin/console doctrine:fixtures:load --no-interaction
	APP_ENV=test XDEBUG_MODE=coverage php bin/phpunit --coverage-html ./var/coverage/

translations:
	php bin/console translation:extract --force --format=yaml ru

workers:
	loginctl enable-linger
	systemctl --user stop askona-worker@*.service
	mkdir -p ~/.config/systemd/user/
	cp ./worker.service ~/.config/systemd/user/askona-worker@.service
	sed -i "s|{{PATH}}|$(shell pwd)|g" ~/.config/systemd/user/askona-worker@.service
	systemctl --user daemon-reload
	systemctl --user enable askona-worker@1.service
	systemctl --user start askona-worker@1.service
	systemctl --user enable askona-worker@2.service
	systemctl --user start askona-worker@2.service
	systemctl --user enable askona-worker@3.service
	systemctl --user start askona-worker@3.service
	systemctl --user enable askona-worker@4.service
	systemctl --user start askona-worker@4.service

workers@restart:
	systemctl --user restart askona-worker@*.service

workers@stop:
	systemctl --user stop askona-worker@*.service

workers@logs:
	journalctl -f --user-unit askona-worker@*

workers@status:
	systemctl --user status askona-worker@*.service
