<?php

namespace Avrelia;

/**
 * Lada parser, to be returned when the lada file is parsed
 */
class LadaParser
{
	# Array of lada code
	protected $ladaCode;

	# String - raw lada code
	protected $ladaCodeRaw;

	# Indentation type (could be one tab "\t", two spaces "  "), the system will figure it out itself
	protected $indentation = false;

	# What type of indentation to use when generating HTML
	protected $outputIndentation = '';

	# List of levels
	protected $levels = array();

	# Registered patterns
	protected $register = array();

	# Current line
	protected $currentLine = false;

	# Last end tag
	protected $lastEndTag = false;

	# Ignore current content? Can be (int) parent level or false
	protected $ignore = false;


	/**
	 * Require array of lada code
	 * --
	 * @param	string	$ladaCode
	 * @param	array	$register
	 * --
	 * @return	void
	 */
	public function __construct($ladaCode, $register)
	{
		$this->ladaCodeRaw = $this->standardizeLineEndings($ladaCode);
		$this->register = $register;

		# Break code to lines
		$this->ladaCode = explode("\n", $this->ladaCodeRaw);

		# Check if we have valid array
		if (!is_array($this->ladaCode)) {
			# Will result in empty string result
			$this->ladaCode = array();
			trigger_error('Lada code: empty string passed in, nothing to process.', E_USER_WARNING);
		}

		# First let us check what type of indentation is used
		$this->checkIndentation();
	}

	/**
	 * Will figure it out indentation type
	 * --
	 * @return	void
	 */
	public function checkIndentation()
	{
		foreach ($this->ladaCode as $ladaLine) {
			if (preg_match('/^(\t|[ ])+/', $ladaLine, $match) === 1) {
				$this->indentation = $match[0];
				if (empty($this->outputIndentation)) {
					$this->outputIndentation = $this->indentation;
				}
				return;
			}
		}
	}

	/**
	 * Standardize line endings
	 * --
	 * @param	string	$input
	 * --
	 * @return	string
	 */
	protected function standardizeLineEndings($input)
	{
		return preg_replace('/\r{,2}|\n{,2}|\r\n{,2}/ism', "\n", $input);
	}
	//-

	/**
	 * Will start main process
	 * --
	 * @return	array
	 */
	public function process()
	{
		$parsed = array();

		foreach ($this->ladaCode as $ladaLine) {
			# This will stop parser immediately
			if (trim($ladaLine) === '!!STOP') {
				break;
			}

			# Determine indentation level
			$level = $this->getIndentation($ladaLine);

			# Should we ignore following lines?
			if ($this->ignore !== false) {
				if ($this->ignore >= $level) {
					$this->ignore = false;
				}
				else {
					$parsed[] = $this->putIndentation($level) . $ladaLine;
					# We need to reset last end tag here
					$this->lastEndTag = false;
					continue;
				}
			}

			# Is level less or the same as current?
			if (is_array($this->levels) && !empty($this->levels)) {
				foreach ($this->levels as $preLevel => $endTag) {
					if ($preLevel >= $level) {
						if ($endTag === $this->lastEndTag) {
							$lk = count($parsed) - 1;
							$parsed[$lk] .= $endTag;
						}
						else {
							$parsed[] = $this->putIndentation($preLevel) . $endTag;
						}
						$this->lastEndTag = false;
						unset($this->levels[$preLevel]);
					}
				}
			}

			# Trim extra spaces
			$ladaLine = ltrim($ladaLine);

			# Check if we have no-parse content inside the tag (\\)
			if (substr($ladaLine, -2, 2) === '\\\\') {
				$this->ignore = $level;
				$ladaLine = substr($ladaLine, 0, -2);
				# We continue parsing here because we'll still need 
				# to convert this line to HTML, and ignoring starts on next line.
			}

			# Check if we have one long line (end like \)
			if (substr($ladaLine, -1, 1) === '\\') {
				if ($this->currentLine === false) {
					$this->currentLine = substr($ladaLine, 0, -1);
				}
				else {
					$this->currentLine .= substr($ladaLine, 0, -1);
				}
				continue;
			}

			# Set current line back to false
			if ($this->currentLine !== false) {
				$ladaLine = $this->currentLine . $ladaLine;
				$this->currentLine = false;
			}

			# If we have prefix | we leave it as it is, no changes
			if (substr($ladaLine, 0, 2) === '| ') {
				$parsed[] = $this->putIndentation($level) . substr($ladaLine, 2);
				# We need to reset last end tag here
				$this->lastEndTag = false;
				continue;
			}

			# Loop through the registered tags
			foreach ($this->register as $pattern => $opt)
			{
				# Do we have a match?
				if (preg_match($pattern, $ladaLine, $match) === 1) {
					$mObj = $opt[0];
					$mMet = $opt[1];

					# Send it to appropriate method
					if (is_callable(array($mObj, $mMet))) {
						$matchResult = $mObj->$mMet($match);
						# Replace start tag
						$ladaLine = $matchResult['open'];

						# Should we ignore inner content? (Ignore won't be reseted)
						if ($this->ignore === false) {
							if (isset($matchResult['ignore']) && $matchResult['ignore'] === true) {
								$this->ignore = $level;
							}
						}

						# Store end tag for then we change indentation
						$this->levels[$level] = $matchResult['end'];
						$this->lastEndTag     = $matchResult['end'];
						krsort($this->levels);
						break;
					}
					else {
						trigger_error('Costume method not callable: {$mObj}->{$mMet}', E_USER_WARNING);
					}
				}
			}

			$parsed[] = $this->putIndentation($level) . $ladaLine;
		}

		# Close all open tags
		if (!empty($this->levels)) {
			foreach ($this->levels as $ki => $endTag) {
				if ($endTag === $this->lastEndTag) {
					$lk = count($parsed) - 1;
					$parsed[$lk] .= $endTag;
				}
				else {
					$parsed[] = $this->putIndentation($ki) . $endTag;
				}
				$this->lastEndTag = false;
			}
		}

		return $parsed;
	}

	/**
	 * Get indentation level for current line
	 * --
	 * @param	string	$line
	 * --
	 * @return	integer
	 */
	protected function getIndentation(&$line)
	{
		$inLength = strlen($this->indentation);
		$lnLength = strlen($line);

		for($i=0; $i<$lnLength; $i=$i+$inLength) {
			if (substr($line, $i, $inLength) !== $this->indentation) {
				break;
			}
		}

		$line = substr($line, $i);
		return $i / $inLength;
	}

	/**
	 * Calculate indentation for current level
	 * --
	 * @param  integer $currentLevel
	 * --
	 * @return string
	 */
	protected function putIndentation($currentLevel)
	{
		return str_repeat($this->outputIndentation, $currentLevel);
	}
}