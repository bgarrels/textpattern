<?php
/*

$HeadURL$
$LastChangedRevision$

*/

safe_upgrade_table('textpattern', array(
	'markup_body' => 'varchar(32)',
	'markup_excerpt' => 'varchar(32)',
));

// user-specific preferences
safe_upgrade_table('txp_prefs_user', array(
	'id' => DB_AUTOINC.' PRIMARY KEY',
	'user' => "varchar(64) NOT NULL default ''",
	'name' => "varchar(255) NOT NULL default ''",
	'val' => "varchar(255) NOT NULL default ''",
));

// unique index on user+name
safe_upgrade_index('txp_prefs_user', 'user_idx', 'unique', 'user, name');

if (!safe_column_exists('txp_section', 'id')) {
	safe_alter('txp_section', 'drop primary key');
}

safe_upgrade_table('txp_section', array(
	'id' => DB_AUTOINC.' PRIMARY KEY',
	'path' => "varchar(255) not null default ''",
	'parent' => 'INT',
	'lft' => 'INT not null default 0',
	'rgt' => 'INT not null default 0',
	'inherit' => 'SMALLINT not null default 0',
));

safe_update('txp_section', 'path=name', "path=''");

// shortname has to be unique within a parent
if (!safe_index_exists('txp_section', 'parent_idx'))
	safe_upgrade_index('txp_section', 'parent_idx', 'unique', 'parent,name');

#if (!safe_index_exists('txp_section', 'path_idx'))
#	safe_upgrade_index('txp_section', 'path_idx', 'unique', 'path');

safe_update('txp_section', 'parent=0', "name='default'");
$root_id = safe_field('id', 'txp_section', "name='default'");
safe_update('txp_section', "parent='".$root_id."'", "parent IS NULL");
include_once(txpath.'/lib/txplib_tree.php');
tree_rebuild('txp_section', $root_id, 1);

// <txp:message /> is dropped
safe_update('txp_form', "Form = REPLACE(Form, '<txp:message', '<txp:comment_message')", "1 = 1");

// Expiry datetime for articles
safe_upgrade_table('textpattern', array(
	'Expires' => "datetime NOT NULL default '0000-00-00 00:00:00' after `Posted`"
));

if (!safe_field('name', 'txp_prefs', "name = 'publish_expired_articles'"))
	safe_insert('txp_prefs', "prefs_id = 1, name = 'publish_expired_articles', val = '0', type = '1', event='publish', html='yesnoradio', position='130'");

/*
 * @todo determine section:article relation key
 */
// populate section_id values
// foreach (safe_rows('id, name', 'txp_section', '1=1') as $row) {
//	safe_update('textpattern', "section_id='".doSlash($row['id'])."'", "Section='".doSlash($row['name'])."'");
//}

// fix up the parent field in txp_category
safe_query("alter ignore table ".safe_pfx('txp_category')." modify parent INT not null");
$types = safe_column('distinct type', 'txp_category', '1=1');
foreach ($types as $type) {
	$root = safe_field('id', 'txp_category', "type='".doSlash($type)."' and name='root' and parent=0");
	if (!$root)
		$root = safe_insert('txp_category', "name='root', type='".doSlash($type)."', parent=0");
	safe_update('txp_category', "parent='".$root."'", "type='".doSlash($type)."' and parent=0 and id != '".$root."'");
	tree_rebuild_full('txp_category', "type='".doSlash($type)."'");
}

// index on form type
safe_upgrade_index('txp_form', 'type_idx', '', 'type');

// dropdown ui for certain prefs
safe_upgrade_table('txp_prefs', array(
	'choices' => 'varchar(64)'
));

safe_update('txp_prefs', "html='checkbox'", "html='yesnoradio'");
safe_update('txp_prefs', "html='text'", "html='text_input'");

safe_update('txp_prefs', "choices='commentmode', html='select'", "html='commentmode'");
safe_update('txp_prefs', "choices='logging', html='select'", "html='logging'");
safe_update('txp_prefs', "choices='production_stati', html='radio'", "html='prod_levels'");
safe_update('txp_prefs', "choices='gmtoffsets', html='select'", "html='gmtoffset_select'");

safe_update('txp_prefs', "choices='weeks', html='select'", "html='weeks'");
safe_update('txp_prefs', "choices='languages', html='select'", "html='languages'");
safe_update('txp_prefs', "choices='permlinkmodes', html='radio'", "html='permlinkmodes'");
safe_update('txp_prefs', "choices='dateformats', html='select'", "html='dateformats'");

// change previous Textile prefs into matching markup class names from classMarkup.php
$use_textile = safe_field('val', 'txp_prefs', "name='use_textile'");
$markups = array (
'txprawxhtml',
'txptextile',
'txpnl2br'
);

if(!empty($markups[$use_textile])) {
	safe_insert('txp_prefs', "prefs_id = 1, event='publish', name = 'markup_default', val = '$markups[$use_textile]', type = '0', html='select', choices='markups'");
	safe_delete('txp_prefs', "name='use_textile'");
}

?>
