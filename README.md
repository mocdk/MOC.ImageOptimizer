MOC.ImageOptimizer
==================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mocdk/MOC.ImageOptimizer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mocdk/MOC.ImageOptimizer/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/moc/imageoptimizer/v/stable)](https://packagist.org/packages/moc/imageoptimizer)
[![Total Downloads](https://poser.pugx.org/moc/imageoptimizer/downloads)](https://packagist.org/packages/moc/imageoptimizer)
[![License](https://poser.pugx.org/moc/imageoptimizer/license)](https://packagist.org/packages/moc/imageoptimizer)

Introduction
------------

Neos CMS / Flow framework package that optimizes generated thumbnail images (jpg, png, gif, svg and more) for web presentation.

Original files are never affected since copies are always created for thumbnails.

Non-blocking during rendering (asynchronous) optimization.

Using jpegtran, optipng, gifsicle and svgo or alternative customizible ones for the optimizations.

Should work with Linux, FreeBSD, OSX, SunOS & Windows (only tested Linux & FreeBSD so far).

Compatible with Neos 1.x - 4.x / Flow 1.x - 5.x

##### Only supports local file system (no CDN support yet) (see #10)

Installation
------------

Requires npm (node.js) to work out of the box, although binaries can also be installed manually without it.

`composer require "moc/imageoptimizer" "~3.0"`

Ensure the image manipulation libraries `jpegtran` (JPG), `optipng` (PNG), `gifsicle` (GIF) and `svgo` (SVG) are installed globally. Libraries can be skipped if desired, just make sure to disable those mimetypes. 

Alternatively install them using `npm`:
```
# Globally
npm install -g jpegtran-bin optipng-bin gifsicle svgo

# Locally
npm install --prefix Packages/Application/MOC.ImageOptimizer/Resources/Private/Library
```

Configuration
-------------

Using the `Settings` configuration, multiple options can be adjusted.

Optimization can be disabled for specific file formats.

Additionally options such as optimization level (png & gif), progressive (jpg), pretty (svg) can be adjusted depending on optimization library.

Usage of global available binaries can be configured instead or for specific formats.

Enable using the setting `MOC.ImageOptimizer.useGlobalBinary` and configure the path in `MOC.ImageOptimizer.globalBinaryPath`.

Use alternative libraries for optimization
------------------------------------------

You can replace the preconfigured libraries with alternative ones.

Example:

Add the following to your `Settings` to use `jpegoptim` instead of `jpegtran`:

    MOC:
      ImageOptimizer:
        formats:
          'image/jpeg':
            enabled: true
            library: 'jpegoptim'
            binaryPath: 'jpegoptim-bin/vendor/jpegoptim'
            arguments: "${'--strip-all --max=' + quality + ' ' + (progressive ? '--all-progressive ' : '') + '-o ' + file}"
            parameters:
              progressive: true # whether or not to serve progressive jpgs
              quality: 80 # quality level (1-100)

When doing this you have to take care that you provide the necessary library yourself as it's not included 
when doing the installation like described above.

Usage
-----

* Clear thumbnails to generate new ones that will automatically be optimized.

`./flow media:clearthumbnails`

* See system log for debugging and error output.
