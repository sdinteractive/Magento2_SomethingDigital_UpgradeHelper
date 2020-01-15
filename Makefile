build:
	cp Test/Unit/phpunit.xml.dist ../../../../dev/tests/unit/phpunit.xml
	cp -r Test/Fixtures/app/design/frontend/SomethingDigitalUpgradeHelper ../../../../app/design/frontend/
	cp -r Test/Fixtures/app/code/SomethingDigitalUpgradeHelper ../../../../app/code/
	cp -r Test/Fixtures/vendor/somethingdigitalupgradehelper ../../../../vendor/
	cd ../../../../; vendor/bin/phpunit -c dev/tests/unit/phpunit.xml
	rm -rf ../../../../app/design/frontend/SomethingDigitalUpgradeHelper
	rm -rf ../../../../app/code/SomethingDigitalUpgradeHelper
	rm -rf ../../../../vendor/somethingdigitalupgradehelper
