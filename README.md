MOC.ImageOptimizer
==================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mocdk/MOC.ImageOptimizer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mocdk/MOC.ImageOptimizer/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/moc/imageoptimizer/v/stable)](https://packagist.org/packages/moc/imageoptimizer)
[![Total Downloads](https://poser.pugx.org/moc/imageoptimizer/downloads)](https://packagist.org/packages/moc/imageoptimizer)
[![License](https://poser.pugx.org/moc/imageoptimizer/license)](https://packagist.org/packages/moc/imageoptimizer)

Introduction
------------

Neos CMS / Flow framework package that optimizes generated thumbnail images (jpg, png, gif, svg) for web presentation.

Original files are never affected since copies are always created for thumbnails.

Non-blocking during rendering (asynchronous) optimization.

Using jpegtran, optipng, gifsicle and svgo for the optimizations.

Should work with Linux, FreeBSD, OSX, SunOS & Windows (only tested Linux & FreeBSD so far).

Compatible with Neos 1.x-2.x+ / Flow 2.x-3.x+

##### Only supports local file system (no CDN support yet)

Installation
------------

Requires npm (node.js) to work out of the box, although binaries can also be installed manually without it.

`composer require "moc/imageoptimizer" "~2.0"`

Ensure the image manipulation libraries `jpegtran` (JPG), `optipng` (PNG), `gifsicle` (GIF) and `svgo` (SVG) are installed globally. Libraries can be skipped if desired.

Alternatively install them using `npm`:
```
# Globally
npm install -g jpegtran-bin optipng-bin gifsicle svgo

# Locally
npm install --prefix Packages/Libraries/MOC.ImageOptimizer/Resources/Private/Library
```

Configuration
-------------

Using the `Settings` configuration, multiple options can be adjusted.

Optimization can be disabled for specific file formats.

Additionally options for optimization level (png & gif), progressive (jpg), pretty (svg) can be adjusted.

Usage of global available binaries can be configured instead or for specific formats.

Enable using the setting `MOC.ImageOptimizer.useGlobalBinary` and configure the path in `MOC.ImageOptimizer.globalBinaryPath`.

Usage
-----

* Clear thumbnails to generate new ones that will automatically be optimized.

`./flow media:clearthumbnails`

* See system log for debugging and error output.
