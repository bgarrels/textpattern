<?php

/*

$HeadURL$
$LastChangedRevision$

*/


/* This is a kind of hybrid view-controller.

Example usage:

class FooController extends ZemAdminController {

	// each 'step' has a pair of functions: one to handle the POST, and one to generate the HTML

   function edit_view() {
   	// return the html to display for the 'edit' step, following a GET or POST
		return a_html_input_form();
   }

   function edit_post() {
   	// handle a http POST for the 'edit' step, before the view function is displayed
      if (save_something($this->ps('something'))) {
      	$this->_message('something was saved ok');
      }
      else {
      	$this->_error('invalid input');
      }
   }

   // ..etc for other pairs of functions representing additions steps

}

*/

class ZemAdminController {
	var $event = NULL;
	var $caption = '';	// human-readable, i18n-aware name
	var $default_step = 'default';

	var $_messages = array();
	var $_step;
	var $_view_args = array();

	function ZemAdminController() {
	}

	function event_handler($event, $step) {
		// the generic event handler

		$this->init($event, $step);

		$this->post_handler($event, $step);

		// now call $this->{$this->_step}_view()
		$step_view = "{$this->_step}_view";
		if (is_callable(array(&$this, $step_view))) {
			// ob buffering is for legacy reasons -- ideally, _view methods should return
			// output, not echo it, but this will help older code work during migration
			ob_start();
			$out = call_user_func_array(array(&$this, $step_view), $this->_view_args);
			$out .= ob_get_clean();
			$this->_render($out);
		}
		else {
			trigger_error("Unhandled event '$event', step '$step_view'", E_USER_ERROR);
		}

	}

	function post_handler($event, $step) {
		if ($this->_method() == 'POST') {
			// call $this->{$step}_post() if it exists
			$step_post = "{$step}_post";
			if (is_callable(array(&$this, $step_post))) {
				$this->$step_post();
			}
		}
	}

	function init($event, $step) {
		$this->event = $event;
		if (!$step)
			$step = $this->default_step;
		$this->_set_view($step);

	}

// ---------------------------------------------------------
// functions for use by child classes

	function _method() {
		// return the request method
		return strtoupper(serverSet('REQUEST_METHOD'));
	}

	function _pagetop()
	{
		pagetop(gTxt($this->caption));
	}

	function _pagebottom()
	{
	}

	function _render($out) {
		$this->_pagetop();

		$msg = '';
		foreach ($this->_messages as $m) {
			$msg .= '<div class="'.$m[0].'">'.n
			.$m[1].n
			.'</div>'.n;
		}

		echo '<div id="main-view">'.n
			.$msg
			.$out.n
			.'</div>';

		$this->_pagebottom();
	}

	function _set_view($step /*, $a, $b, ... */) {
		// use this in a _post handler to switch to a different view
		// additional args will be passed as parameters to the view function
		$this->_step = $step;
		$args = func_get_args();
		$this->_view_args = array_slice($args, 1);
	}

	// display a message to the user
	// $type is used as the CSS class
	function _message($msg, $type='message') {
		$this->_messages[] = array($type, $msg);
	}

	function _error($msg) {
		$this->_message($msg, 'error');
	}

	function get_last_message($type='message') {
		foreach (array_reverse($this->_messages) as $m) {
			if ($m[0] == $type)
				return $m[1];
		}
	}

	function get_last_error() {
		return $this->get_last_message('error');
	}

	// GET/POST values

	// GET or POST
	function gps($name, $default='') {
		return gps($name, $default);
	}

	// GET only
	function gs($name, $default='') {
		if (isset($_GET[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_GET[$thing]);
			} else {
				return $_GET[$thing];
			}
		}
		return $default;
	}

	// POST only
	function ps($name, $default='') {
		return ps($name, $default);
	}

	// this does ps() and assert_int() in one step
	// and also gives a more informative error message than assert_int() alone
	function psi($name, $default='') {
		$i = ps($name, $default);
		if (is_numeric($i) and intval($i) == $i)
			return intval($i);
		trigger_error(gTxt('post_var_not_int', array('{name}' => $name, '{val}' => $i)));
		return $default;
	}

	// FIXME: this belongs in the table view class
	function pageby_form()
	{
		global $prefs;

		$vals = array(
			15  => 15,
			25  => 25,
			50  => 50,
			100 => 100
		);

		$val = @$prefs[$this->event.'_list_pageby'];

		$select_page = selectInput('qty', $vals, $val,'', 1);

		// proper localisation
		$page = str_replace('{page}', $select_page, gTxt('view_per_page'));

		return form(
			$page.
			eInput($this->event).
			sInput('change_pageby').
			'<noscript> <input type="submit" value="'.gTxt('go').'" class="smallerbox" /></noscript>'
		, '', '', 'post', 'pageby-form');
	}


// -------------------------------------------------------------
// default post handler for pageby form
	function change_pageby_post()
	{
		event_change_pageby($this->event);
		$this->_set_view($this->default_step);
	}


}

function register_controller($classname, $event) {
	global $txp_controllers;

	$func = 'txp_'.$classname.'_controller';

	$code =
		'function '.$func.'($event, $step) {
			$o = new '.$classname.'();
			$o->event_handler($event, $step);
		}';

	eval($code);

	// register an event handler and tab
	register_callback($func, $event);
	@$txp_controllers[$event] == $classname;
}

function controller_name($event) {
	global $txp_controllers;

	return @$txp_controllers[$event];
}

?>