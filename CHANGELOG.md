# Version 0.3.5

## Bugfixes

* None

## Features

* Refactoring ANT PHPUnit execution process
* Composer integration by optimizing folder structure (move bootstrap.php + phpunit.xml.dist => phpunit.xml)
* Switch to new appserver-io/build build- and deployment environment

# Version 0.3.4

## Bugfixes

* `StructureMap::findIdentifier()` had problems with long class DocBlocks. Did a quick fix

# Version 0.3.3

## Bugfixes

* Configured omitted namespaces did not get stripped of additional backslashes

## Features

* `StructureMap::getEntries()` can be filtered for enforced entries only
* Config now supports the value `enforcement/omit`