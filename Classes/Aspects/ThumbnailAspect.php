<?php
namespace MOC\ImageOptimizer\Aspects;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Resource\ResourceManager;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ThumbnailAspect {

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
	public function injectSettings(array $settings) {
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
	 * @Flow\AfterReturning("method(TYPO3\Media\Domain\Model\Thumbnail->refresh())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 */
	public function optimizeThumbnail(JoinPointInterface $joinPoint) {
		/** @var \TYPO3\Media\Domain\Model\Thumbnail $thumbnail */
		$thumbnail = $joinPoint->getProxy();
		$thumbnailResource = $thumbnail->getResource();

		$streamMetaData = stream_get_meta_data($thumbnailResource->getStream());
		$pathAndFilename = $streamMetaData['uri'];

		$useGlobalBinary = $this->settings['useGlobalBinary'];
		$binaryRootPath = 'Private/Library/node_modules/';
		$file = escapeshellarg($pathAndFilename);
		$imageType = $thumbnailResource->getMediaType();
		switch ($imageType) {
			case 'image/jpeg':
				if ($this->settings['formats']['jpg']['enabled'] === FALSE) {
					return;
				}
				$library = 'jpegtran';
				$binaryPath = sprintf('%1$s-bin/vendor/%s', $library);
				$arguments = sprintf('-copy none -optimize %s -outfile %s %s', $this->settings['formats']['jpg']['progressive'] === TRUE ? '-progressive' : '', $file, $file);
				if ($this->settings['formats']['jpg']['useGlobalBinary'] === TRUE) {
					$useGlobalBinary = TRUE;
				}
				break;
			case 'image/png':
				if ($this->settings['formats']['png']['enabled'] === FALSE) {
					return;
				}
				$library = 'optipng';
				$binaryPath = sprintf('%1$s-bin/vendor/%s', $library);
				$arguments = sprintf('-o%u -strip all -out %s %s', $this->settings['formats']['png']['optimizationLevel'], $file, $file);
				if ($this->settings['formats']['png']['useGlobalBinary'] === TRUE) {
					$useGlobalBinary = TRUE;
				}
				break;
			case 'image/gif':
				if ($this->settings['formats']['gif']['enabled'] === FALSE) {
					return;
				}
				$library = 'gifsicle';
				$binaryPath = sprintf('%1$s/vendor/%1$s', $library);
				$arguments = sprintf('--batch -O%u %s ', $this->settings['formats']['gif']['optimizationLevel'], $file);
				if ($this->settings['formats']['gif']['useGlobalBinary'] === TRUE) {
					$useGlobalBinary = TRUE;
				}
				break;
			case 'image/svg+xml':
				if ($this->settings['formats']['svg']['enabled'] === FALSE) {
					return;
				}
				$library = 'svgo';
				$binaryPath = sprintf('%1$s/bin/%1$s', $library);
				$arguments = sprintf('%s %s', $this->settings['formats']['svg']['pretty'] === TRUE ? '--pretty' : '', $file);
				if ($this->settings['formats']['svg']['useGlobalBinary'] === TRUE) {
					$useGlobalBinary = TRUE;
				}
				break;
		}
		$binaryPath = $useGlobalBinary === TRUE ? $this->settings['globalBinaryPath'] . $library : $this->packageManager->getPackageOfObject($this)->getResourcesPath() . $binaryRootPath . $binaryPath;
		$cmd = escapeshellcmd($binaryPath) . ' ' . $arguments;
		$output = [];
		exec($cmd, $output, $result);
		$this->systemLogger->log($cmd . ' (' . ((int)$result === 0 ? 'OK' : 'Error: ' . $result) . ')', LOG_INFO, $output);
	}

}
