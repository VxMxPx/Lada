<?php

namespace Avrelia;

/**
 * Lada
 * ----
 * Lada, Simple markup language
 * Standard Lada Module, HTML + PHP
 * ----
 * @package    Lada
 * @author     Marko Gajst
 * @link       https://github.com/VxMxPx/Lada
 * @license    https://github.com/VxMxPx/Lada
 * @version    0.10 (beta)
 */
class StandardLadaModule extends LadaModule implements LadaModuleInterface
{
	# List of self-closing tags
	protected $selfClosingTags = array(
		'area', 'base', 'br', 'col', 'command', 'embed', 
		'hr', 'img', 'input', 'keygen', 'link', 'meta', 
		'param', 'source', 'track', 'wbr',
	);

	# The content of tags bellow won't be processed
	protected $ignoreContentTags = array(
		'style', 'script'
	);

	/**
	 * Standard introductory method
	 * --
	 * @return	array
	 */
	public function introduce()
	{
		return array(
			'id' => __CLASS__,
			'name' => 'HTML Lada Module',
			'author' => 'Marko Gajst',
			'version' => '0.10',
		);
	}

	/**
	 * Register patterns
	 * --
	 * @return	array
	 */
	public function register()
	{
		return array(
			# Register standard HTML tags
			'/(^[\.#a-z]+[a-z0-9]*)(.*)/' => array($this, 'htmlTag'),
			
			# PHP tags
			'/(^[\-|=] )(.*)/' => array($this, 'phpTag'),

			# DOCTYPE tag
			'/^!doctype(.*)/i' => array($this, 'docType'),
		);
	}

	/**
	 * Resolve doctype tag
	 * --
	 * @param	array	$match
	 * --
	 * @return	array
	 */
	public function docType($match) {
		return array(
			'open' => '<'.$match[0].'>',
			'end'  => false
		);
	}

	/**
	 * Resolve PHP tag
	 * --
	 * @param  array $match
	 * --
	 * @return array
	 */
	public function phpTag($match)
	{
		# Assign match to tag
		$tag = $match[0];

		# Do we have simple tag?
		if (substr($tag, 0, 1) === '=') {
			return $this->phpReturner('echo ' . substr($tag, 2) . ';');
		}

		# Do we have else?
		if (substr($tag, 0, 6) === '- else') {
			return $this->phpReturner('else {', '}');
		}

		# Process furthermore
		if (preg_match(
				'/^- (if|foreach|for|dowhile|do while|else if|elseif|elif) (.+)/i', 
				$tag, $match)) {
			if ($match[1] === 'dowhile' || $match[1] === 'do while') {
				return $this->phpReturner('do {', '} while(' . $match[2] . ');');
			}
			else {
				if ($match[1] === 'elif') {
					$match[1] = 'else if';
				}
				return $this->phpReturner($match[1] . ' (' . $match[2] . ') {', '}');
			}
		}

		# Regular tag perhaps?
		return $this->phpReturner(substr($tag, 2).';');
	}

	/**
	 * Return properly formatter array. PHP helper
	 * --
	 * @param  string $phpTag
	 * --
	 * @return array
	 */
	protected function phpReturner($phpTag, $endTag=false)
	{
		return array(
			'open'   => '<?php ' . $phpTag . ' ?>',
			'end'    => $endTag ? '<?php ' . $endTag . ' ?>' : false,
			'ignore' => false
		);
	}

