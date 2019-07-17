<?php
	function wikiprotip_wp_head() {
		// Set plugin URL
		$url = defined('WP_PLUGIN_URL') ? WP_PLUGIN_URL . '/wp-wikiprotip' : get_bloginfo('wpurl') . '/wp-content/plugins/wp-wikiprotip';

		// Is this really the best way to do this? :S
		$url = empty($_SERVER['HTTPS']) ? $url : str_replace('http://', 'https://', $url);

		// get plugins options
		$options = get_option( 'wikiprotip_wp_options', array() );

		// Header stuff. Assumes we already have jQuery. Don't use $ as this is probably used by something else.
		echo "\n\n<!-- Wiki ProTip Plugin -->\n";
		echo "<link rel='stylesheet' type='text/css' href='{$url}/assets/wikiprotip.css' media='screen' />";
		echo "<script type='text/javascript' src='{$url}/assets/jquery.protip.js'></script>\n";
		echo "<script type='text/javascript'>\n";
		echo "\tjQuery(document).ready(function($){\n";
		echo "\t\t$('.wikiprotip').protip({ 'tip': function() { return $(this).next(); } });\n";
		echo "\t});\n";
		echo "</script>\n";
		echo "<!-- End of Wiki ProTip Plugin -->\n\n";
	}

	// Shortcode function for wiki tag; sample [wiki]Pink Floyd[/wiki]
	function wikiprotip_wp_tags($atts, $content = null) {

		// Defaults
		$defaults = array(
			'len'=>200,
			'para_skip'=>0,
			'label'=>null,
			'nolookup'=>false
		);

		// Atts
		$atts = array_merge($defaults, (array)$atts);

		if ($atts['nolookup'] === false) {
			$wiki_content = wikiprotip_wiki_data($content, $atts['para_skip']);
		}
		else {
			$wiki_content = null;
		}


		$wiki_title = wiki_url($content);

		// Truncate content
		$max_len = $atts['len'];
		if (strlen($wiki_content) > $max_len) {
			$wiki_content = substr($wiki_content, 0, $max_len) . '...';
		}

		// Link if no content, tooltip if there is
		if (!is_null($wiki_content))
			return '<a href="http://en.wikipedia.org/wiki/'.$wiki_title.'" class="wikiprotip" target="_blank">'.(is_null($atts['label']) ? $content : $atts['label']).'</a><span class="wikiprotip-tip"><b>Wikipedia:</b> '.$wiki_content.'</span>';

		return '<a href="http://en.wikipedia.org/wiki/'.$wiki_title.'" target="_blank">'.(is_null($atts['label']) ? $content : $atts['label']).'</a>';
	}

	// Get content from wikipedia
	function wikiprotip_wiki_data($title, $para_skip) {
		// Cache?
		if (function_exists('apc_fetch')) {
			$apc_key_name = 'wikipedia-'.wiki_url($title).'-p'.$para_skip;
			$cached = apc_fetch($apc_key_name, $success);
			if ($success) {
				return $cached;
			}
		}

		// Get it
		$content = download_pretending("http://en.wikipedia.org/wiki/".wiki_url($title));

		// Domdoc it
		$dom = @DOMDocument::loadHTML($content);

		// Xpath from dom
		$xpath = new domXPath($dom);

		// Para
		$result = $xpath->query('//div[@id="mw-content-text"]/div/p');

		// Skip counter
		$skipcnt = 0;
		$val = null;

		foreach ($result as $node) {
			// Get the node into a new document
			$tmpDoc = new DOMDocument();
			$tmpDoc->appendChild($tmpDoc->importNode($node, true));
			// Get an xpath object for the node's doc
			$tmpXpath = new domXPath($tmpDoc);
			// Look for a span id coordinates
			$tmpResult = $tmpXpath->query('//span[@id="coordinates"]');

			// Skip to next node if span exists
			if ($tmpResult->length)
				continue;

			// Skip empty paragraphs
			if (!trim($val))
				continue;

			// Skip $para_skip paragraphs
			if ($skipcnt++ < $para_skip)
				continue;

			// Return value
			$val = $node->nodeValue;

			break;
		}

		// Cache for 1d
		if (function_exists('apc_store')) {
			apc_store($apc_key_name, $val, 86400);
		}

		return $val;
	}

	// Get title in a Wiki friendly way
	function wiki_url($title) {
		$title = str_replace(' ', '_', $title);

		return $title;
	}

	// Curl UA spoofer
	function download_pretending($url, $user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}
?>
