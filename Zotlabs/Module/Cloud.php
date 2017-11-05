<?php
namespace Zotlabs\Module;
/**
 * @file Zotlabs/Module/Cloud.php
 * @brief Initialize Hubzilla's cloud (SabreDAV).
 *
 * Module for accessing the DAV storage area.
 */

use Sabre\DAV as SDAV;
use \Zotlabs\Storage;

// composer autoloader for SabreDAV
require_once('vendor/autoload.php');

require_once('include/attach.php');


/**
 * @brief Cloud Module.
 *
 */
class Cloud extends \Zotlabs\Web\Controller {

	/**
	 * @brief Fires up the SabreDAV server.
	 *
	 */
	function init() {

		if (! is_dir('store'))
			os_mkdir('store', STORAGE_DEFAULT_PERMISSIONS, false);

		$which = null;
		if (argc() > 1)
			$which = argv(1);

		$profile = 0;

		if ($which)
			profile_load( $which, $profile);

		$auth = new \Zotlabs\Storage\BasicAuth();

		$ob_hash = get_observer_hash();

		if ($ob_hash) {
			if (local_channel()) {
				$channel = \App::get_channel();
				$auth->setCurrentUser($channel['channel_address']);
				$auth->channel_id = $channel['channel_id'];
				$auth->channel_hash = $channel['channel_hash'];
				$auth->channel_account_id = $channel['channel_account_id'];
				if($channel['channel_timezone'])
					$auth->setTimezone($channel['channel_timezone']);
			}
			$auth->observer = $ob_hash;
		}

		// if we arrived at this path with any query parameters in the url, build a clean url without
		// them and redirect.
		// @fixme if the filename has an ampersand in it AND there are query parameters, 
		// this may not do the right thing. 

		if((strpos($_SERVER['QUERY_STRING'],'?') !== false) || (strpos($_SERVER['QUERY_STRING'],'&') !== false && strpos($_SERVER['QUERY_STRING'],'&amp;') === false)) {
			$path = z_root();
			if(argc()) {
				foreach(\App::$argv as $a) {
					$path .= '/' . $a;
				}
			}
			goaway($path);
		}


		$rootDirectory = new \Zotlabs\Storage\Directory('/', $auth);

		// A SabreDAV server-object
		$server = new SDAV\Server($rootDirectory);
		// prevent overwriting changes each other with a lock backend
		$lockBackend = new SDAV\Locks\Backend\File('store/[data]/locks');
		$lockPlugin = new SDAV\Locks\Plugin($lockBackend);

		$server->addPlugin($lockPlugin);

		$is_readable = false;

		// provide a directory view for the cloud in Hubzilla
		$browser = new \Zotlabs\Storage\Browser($auth);
		$auth->setBrowserPlugin($browser);

		$server->addPlugin($browser);

		// Experimental QuotaPlugin
	//	require_once('\Zotlabs\Storage/QuotaPlugin.php');
	//	$server->addPlugin(new \Zotlabs\Storage\\QuotaPlugin($auth));

//		ob_start();
		// All we need to do now, is to fire up the server
		$server->exec();

//		ob_end_flush();
		if($browser->build_page)
			construct_page();
		killme();
	}

}
