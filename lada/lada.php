<?php

namespace Avrelia;

/**
 * Lada
 * ----
 * Lada, Simple markup language (parser).
 * ----
 * @package    Lada
 * @author     Marko Gajst
 * @link       https://github.com/VxMxPx/Lada
 * @license    https://github.com/VxMxPx/Lada
 * @version    0.10 (beta)
 */
class Lada
{
	# List of loaded modules
	protected $loadedModules;

	# Registered patterns
	protected $register = array();

	# Was system initialized already?
	private static $initialized = false;

	/**
	 * Check if system was initialized, and if not
	 * include all required classes (we don't use autoloader, for obvious reasons)
	 * --
	 * @return	void
	 */
	public function __construct()
	{
		if (!self::$initialized) {
			# Define basepath
			if (!defined('LADA_BASEDIR')) {
				define('LADA_BASEDIR', dirname(__FILE__));
			}
			else {
				throw new LadaException('LADA_BASEDIR seems to be define already.');
			}

			# Include base classes
			include(LADA_BASEDIR.DIRECTORY_SEPARATOR.'lada_expection.php');
			include(LADA_BASEDIR.DIRECTORY_SEPARATOR.'lada_module.php');
			include(LADA_BASEDIR.DIRECTORY_SEPARATOR.'lada_module_interface.php');

			include(LADA_BASEDIR.DIRECTORY_SEPARATOR.'lada_output.php');
			include(LADA_BASEDIR.DIRECTORY_SEPARATOR.'lada_parser.php');

			# Include default modules
			foreach (glob(LADA_BASEDIR.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'*.php') as $module) {
				include($module);
				# We'll get class name now
				$fileName = basename($module);
				$className = 'Avrelia\\' . substr($this->toCamelCase($fileName), 0, -4);

				if (class_exists($className, false)) {
					$ladaModuleObject = new $className();
					$this->addModule($ladaModuleObject);
				}
				else {
					trigger_error('Invalid module in autoload: ' . $className);
				}
			}

			# Alright, everything is initialized
			self::$initialized = true;
		}
	}

	/**
	 * Will register lada module
	 * --
	 * @param	object	$module
	 * --
	 * @return	void
	 */
	public function addModule(LadaModuleInterface $module)
	{
		$Details = $module->introduce();

		if (!isset($this->loadedModules[$Details['id']])) {
			$this->loadedModules[$Details['id']] = $module;
			$register = $module->register();
			$this->register = array_merge($this->register, $register);
		}
	}

	/**
	 * Initialize new lada parser, and return LadaOutput
	 * --
	 * @param	string	$string
	 * @param	string	$filename
	 * --
	 * @return	LadaOutput
	 */
	protected function loader($string, $filename=false)
	{
		if (empty($string) || !is_string($string)) {
			throw new LadaException('Invalid input passed.');
		}

		# New lada parser, and process text, we pass in register
		# becase parser will need it!
		$LadaParser = new LadaParser($string, $this->register);
		$processedText = $LadaParser->process();

		# Return output
		return new LadaOutput($processedText, $filename);
	}

	/**
	 * Parse string
	 * --
	 * @param	string	$string
	 * --
	 * @return	LadaOutput
	 */
	public function fromString($string)
	{
		return $this->loader($string, false);
	}

	/**
	 * Load lada code from file
	 * --
	 * @param	string	$filename
	 * --
	 * @return	LadaOutput
	 */
	public function fromFile($filename)
	{
		$filename = realpath($filename);
		if (!file_exists($filename)) {
			throw new LadaException('File not found: ' . $filename);
		}

		$contents = file_get_contents($filename);
		return $this->loader($contents, $filename);
	}

	/**
	 * Helper method, to Convert to camel case
	 * --
	 * @param	string	$string
	 * @param	boolean	$ucFirst	Upper case first letter also?
	 * --
	 * @return	string
	 */
	protected function toCamelCase($string, $ucFirst=true)
	{
		$string = str_replace('_', ' ', $string);
		$string = ucwords($string);
		$string = str_replace(' ', '', $string);

		if (!$ucFirst) {
			$string = lcfirst($string);
		}

		return $string;
	}
}