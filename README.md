INTRODUCTION
===============

What is php-by-contract?
-----------------

php-by-contract strives to be a lightweight [`Design by Contract`](<http://en.wikipedia.org/wiki/Design_by_contract>) library for PHP which can be added with minimal changes
to any existing projects.
With Design by Contract you can enforce the object oriented structure of your code and secure the interaction of these
objects with one another.


Installation
-----------------

If you want to give this project a try you can do so using composer.
Just include the following code into your composer.json` and you are good to go.

```js
{
    "require": {
        "techdivision/php-by-contract": "dev-master"
    }
}
```

Usage
-----------------

By now, we have scripted bootstrapping so the only thing you have to do is adding the following code **AFTER** registering any
autoloaders your application might require.

```php
require_once "<PATH_TO_VENDOR>/techdivision/php-by-contract/src/TechDivision/PBC/Bootstrap.php";
```

You also have to specify your project's root path(s) within the `config.default.json` (or add your own file with `Config::load()`):
```php
"project-dirs": [
        "./../../../tests"
    ]
```

You can specify multiple directories and additionally use [`glob-like`](<http://php.net/manual/en/function.glob.php>) regex
for more complicated paths.

After that you can specify contracts within your code's doc-comments.
Those might look like this example of a stack's pop-method:

```php
/**
 * @requires $this->size() >= 1
 * @ensures $pbcResult instanceof StackElement
 */
public function pop()
{
    return array_pop($this->elements);
}
```

Check out more ways to use contracts in the included tests.

And then?
-----------------

Have fun testing! :)
Feel free to come back to me with any bugs you might encounter.


DOCUMENTATION
===============
What can be done?
-----------------
As stated above this library aims to bring you the power of [`Design by Contract`](<http://en.wikipedia.org/wiki/Design_by_contract>),
a concept first outlined by Bertrand Meyer in 1986, to make your applications more robust and easier to debug.
This contains basic features as:

- Use your basic docBlock annotations `@param` and `@return` as type hints (scalar and class/interface based), including
    special features like "typed arrays" using e.g. `array<int>` (collections for scalar types only yet)
- Specify complex method contracts in PHP syntax using `@requires` as precondition and `@ensures` as postcondition
- Specify a state of validity for your classes (e.g. `$attribute !== null`) which will be true all times using `@invariant`
- The above (not including type safety) will be inherited by every child structure, strengthening your object hierarchies
- The library will warn you (exception or log message) on violation of these contracts

How does it work?
-----------------
We use a system of autoloading and code creation to ensure our annotations will get enforced.
This features a 4 step process:

- Autoloader : Handles autoloading and will know if contract enforcement is needed for a certain file.
    If so (and the cache is empty) the call will be directed to the Generator/Parser Combo
- Parser : Will parse the needed file using [`Tokenizer`](<http://www.php.net/manual/en/book.tokenizer.php>) and provide
    information for further handling.
- Generator : Will use stream filters to create a new file structure definition containing configured enforcement
- Cache : Will allow us to omit Parser and Generator for future calls, to speed up usage significantly.

Configuration
-----------------
The php-by-contract library can be configured via JSON config files.
The library contains a default configuration named `config.default.json` which contains a default configuration to
operate on the enclosed unit tests with the highest level of caution.
If you want to use the library you can alter this file but we strongly recommend to write your own file instead.
You can do so by calling `Config::load(<PATH_TO_YOUR_FILE>)` during bootstrapping your project.
This will take the default configuration and overwrite it with the values you specified. If something was not specified
by you, default settings will take effect.

Default config looks like this:

```js
{
    "environment": "production",

    "cache": {

        "dir": "./cache"
    },

    "autoloader": {

        "omit": [
            "PHPUnit",
            "Psr\\Log",
            "PHP"
        ]
    },

    "enforcement": {

        "enforce-default-type-safety": true,
        "processing": "exception",
        "level": 7
    },

    "project-dirs": [
        "./../../../tests"
    ]
}
```

Configuration files can contain the following options:

- *environment* : Can be either *production* or *development*. *development* lets you ignore the caching mechanism for
    for easier testing but comes with a severe performance hit

- *cache* :
    * *dir* : Entry which specifies the directory used as cache. This directory must be **writeable**

- *autoloader* :
    * *omit* : Namespaces (or their beginning) which will be ignored by the autoloading/enforcement mechanism

- *enforcement* :
    * *enforce-default-type-safety* : If `true` the library will consider `@param` and `@return` as contract conditions
    specifying a parameter/return-value type hinting
    * *processing* : Can be either *exception*, *none* or *logging* and will specify how the library will reaction on a
    broken contract. If *logging* is used, the *logger* option **has to be set** as well
    * *logger*(optional) : Needed if *processing* is set to *logging*. Specify a [`PSR-3`](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)
    compatible logger class which does not need any parameters on construction
    * *level* : You can specify a level of enforcement here. This is similar to Linux user right notation and will be used
    as a bitmask to switch on enforcement features
        - 1 : Preconditions will be enforced
        - 2 : Postconditions will be enforced
        - 4 : Invariants will be enforced

- *project-dirs* : List of directories which should be included into autoloading and enforcement mechanics. You can us
    [`glob`](<http://php.net/manual/en/function.glob.php>) regex here.

ROADMAP
===============
I have a lot more in mind to come but my current state of thought includes the following ideas which will be available
within future commits.
There currently is no version based roadmap or an order in which this features will be available.

- Support/surveillance of `@throws` annotation for enforcing interface error behaviour
- Support/surveillance of `@var` annotation as basic type safety for attributes
- `phpDocumentor<http://www.phpdoc.org/>` support for custom annotations
- Something like `@ignoreContract` to selectively ignore certain parts of your code
- Exception mapping so you can globally specify the exceptions the library should throw on errors