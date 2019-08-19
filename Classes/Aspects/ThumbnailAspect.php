<?php
namespace MOC\ImageOptimizer\Aspects;

use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Exception;
use Neos\Eel\Utility;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Thumbnail;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ThumbnailAspect
{
    /**
     * @var LoggerInterface
     * @Flow\Inject
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var PackageManager
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
     * @param JoinPointInterface $joinPoint The current join point
     * @return void
     * @throws Exception
     * @throws UnknownPackageException
     */
    public function optimizeThumbnail(JoinPointInterface $joinPoint)
    {
        /** @var Thumbnail $thumbnail */
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
            $this->systemLogger->info(sprintf('Unsupported type "%s" skipped in optimizeThumbnail', $imageType), LogEnvironment::fromMethodName(__METHOD__));
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

        $this->systemLogger->log($failed ? LogLevel::ERROR : LogLevel::INFO, $cmd . ' (' . ($failed ? 'Error: ' . $result : 'OK') . ')', array_merge(LogEnvironment::fromMethodName(__METHOD__), $output));
    }
}
