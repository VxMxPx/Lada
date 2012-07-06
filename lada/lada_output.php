<?php

namespace Avrelia;

/**
 * Lada
 * ----
 * Lada, Simple markup language (output).
 * ----
 * @package    Lada
 * @author     Marko Gajst
 * @link       https://github.com/VxMxPx/Lada
 * @license    https://github.com/VxMxPx/Lada
 * @version    0.10 (beta)
 */
class LadaOutput
{
	# Filename for input file
	protected $filename;

	# Processed ladaCode
	protected $ladaCode;

	/**
	 * Constructor will set filename and ladaCode array
	 * --
	 * @param array  $ladaCode An array of processed lada code
	 * @param string $filename Filename from which code was read
	 */
	public function __construct($ladaCode, $filename)
	{
		$this->ladaCode = $ladaCode;
		$this->filename = $filename;
	}

	/**
	 * Return code as an example, meaning the tags will be escaped - the source will be displayed on screen
	 * --
	 * @param boolean $pre Should code be wrapped in <pre> tags
	 * --
	 * @return string
	 */
	public function asExample($pre=true)
	{
		$code = str_replace(
			"\n", 
			'<br />', 
			str_replace(
				array('<', '>'), 
				array('&lt;', '&gt;'), 
				$this->asString()
			)
		);
		
		return $pre ? "<pre>{$code}</pre>" : $code;
	}

	/**
	 * Return processed lada code as string
	 * --
	 * @return string
	 */
	public function asString()
	{
		return implode("\n", $this->ladaCode);
	}

	/**
	 * Save code to file
	 * --
	 * @param string $filename Filename with full absolute path. If filename is false,
	 *                         and you loaded original lada code from file (instead of passing string in),
	 *                         then the path and name will be used from original (extension php will be added)
	 * --
	 * @return boolean True if successfully
	 */
	public function toFile($filename=false)
	{
		# See if we have valid filename
		if (!$filename) {
			if (!$this->filename) {
				throw new LadaException("Filename not provided, can't continue!");
			}
			else {
				$filename = $this->filename;
				if (strtolower(substr($filename, -5)) === '.lada') {
					$filename = substr($filename, 0, -5) . '.php';
				}
				elseif (strtolower(substr($filename, -4)) !== '.php') {
					$filename .= '.php';
				}
			}
		}

		# Check if directory exists
		if (!is_dir(dirname($filename))) {
			throw new LadaException('Directory not found: ' . dirname($filename));
		}

		return file_put_contents($filename, $this->asString()) !== false ? true : false;
	}

	/**
	 * Return processed lada code as an array
	 * --
	 * @return array
	 */
	public function asArray()
	{
		return $this->ladaCode;
	}
}