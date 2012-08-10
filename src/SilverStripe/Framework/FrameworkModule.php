<?php
/**
 * @package framework
 */

namespace SilverStripe\Framework;

use SilverStripe\Framework\Core\ModuleInterface;

/**
 * The framework module is the module object for the actual framework itself.
 *
 * @package framework
 */
class FrameworkModule implements ModuleInterface {

	const VERSION = '3.1.0-dev';

	public function getName() {
		return 'framework';
	}

	public function getPath() {
		return dirname(dirname(dirname(__DIR__)));
	}

	public function getAssetDirs() {
		return array(
			'css',
			'images',
			'javascript',
			'thirdparty',
			'admin/css',
			'admin/images',
			'admin/javascript',
			'admin/thirdparty'
		);
	}

}
