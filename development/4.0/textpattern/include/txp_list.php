<?php
/*
	This is Textpattern
	Copyright 2005 by Dean Allen 
 	All rights reserved.

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	global $statuses;
	$statuses = array(
		1 => gTxt('draft'),
		2 => gTxt('hidden'),
		3 => gTxt('pending'),
		4 => gTxt('live'),
		5 => gTxt('sticky'),
	);

	if ($event=='list') {
		require_privs('article');

		if(!$step or !in_array($step, array('list_change_pageby','list_list','list_multi_edit','list_list'))){
			list_list();
		} else $step();
	}

//--------------------------------------------------------------

	function list_list($message = '', $post = '')
	{
		global $statuses, $step;

		pagetop(gTxt('tab_list'), $message);

		extract(get_prefs());

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'method')));

		$sesutats = array_flip($statuses);

		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		switch ($sort)
		{
			case 'id':
				$sort_sql = '`ID` '.$dir;
			break;

			case 'posted':
				$sort_sql = '`Posted` '.$dir;
			break;

			case 'title':
				$sort_sql = '`Title` '.$dir.', `Posted` desc';
			break;

			case 'section':
				$sort_sql = '`Section` '.$dir.', `Posted` desc';
			break;

			case 'category1':
				$sort_sql = '`Category1` '.$dir.', `Posted` desc';
			break;

			case 'category2':
				$sort_sql = '`Category2` '.$dir.', `Posted` desc';
			break;

			case 'status':
				$sort_sql = '`Status` '.$dir.', `Posted` desc';
			break;

			case 'author':
				$sort_sql = '`AuthorID` '.$dir.', `Posted` desc';
			break;

			default:
				$dir = 'desc';
				$sort_sql = '`Posted` '.$dir;
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($crit or $method)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'title_body' => "Title rlike '$crit_escaped' or Body rlike '$crit_escaped'",
				'section'		 => "Section rlike '$crit_escaped'",
				'categories' => "Category1 rlike '$crit_escaped' or Category2 rlike '$crit_escaped'",
				'status'		 => "Status = '".(@$sesutats[$crit_escaped])."'",
				'author'		 => "AuthorID rlike '$crit_escaped'",
			);

			if (array_key_exists($method, $critsql))
			{
				$criteria = $critsql[$method];
				$limit = 500;
			}

			else
			{
				$method = '';
			}
		}

		$total = safe_count('textpattern', "$criteria");

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.graf(gTxt('no_results_found'), ' style="text-align: center;"');
			}

			else
			{
				echo graf(gTxt('no_articles_recorded'), ' style="text-align: center;"');
			}

			return;
		}

		$limit = max(@$article_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo list_searching_form($crit, $method);

		$rs = safe_rows_start('*, unix_timestamp(Posted) as uPosted', 'textpattern',
			"$criteria order by $sort_sql limit $offset, $limit"
		);

		if ($rs)
		{
			echo n.n.'<form name="longform" method="post" action="index.php" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

				n.startTable('list').
				n.tr(
					n.column_head('ID', 'id', 'list', true, $switch_dir, $crit, $method).
					column_head('posted', 'posted', 'list', true, $switch_dir, $crit, $method).
					column_head('title', 'title', 'list', true, $switch_dir, $crit, $method).
					column_head('section', 'section', 'list', true, $switch_dir, $crit, $method).
					column_head('category1', 'category1', 'list', true, $switch_dir, $crit, $method).
					column_head('category2', 'category2', 'list', true, $switch_dir, $crit, $method).
					column_head('status', 'status', 'list', true, $switch_dir, $crit, $method).
					column_head('author', 'author', 'list', true, $switch_dir, $crit, $method).
					td()
				);

			while ($a = nextRow($rs))
			{
				extract($a);

				$Title = empty($Title) ? gTxt('untitled') : $Title;
				$stat = !empty($Status) ? $statuses[$Status] : '';

				echo n.n.tr(
					n.td(
						eLink('article', 'edit', 'ID', $ID, $ID)
					, 50).

					td(
						safe_strftime('%d %b %Y %I:%M %p', $uPosted)
					, 75).

					td(
						eLink('article', 'edit', 'ID', $ID, $Title)
					, 200).

					td(
						$Section
					, 75).

					td(
						$Category1
					, 75).

					td(
						$Category2
					, 75).

					td(
						$stat
					, 50).

					td(
						$AuthorID
					, 75).

					td(
						fInput('checkbox', 'selected[]', $ID, '', '', '', '', '', $ID)
					)
				);
			}

			echo tr(
				tda(
					select_buttons().list_multiedit_form()
				,' colspan="9" style="text-align: right; border: none;"')
			).

			endTable().
			'</form>'.

			list_nav_form($page, $numPages, $sort, $dir, $crit, $method).

			pageby_form('list', $article_list_pageby);

			unset($sort);
		}
	}

// -------------------------------------------------------------
	function list_change_pageby() 
	{
		event_change_pageby('article');
		list_list();
	}

// -------------------------------------------------------------

	function list_searching_form($crit, $method)
	{
		$methods =	array(
			'title_body' => gTxt('title_body'),
			'section'		 => gTxt('section'),
			'categories' => gTxt('categories'),
			'status'		 => gTxt('status'),
			'author'		 => gTxt('author')
		);

		return n.n.form(
			graf(

				gTxt('Search').sp.selectInput('method', $methods, $method).
				fInput('text', 'crit', $crit, 'edit', '', '', '15').
				eInput("list").
				sInput('list').
				fInput('submit', 'search', gTxt('go'), 'smallerbox')

			,' style="text-align: center;"')
		);
	}

// -------------------------------------------------------------

	function list_nav_form($page, $numPages, $sort, $dir, $crit, $method)
	{
		$nav = array();

		if ($page > 1)
		{
			$nav[] = PrevNextLink('list', $page - 1, gTxt('prev'), 'prev', $sort, $dir, $crit, $method).sp;
		}

		$nav[] = small($page.'/'.$numPages);

		if ($page != $numPages)
		{
			$nav[] = sp.PrevNextLink('list', $page + 1, gTxt('next'), 'next', $sort, $dir, $crit, $method);
		}

		return graf(join('', $nav),' style="text-align: center;"');
	}

// -------------------------------------------------------------
	function list_multiedit_form() 
	{
		return event_multiedit_form('list');
	}

// -------------------------------------------------------------
	function list_multi_edit() 
	{
		global $txp_user;

		if (ps('selected') and !has_privs('article.delete')) {
			$ids = array();
			if (has_privs('article.delete.own')) {
				foreach (ps('selected') as $id) {
					$author = safe_field('AuthorID', 'textpattern', "ID='".doSlash($id)."'");
					if ($author == $txp_user)
						$ids[] = $id;
				}
			}
			$_POST['selected'] = $ids;
		}

		$deleted = event_multi_edit('textpattern','ID');
		if(!empty($deleted)){
			$method = ps('method');
			return list_list(messenger('article',$deleted,(($method == 'delete')?'deleted':'modified')));
		}
		return list_list();
	}

?>
