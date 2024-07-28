AddWatermark is a library written in pure PHP that provides class that allows you to add watermark text to pdf or image files.

- [Requirements](#requirements)
- [Installation](#installation)
- [Getting started](#getting-started)

## Requirements

AddWatermark requires the following:

- PHP 8.1+
- [GD extension](http://php.net/manual/en/book.image.php) 

## Installation

AddWatermark is installed via [Composer](https://getcomposer.org/).
To [add a dependency](https://getcomposer.org/doc/04-schema.md#package-links) to AddWatermark in your project, either

Run the following to use the latest stable version
```sh
composer require tuckdesign/add-watermark
```
or if you want the latest unreleased version
```sh
composer require tuckdesign/add-watermark
```

## Getting started

The following is a basic usage example of the AddWatermark library.

```php
<?php
require_once 'vendor/autoload.php';

$addWatermark = new \tuckdesign\AddWatermark('myfile.pdf', 'CONFIDENTIAL', 100, 100, 40, 45);
$newFile = $addWatermark->getOutputFile();

```
