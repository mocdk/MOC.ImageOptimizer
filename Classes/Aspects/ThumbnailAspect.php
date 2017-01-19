<?php
namespace MOC\ImageOptimizer\Aspects;

use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Utility;
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
     * @Flow\Inject
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

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
        /** @var \Neos\Media\Domain\Model\Thumbnail $thumbnail */
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

        if (!array_key_exists($imageType, $this->settings['formats'])) {
            $this->systemLogger->log(sprintf('Unsupported type "%s" skipped in optimizeThumbnail', $imageType), LOG_INFO);
            return;
        }

        $librarySettings = $this->settings['formats'][$imageType];

        if ($librarySettings['enabled'] === false) {
            return;
        }

        if ($librarySettings['useGlobalBinary'] === true) {
            $useGlobalBinary = true;
        }

        $library = $librarySettings['library'];
        $binaryPath = $librarySettings['binaryPath'];
        $eelExpression = $librarySettings['arguments'];
        $parameters = array_merge($librarySettings['parameters'], ['file' => $file]);
        $arguments = Utility::evaluateEelExpression($eelExpression, $this->eelEvaluator, $parameters);

        $binaryPath = $useGlobalBinary === true ? $this->settings['globalBinaryPath'] . $library : $this->packageManager->getPackage('MOC.ImageOptimizer')->getResourcesPath() . $binaryRootPath . $binaryPath;
        $cmd = escapeshellcmd($binaryPath) . ' ' . $arguments;
        $output = [];
        exec($cmd, $output, $result);
        $failed = (int)$result !== 0;
        $this->systemLogger->log($cmd . ' (' . ($failed ? 'Error: ' . $result : 'OK') . ')', $failed ? LOG_ERR : LOG_INFO, $output);
    }
}
