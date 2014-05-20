<?php
/*
* Smarty plugin
* -------------------------------------------------------------
* File: outputfilter.merge.php
* Type: outputfilter
* Name: Merge javascript files
* Purpose: Combines all javascript files into one
* -------------------------------------------------------------
*/
function smarty_outputfilter_merge($output, Smarty_Internal_Template $smarty) {
	include_once($_SERVER['DOCUMENT_ROOT'] . '/Merge.php');

	$files = array('css' => array(), 'js' => array());
	$tag = false;
	$file = '';

	preg_match_all('/<script(.*?)<\/script>|<meta(.*?)>|<link(.*?)>/', $output, $condition);

	foreach ($condition[0] as $script) {
		/**
		 * Ignore remote files
		 */
		if (stripos($script, 'http') !== false || stripos($script, 'www') !== false
			|| (stripos($script, 'src') === false && stripos($script, 'href') === false)) {
			continue;
		}

		//# CSS
		if (strtolower(substr($script, 1, 4)) == 'link') {
			//# Ignore stylesheet
			if (stripos($script, 'stylesheet') === false) { continue; }

			$values = preg_match('/href="(.*?)"/', $script, $match);
			if (count($match) > 0) {
				$files['css'][] = $match[1];
			}

		} else { //# Javascript

			$values = preg_match('/src="(.*?)"/', $script, $match);
			if (count($match) > 0) {
				$files['js'][] = $match[1];
			}

		}

		if (!$tag) {
			$output = str_replace($script, '%%MERGE%%', $output);
			$tag = true;
		} else {
			$output = str_replace($script, '', $output);
		}
	}

	$isUtf8 = false;
	foreach ($condition[2] as $meta) {
		if (!$meta) { continue; }

		if (stripos($meta, 'UTF-8') !== false && stripos($meta, 'Content-Type') !== false) {
			$isUtf8 = true;
			break;
		}
	}

	if (count($files['js']) > 0) {
		$file .= '<script type="text/javascript" language="javascript" src="'.Merge::javascript($files['js'], $isUtf8).'"></script>
		';
	}
	if (count($files['css']) > 0) {
		$file .= '<link rel="stylesheet" type="text/css" href="'.Merge::css($files['css'], $isUtf8).'" />
		';
	}

	$output = str_replace('%%MERGE%%', $file, $output);

	return $output;
}