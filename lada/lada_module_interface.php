<?php

namespace Avrelia;

interface LadaModuleInterface
{
	/**
	 * Expected response is array with some details about module.
	 * array('id' => __CLASS__, 'name' => 'Module Name', 'version' => '0.10', 'author' => 'Marko Gajst')
	 * --
	 * @return	array
	 */
	function introduce();

	/**
	 * Must return list of particular patterns which this module will handle.
	 * They must be regular expression format: 
	 * 		'/- (if)(.*?)/i' => array(
	 *			'object' => $this, // which object to call
	 *			'method' => 'method_name', // which method should be called on match
	 * 			'ignore' => false // should we ignore (not parse it) the content of this tag?
	 * The method will get passed in an array of matches as defined by regular expression.
	 * --
	 * @return	array
	 */
	function register();
}