	/**
	 * Resolve HTML tag
	 * --
	 * @param	array	$match
	 * --
	 * @return	array
	 */
	public function htmlTag($match)
	{
		# Assign match to tag
		$tag = $match[0];

		# First encode any quotes (")
		$tag = preg_replace_callback('/"(.*?)"/', array($this, 'encodeQuotes'), $tag);

		# Encode any scripts calls
		$tag = preg_replace_callback('/(?<!\\\){(.+?)(?<!\\\)}/', array($this, 'encodeScript'), $tag);

		# Now we can break it at first space (if exists)
		$tagToTwo = explode(' ', $tag, 2);
		$tagSelf = $tagToTwo[0];
		$tagText = isset($tagToTwo[1]) ? $tagToTwo[1] : null;

		# Check if we have such notation > perhaps
		if (substr($tagText, 0, 2) === '> ') {
			$tagText = substr($tagText, 2);
			$tagText = $this->htmlTag(array($tagText));
			$tagText = $tagText['open'] . $tagText['end'];
		}

		# Get pure tag now
		if (in_array(substr($tagSelf, 0, 1), array('.', '#'))) {
			$pureTag = 'div';
		}
		elseif (preg_match('/([a-z0-9]+)/', $tagSelf, $match)) {
			$pureTag = $match[0];
		}
		else {
			throw new LadaException('Invalid tag definition: ' . $tag, 1);
		}

		# Will remove pure tag tag from list
		if (substr($tagSelf, 0, strlen($pureTag)) === $pureTag) {
			$tagSelf = substr($tagSelf, strlen($pureTag));
		}

		# Now we'll get all classes, ids and atteibutes :)
		$classes = array();
		$id = '';
		$attributes = array();

		$currentType = false;
		$currentValue = '';
		
		if (!empty($tagSelf)) {
			# We set +1 since we wanna register last item also
			for ($i=0; $i < strlen($tagSelf)+1; $i++) {
				if (!isset($tagSelf[$i]) || in_array($tagSelf[$i], array('.', ':', '#'))) {
					if ($currentType === 'class') {
						$classes[] = $currentValue;
					}
					elseif ($currentType === 'id') {
						$id = $currentValue;
					}
					elseif ($currentType === 'attribute') {
						$attributes[] = $currentValue;
					}
					$currentValue = '';

					# Break out here
					if (!isset($tagSelf[$i])) {
						break;
					}

					if ($tagSelf[$i] === '.') $currentType = 'class';
					if ($tagSelf[$i] === '#') $currentType = 'id';
					if ($tagSelf[$i] === ':') $currentType = 'attribute';

					continue;
				}
				$currentValue .= $tagSelf[$i];
			}
		}

		# Connect classes, id, attributes
		$classes = !empty($classes) ? 'class="'.implode(' ', $classes).'"' : false;
		$id = !empty($id) ? 'id="'.$id.'"' : false;
		$attr = !empty($attributes) ? implode(' ', $attributes) : '';

		# Restore attributes now...
		if ($attr) {
			$attr = preg_replace_callback('/QUTENCS_(.*?)_QUTENCS/', array($this, 'decodeQuotes'), $attr);
			$attr = preg_replace_callback('/SCRENCS_(.*?)_SCRENCS/', array($this, 'decodeScriptAttr'), $attr);
		}

		# Build tag
		$finalTag = 
			'<' . 
			$pureTag . 
			($id ? ' ' . $id : '') .
			($classes ? ' ' . $classes : '') .
			($attr ? ' ' . $attr : '') . 
			(in_array($pureTag, $this->selfClosingTags) ? ' />' : '>' . $tagText);

		# Restore the rest of tag
		$finalTag = preg_replace_callback('/QUTENCS_(.*?)_QUTENCS/', array($this, 'decodeQuotes'), $finalTag);
		$finalTag = preg_replace_callback('/SCRENCS_(.*?)_SCRENCS/', array($this, 'decodeScript'), $finalTag);

		# Restore some \{
		$finalTag = str_replace(array('\{', '\}'), array('{', '}'), $finalTag);

		# Finally return tag
		return array(
			'open'   => $finalTag,
			'end'    => (in_array($pureTag, $this->selfClosingTags) ? false : '</' . $pureTag . '>'),
			'ignore' => in_array($pureTag, $this->ignoreContentTags) ? true : false
		);
	}

	# Helper method to encode quotes in attributes
	protected function encodeQuotes($match)
	{
		return 'QUTENCS_' . base64_encode($match[1]) . '_QUTENCS';
	}

	# Helper method to decode quotes in attributes
	protected function decodeQuotes($match)
	{
		return '"' . base64_decode($match[1]) . '"';
	}

	# Helper method to encode script (PHP) in attributes
	protected function encodeScript($match)
	{
		return 'SCRENCS_' . base64_encode($match[1]) . '_SCRENCS';
	}

	# Helper method to decode script (PHP) in attributes
	protected function decodeScript($match)
	{
		$script = base64_decode($match[1]);
		if (substr($script, 0, 1) === '-') {
			$script = substr($script, 1);
		}
		else {
			$script = 'echo ' . $script;
		}

		$return = '<?php ';
		$return .= $script;
		$return .= '; ?>';

		return $return;
	}

	# Helper method to decode script (PHP) in attributes
	protected function decodeScriptAttr($match)
	{
		$return = $this->decodeScript($match);
		return '"' . $return . '"';
	}
}