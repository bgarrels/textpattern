<?php

/*
Copyright 2006 Alex Shiels http://thresholdstate.com/

$HeadURL$
$LastChangedRevision$
*/

// --------------------------------------------------------------
	function EvalElse($thing, $condition)
	{
		$f = '@(</?txp:\S+\b.*(?:/)?(?<!\\\\)>)@sU';

		$parsed = preg_split($f, $thing, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		$tagpat = '@^<(/?)txp:(\w+).*?(/?)(?<!\\\\)>$@';

		$parts = array(0 => '', 1 => '');
		$in = 0;
		$level = 0;
		foreach ($parsed as $chunk) {
			if (preg_match($tagpat, $chunk, $m)) {
				if ($m[2] == 'else' and $m[3] == '/' and $level == 0) {
					$in = 1-$in;
				}
				elseif ($m[1] == '' and $m[3] == '') {
					++$level;
					$parts[$in] .= $chunk;
				}
				elseif ($m[1] == '/') {
					--$level;
					$parts[$in] .= $chunk;
				}
				else {
					$parts[$in] .= $chunk;
				}
			}
			else {
				$parts[$in] .= $chunk;
			}
		}

		global $txp_current_tag;
		trace_add("[$txp_current_tag: ".($condition ? gTxt('true') : gTxt('false'))."]");
		return ($condition ? $parts[0] : $parts[1]);
	}

// -------------------------------------------------------------

	function processTags($matches)
	{
		global $pretext, $production_status, $txptrace, $txptracelevel, $txp_current_tag;

		$tag = $matches[1];

		$trouble_makers = array(
			'link'
		);

		if (in_array($tag, $trouble_makers))
		{
			$tag = 'tpt_'.$tag;
		}

		$atts = isset($matches[2]) ? splat($matches[2]) : '';
		$thing = isset($matches[4]) ? $matches[4] : null;

		$old_tag = @$txp_current_tag;

		$txp_current_tag = '<txp:'.$tag.
			($atts ? $matches[2] : '').
			($thing ? '>' : '/>');

		trace_add($txp_current_tag);
		@++$txptracelevel;

		if ($production_status == 'debug')
		{
			maxMemUsage(trim($matches[0]));
		}

		$out = '';

		if (function_exists($tag))
		{
			$out = $tag($atts, $thing, $matches[0]);
		}

		elseif (isset($pretext[$tag]))
		{
			$out = $pretext[$tag];
		}

		else
		{
			trigger_error(gTxt('unknown_tag', array('{tag}'=>$tag)), E_USER_WARNING);
		}

		@--$txptracelevel;

		if (isset($matches[4]))
		{
			trace_add('</txp:'.$tag.'>');
		}

		$txp_current_tag = $old_tag;

		return $out;
	}

// --------------------------------------------------------------
	function parse($thing)
	{
		$f = '@(</?txp:\S+\b.*(?:/)?(?<!\\\\)>)@sU';

		$parsed = preg_split($f, $thing, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		$tagpat = '@^<(/?)txp:(\w+)\b(.*?)(/?)(?<!\\\\)>$@';

		$out = '';
		$stack = array();
		$inside = '';
		$tag = array();
		foreach ($parsed as $chunk) {
			if (preg_match($tagpat, $chunk, $m)) {
				if ($m[1] == '' and $m[4] == '') {
					// opening tag

					if (empty($stack))
						$tag = $m;
					else
						$inside .= $chunk;

					array_push($stack, $m);
				}
				elseif ($m[1] == '/' and $m[4] == '') {
					// closing tag
					$pop = @array_pop($stack);
					if (!$pop or $pop[2] != $m[2])
						trigger_error(gTxt('parse_tag_mismatch', array('code', $chunk)));

					if (empty($stack)) {
						$out .= processTags(array($m[0], $tag[2], $tag[3], '', $inside));
						$inside = '';
					}
					else
						$inside .= $chunk;
				}
				elseif ($m[1] == '' and $m[4] == '/') {
					// self closing
						if (empty($stack))
							$out .= processTags(array($m[0], $m[2], $m[3]));
						else
							$inside .= $chunk;
				}
				else {
					trigger_error(gTxt('parse_error'.':'.$chunk, array('code', $chunk)));
				}
			}
			else {
				if (empty($stack))
					$out .= $chunk;
				else
					$inside .= $chunk;
			}
		}

		if ($inside)
			$out .= $inside;

		foreach ($stack as $t)
			trigger_error(gTxt('parse_tag_unclosed', array('tag', $t[2])));

		return $out;
	}


?>
