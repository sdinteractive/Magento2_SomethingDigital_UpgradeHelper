build:
	cp Test/Unit/phpunit.xml.dist ../../../../dev/tests/unit/phpunit.xml
	cp -r Test/Fixtures/app/design/frontend/SomethingDigitalUpgradeHelper ../../../../app/design/frontend/
	cd ../../../../; vendor/bin/phpunit -c dev/tests/unit/phpunit.xml
	rm -rf ../../../../app/design/frontend/SomethingDigitalUpgradeHelper
