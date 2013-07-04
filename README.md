README
===============

What is php-by-contract?
-----------------

php-by-contract strives to be a lightweight design by contract library for PHP which can be added with minimal changes
to any existing projects.

Currently we are at a state of a mere prove of concept, but development will continue!


Installation
-----------------

If you want to give this project a try you can do so using composer.
Just include the following code into your composer.json and you are good to go.

```js
{
    "require": {
        "techdivision/php-by-contract": "dev-master"
    }
}
```

Usage
-----------------

By now, we have scripted bootstrapping so the only thing you have to do is adding the following code AFTER loading any
autoloaders your application might require.

```php
require_once "vendor/techdivision/php-by-contract/src/TechDivision/PBC/Bootstrap.php";
```

And then?
-----------------

Have fun testing! :)
Feel free to come back to me with any bugs you might encounter.