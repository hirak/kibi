hirak/kibi
========================

Auto Dependency Injection Container

## Requirements

- PHP >= 7
- composer >= 1.2

## Example

```php
<?php
require 'vendor/autoload.php';
$container = new Hirak\Kibi\Instantiator;
$container->addTrait(Acme\Provider::class);

/** @var Acme\Dispatcher */
$dispatcher = $container->get(Acme\Dispatcher::class);
$dispatcher->run($_SERVER, $_REQUEST);
```

```php
<?php
namespace Acme;

trait Provider
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }
}
```
