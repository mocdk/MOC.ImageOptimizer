MOC.ImageOptimizer
==================

TYPO3 Flow package that optimizes published images (jpg, png, gif, svg) for web presentation.

Using jpegtran, optipng, gifsicle and svgo for the optimizations.

Should work with Linux, FreeBSD, OSX, SunOS & Windows (only tested Linux & FreeBSD so far).

Works with TYPO3 Flow 1.x, 2.x

Installation
------------

Requires npm (node.js) to work out of the box, although binaries can also be installed manually without it.

composer require "moc/imageoptimizer" "1.0.*"

Run `npm install --prefix Resources/Private/Library` in the package (should happen automatically during install).

Configuration
-------------

Using the ``Settings`` configuration, multiple options can be adjusted.

Optimization can be disabled for specific file formats.

Additionally options for optimization level (png & gif), progressive (jpg), pretty (svg) can be adjusted. 

Usage of global available binaries can be configured instead or for specific formats.