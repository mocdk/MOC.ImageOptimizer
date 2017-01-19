<?php
namespace MOC\ImageOptimizer\Aspects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ThumbnailAspect
{
    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * After a thumbnail has been refreshed the resource is optimized, meaning the
     * image is only optimized once when created.
     *
     * A new resource is generated for every thumbnail, meaning the original is
     * never touched.
     *
     * Only local file system target is supported to keep it from being blocking.
     * It would however be possible to create a local copy of the resource,
     * process it, import it and set that as the thumbnail resource.
     *
     * @Flow\AfterReturning("method(Neos\Media\Domain\Model\Thumbnail->refresh())")
     * @param \Neos\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function optimizeThumbnail(JoinPointInterface $joinPoint)
    {
        /** @var \TYPO3\Media\Domain\Model\Thumbnail $thumbnail */
        $thumbnail = $joinPoint->getProxy();
        $thumbnailResource = $thumbnail->getResource();
        if (!$thumbnailResource) {
            return;
        }

        $streamMetaData = stream_get_meta_data($thumbnailResource->getStream());
        $pathAndFilename = $streamMetaData['uri'];

        $useGlobalBinary = $this->settings['useGlobalBinary'];
        $binaryRootPath = 'Private/Library/node_modules/';
        $file = escapeshellarg($pathAndFilename);
        $imageType = $thumbnailResource->getMediaType();
        switch ($imageType) {
            case 'image/jpeg':
                if ($this->settings['formats']['jpg']['enabled'] === false) {
                    return;
                }
                $library = 'jpegtran';
                $binaryPath = sprintf('%1$s-bin/vendor/%s', $library);
                $arguments = sprintf('-copy none -optimize %s -outfile %s %s', $this->settings['formats']['jpg']['progressive'] === true ? '-progressive' : '', $file, $file);
                if ($this->settings['formats']['jpg']['useGlobalBinary'] === true) {
                    $useGlobalBinary = true;
                }
            break;
            case 'image/png':
                if ($this->settings['formats']['png']['enabled'] === false) {
                    return;
                }
                $library = 'optipng';
                $binaryPath = sprintf('%1$s-bin/vendor/%s', $library);
                $arguments = sprintf('-o%u -strip all -out %s %s', $this->settings['formats']['png']['optimizationLevel'], $file, $file);
                if ($this->settings['formats']['png']['useGlobalBinary'] === true) {
                    $useGlobalBinary = true;
                }
            break;
            case 'image/gif':
                if ($this->settings['formats']['gif']['enabled'] === false) {
                    return;
                }
                $library = 'gifsicle';
                $binaryPath = sprintf('%1$s/vendor/%1$s', $library);
                $arguments = sprintf('--batch -O%u %s ', $this->settings['formats']['gif']['optimizationLevel'], $file);
                if ($this->settings['formats']['gif']['useGlobalBinary'] === true) {
                    $useGlobalBinary = true;
                }
            break;
            case 'image/svg+xml':
                if ($this->settings['formats']['svg']['enabled'] === false) {
                    return;
                }
                $library = 'svgo';
                $binaryPath = sprintf('%1$s/bin/%1$s', $library);
                $arguments = sprintf('%s %s', $this->settings['formats']['svg']['pretty'] === true ? '--pretty' : '', $file);
                if ($this->settings['formats']['svg']['useGlobalBinary'] === true) {
                    $useGlobalBinary = true;
                }
            break;
            default:
                $this->systemLogger->log(sprintf('Unsupported type "%s" skipped in optimizeThumbnail', $imageType), LOG_INFO);
                return;
                break;
        }
        $binaryPath = $useGlobalBinary === true ? $this->settings['globalBinaryPath'] . $library : $this->packageManager->getPackage('MOC.ImageOptimizer')->getResourcesPath() . $binaryRootPath . $binaryPath;
        $cmd = escapeshellcmd($binaryPath) . ' ' . $arguments;
        $output = [];
        exec($cmd, $output, $result);
        $this->systemLogger->log($cmd . ' (' . ((int)$result === 0 ? 'OK' : 'Error: ' . $result) . ')', LOG_INFO, $output);
    }
}
