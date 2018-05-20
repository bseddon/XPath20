# XPath 2.0 processor for PHP

**Table of contents**
* [About the project](#about-the-project)
* [License](#license)
* [Contributing](#contributing)
* [Install](#install)
* [Getting started](#getting-started)

## About the project

PHP does include an XPath 1.0 implementation but at the time of writing does not include processor to handle XPath 2.0 expressions.

This library implements the [XPath 2.0 specification](https://www.w3.org/TR/xpath20/) for PHP and is able to pass the 15,000 or so
[XPath 2.0 conformance suite tests](https://dev.w3.org/2006/xquery-test-suite/PublicPagesStagingArea/).

Note that this project does NOT implemen an XSLT 2.0 processor or an XQuery statement evaluator.

### Motivation

This project is standalone but is also part of the XBRL project.  

XBRL Formulas make extensive use of XPath 2.0 both to compute components of formula definitions and to define 
how aspects of the various formula specifications should be interpreted and turned into executable code.

### Dependencies

This project depends on [pear/log](https://github.com/pear/Log) and [lyquidity/xml](https://github.com/bseddon/xml)

## License

This project is released under [GPL version 3.0](LICENCE)

**What does this mean?**

It means you can use the source code in any way you see fit.  However, the source code for any changes you make must be made available to others and must be made
available on the same terms as you receive the source code in this project: under a GPL v3.0 license.  You must include the license of this project with any
distribution of the source code whether the distribution includes all the source code or just part of it.  For example, if you create a class that derives 
from one of the classes provided by this project - a new taxonomy class, perhaps - that is derivative.

**What does this not mean?**

It does *not* mean that any products you create that only *use* this source code must be released under GPL v3.0.  If you create a budgeting application that uses
the source code from this project to access data in instance documents, used by the budgeting application to transfer data, that is not derivative. 

## Contributing

We welcome contributions.  See our [contributions page](https://gist.github.com/bseddon/cfe04753192087c82766bee583f519aa) for more information.  If you do choose
to contribute we will ask you to agree to our [Contributor License Agreement (CLA)](https://gist.github.com/bseddon/cfe04753192087c82766bee583f519aa).  We will 
ask you to agree to the terms in the CLA to assure other users that the code they use is not going to be encumbered by a labyrinth of different license and patent 
liabilities.  You are also urged to review our [code of conduct](CODE_OF_CONDUCT.md).

## Install

The project can be installed by [composer](https://getcomposer.org/).  Save the following JSON into a file called 'composer.json' in your application folder and run
the command:

```
composer install --prefer-dist
```

composer.json

```
{
    "require": {
        "lyquidity/xpath2": "dev-master"
    },
	"minimum-stability": "dev",
	"prefer-stable": false
}
```

Or fork or download the repository.  It will also be necessary to download and install the [XML](https://github.com/bseddon/xml), 
[utilities](https://github.com/bseddon/) and [pear/Log](https://github.com/pear/Log) projects.

## Getting started

The examples folder includes illustrations of using the library to execute XPath 2.0 queries against XML documents.

Assuming you have installed the library using composer then this PHP application will run the test:

```
<php
require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ . "/vendor/lyquidity/XPath2/examples/examples.php";
```
