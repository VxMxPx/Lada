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

	# List of levels
	protected $levels = array();

	# Registered patterns
	protected $register = array();

	# Current line
	protected $currentLine = false;

	# Last end tag
	protected $lastEndTag = false;


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
			# Determine indentation level
			$level = $this->getIndentation($ladaLine);

			# Is level less or the same as current?
			if (is_array($this->levels) && !empty($this->levels)) {
				foreach ($this->levels as $preLevel => $endTag) {
					if ($preLevel >= $level) {
						if ($endTag === $this->lastEndTag) {
							$lk = count($parsed) - 1;
							$parsed[$lk] .= $endTag;
						}
						else {
							$parsed[] = str_repeat("  ", $preLevel) . $endTag;
						}
						unset($this->levels[$preLevel]);
					}
				}
			}

			# Trim extra spaces
			$ladaLine = ltrim($ladaLine);

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
			if (substr($ladaLine, 0, 1) === '|') {
				$parsed[] = substr($ladaLine, 1);
				continue;
			}

			# Loop through the registered tags
			foreach ($this->register as $pattern => $opt) {
				# Do we have a match?
				if (preg_match($pattern, $ladaLine, $match) === 1) {
					$mObj = $opt[0];
					$mMet = $opt[1];

					# Send it to appropriate method
					if (is_callable(array($mObj, $mMet))) {
						$matchResult = $mObj->$mMet($match);
						# Replace start tag
						$ladaLine = $matchResult['open'];

						# Store end tag for then we change indentation
						$this->levels[$level] = $matchResult['end'];
						$this->lastEndTag = $matchResult['end'];
						krsort($this->levels);
					}
					else {
						trigger_error('Costume method not callable: {$mObj}->{$mMet}', E_USER_WARNING);
					}
				}
			}

			$parsed[] = str_repeat("  ", $level) . $ladaLine;
		}

		# Close all open tags
		if (!empty($this->levels)) {
			foreach ($this->levels as $ki => $endTag) {
				if ($endTag === $this->lastEndTag) {
					$lk = count($parsed) - 1;
					$parsed[$lk] .= $endTag;
				}
				else {
					$parsed[] = str_repeat("  ", $ki) . $endTag;
				}
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

		for($i=0; $i<$lnLength; $i++) {
			if (substr($line, $i, $inLength) !== $this->indentation) {
				break;
			}
		}

		$line = substr($line, $i * $inLength);
		return $i;
	}
}