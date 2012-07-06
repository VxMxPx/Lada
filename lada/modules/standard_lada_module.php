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
		$tag = preg_replace_callback('/{(.*?)}/', array($this, 'encodeScript'), $tag);

		# Now we can break it at first space (if exists)
		$tagToTwo = explode(' ', $tag, 2);
		$tagSelf = $tagToTwo[0];
		$tagText = isset($tagToTwo[1]) ? $tagToTwo[1] : null;

		# Get pure tag now
		if (in_array(substr($tagSelf, 0, 1), array('.', '#'))) {
			$pureTag = 'div';
		}
		elseif (preg_match('/([a-z0-9]+)/', $tagSelf, $match)) {
			$pureTag = $match[0];
		}
		else {
			throw new LadaException('Invalid tag defintion: ' . $tag, 1);
		}

		# Get classes
		preg_match_all('/\.([a-zA-Z0-9_\-]+)/', $tagSelf, $classes);
		$classes = isset($classes[1]) && !empty($classes[1]) ? 'class="'.implode(' ', $classes[1]).'"' : false;

		# Get id
		preg_match('/#([a-zA-Z0-9_\-]+)/', $tagSelf, $id);
		$id = isset($id[1]) && !empty($id[1]) ? 'id="'.$id[1].'"' : false;

		# Get attributes
		$attr = explode(':', $tagSelf);
		unset($attr[0]);
		if ($classes) $attr[] = $classes;
		if ($id)      $attr[] = $id;
		$attr = implode(' ', $attr);

		# Restore attributes now
		$attr = preg_replace_callback('/QUTENCS_(.*?)_QUTENCS/', array($this, 'decodeQuotes'), $attr);
		$attr = preg_replace_callback('/SCRENCS_(.*?)_SCRENCS/', array($this, 'decodeScript'), $attr);

		# Build tag
		$finalTag = '<' . $pureTag . ($attr ? ' ' . $attr : '') . (in_array($pureTag, $this->selfClosingTags) ? ' />' : '>' . $tagText);

		# Finally return tag
		return array(
			'open' => $finalTag,
			'end'  => (in_array($pureTag, $this->selfClosingTags) ? false : '</' . $pureTag . '>')
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

		$return = '"<?php ';
		$return .= $script;
		$return .= '; ?>"';

		return $return;
	}
}