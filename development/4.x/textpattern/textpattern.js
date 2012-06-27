/*
$HeadURL$
$LastChangedRevision$
*/

// -------------------------------------------------------------
// admin-side "cookies required" warning

function checkCookies()
{
	var date = new Date();

	date.setTime(date.getTime() + (60 * 1000));

	document.cookie = 'testcookie=enabled; expired='+date.toGMTString()+'; path=/';

	cookieEnabled = (document.cookie.length > 2) ? true : false;

	date.setTime(date.getTime() - (60 * 1000));

	document.cookie = 'testcookie=; expires='+date.toGMTString()+'; path=/';

	return cookieEnabled;
}

// -------------------------------------------------------------
// auto-centering popup windows

function popWin(url, width, height, options)
{
	var w = (width) ? width : 400;
	var h = (height) ? height : 400;

	var t = (screen.height) ? (screen.height - h) / 2 : 0;
	var l =	 (screen.width) ? (screen.width - w) / 2 : 0;

	var opt = (options) ? options : 'toolbar = no, location = no, directories = no, '+
		'status = yes, menubar = no, scrollbars = yes, copyhistory = no, resizable = yes';

	var popped = window.open(url, 'popupwindow',
		'top = '+t+', left = '+l+', width = '+w+', height = '+h+',' + opt);

	popped.focus();
}

/**
 * Basic confirmation for potentially powerful choice (like deletion, for example)
 * @param string msg
 * @return bool
 */

function verify(msg)
{
	return confirm(msg);
}

/**
 * Selects all multi-edit checkboxes
 * @deprecated
 */

function selectall()
{
	$('form[name=longform] input[type=checkbox][name="selected[]"]').prop('checked', true);
}

/**
 * De-selects all multi-edit checkboxes
 * @deprecated
 */

function deselectall()
{
	$('form[name=longform] input[type=checkbox][name="selected[]"]').prop('checked', false);
}

/**
 * Selects a range of multi-edit checkboxes
 * @deprecated
 */

function selectrange()
{
	var inrange = false;

	$('form[name=longform] input[type=checkbox][name="selected[]"]').each(function() {
		var $this = $(this);

		if ($this.is(':checked'))
		{
			inrange = (!inrange) ? true : false;
		}

		if (inrange)
		{
			$this.prop('checked', true);
		}
	});
}

/**
 * Toggles the current selection of multi-edit checkboxes
 * @deprecated
 */

function selecttoggle()
{
	$('form[name="longform"] input[name="selected[]"]').each(function() {
		$(this).prop('checked', !$(this).prop('checked'));
	});
}

/**
 * Toggles the entire column of multi-edit checkboxes
 * @deprecated
 */

function toggleAll()
{
	if ($('#selected_toggle').prop('checked')) {
		selectall();
	}
	else {
		deselectall();
	}
}

/**
 * ?
 * @deprecated
 */

function cleanSelects()
{
	var withsel = document.getElementById('withselected');

	if (withsel && withsel.options[withsel.selectedIndex].value != '')
	{
		return (withsel.selectedIndex = 0);
	}
}

/**
 * Multi-edit functions
 * @param string|obj method
 * @param obj options
 * @since 4.5.0
 */

jQuery.fn.txpMultiEditForm = function(method, options)
{
	var args = {}, opt;

	var defaults = {
		'checkbox' : 'input[name="selected[]"][type=checkbox]',
		'rows' : 'tbody td',
		'row' : 'tr, p, div',
		'selectedClass' : 'selected',
		'actions' : 'select[name=edit_method]',
		'selectAll' : 'input[name="select_all"][type=checkbox]',
		'rowClick' : true,
		'altClick' : true
	};

	if ($.type(method) !== 'string')
	{
		options = method;
		method = null;
	}
	else
	{
		args = options;
	}

	opt = options;

	this.closest('form').each(function() {

		var $this = $(this), form = {}, public = {}, private = {};
		
		if ($this.data('_txpMultiEdit'))
		{
			form = $this.data('_txpMultiEdit');
			opt = $.extend(form.opt, opt);
		}
		
		else
		{
			opt = $.extend(defaults, opt);
			form.pattern = opt.checkbox;
			form.editMethod = $this.find(opt.actions);
			form.lastCheck = null;
			form.opt = opt;
		}

		/**
		 * Registers multi-edit options
		 * @param string label
		 * @param string value HTML Option's value
		 * @param obj|string html Object or HTML markup used as for the action's second step. NULL to skip 2nd step.
		 * @return obj this
		 */
		
		public.addOption = function(options)
		{
			var settings = $.extend({
				'label' : null,
				'value' : null,
				'html' : null
			}, options);
		
			var option = form.editMethod.find('option').filter(function() {
				return $(this).attr('value') === settings.value;
			});
			
			var exists = (option.length > 0);
			form.editMethod.val('');
			
			if (!exists)
			{
				option = $('<option />');
			}
			
			if (!option.data('method'))
			{
				if (!option.attr('value'))
				{
					option.attr('value', settings.value);
				}
				
				if (!option.text() && settings.label)
				{
					option.text(settings.label);
				}
				
				option.data('method', settings.html);
			}
			
			if (!exists)
			{
				form.editMethod.append(option);
			}
			
			return public;
		};
		
		/**
		 * Selects rows based on supplied arguments. Only one of the filters applies at time.
		 * @param array index Select based on row's index.
		 * @param array range [min, max] Select based on index range.
		 * @param array value [value1, value2, value3, ...]
		 * @param bool checked Set matched checked or unchecked. FALSE to uncheck.
		 */
		
		public.select = function(options)
		{
			var settings = $.extend({
				'index' : null,
				'range' : null,
				'value' : null,
				'checked' : true
			}, options);
			
			var obj = $this.find(form.pattern);
			
			if (settings.value !== null)
			{
				obj = obj.filter(function() {
					return $.inArray($(this).attr('value'), settings.value) !== -1;
				});
			}
			
			else if (settings.index !== null)
			{
				obj = obj.filter(function(index) {
					return $.inArray(index, settings.index) !== -1;
				});
			}
			
			else if (settings.range !== null)
			{
				obj = obj.slice(settings.range[0], settings.range[1]);
			}
		
			obj.prop('checked', settings.checked).change();
			return public;
		};
		
		/**
		 * Binds checkboxes
		 */
		
		private.bindRows = function()
		{
			form.rows = $this.find(opt.rows);
			form.boxes = $this.find(form.pattern);
			return private;
		};
		
		/**
		 * Highlights selected rows
		 */
		
		private.highlight = function()
		{
			form.boxes.filter(':checked').closest(opt.row).addClass(opt.selectedClass);
			form.boxes.filter(':not(:checked)').closest(opt.row).removeClass(opt.selectedClass);
			return private;
		};
		
		/**
		 * Extends click region to whole row
		 */
		
		private.extendedClick = function()
		{
			if (opt.rowClick)
			{
				var obj = form.rows;
			}
			else
			{
				var obj = form.boxes;
			}

			obj.live('click', function(e) {
			
				var self = ($(e.target).is(form.pattern) || $(this).is(form.pattern));
	
				if (!self && (e.target != this || $(this).is('a, :input') || $(e.target).is('a, :input')))
				{
					return;
				}
				
				if (!self && opt.altClick && !e.altKey && !e.ctrlKey)
				{
					return;
				}
				
				var box = $(this).parents('tr').find(form.pattern);
					
				if (box.length < 1)
				{
					return;
				}
				
				private.bindRows();
				
				var checked = box.prop('checked');
					
				if (self)
				{
					checked = !checked;
				}
					
				if (form.lastCheck)
				{
					var end = form.boxes.index(form.lastCheck);
				}
					
				if (checked === false)
				{
					if (e.shiftKey && form.lastCheck)
					{
						var start = form.boxes.index(box);
						
						public.select({
							'range' : [Math.min(start, end), Math.max(start, end)+1]
						});
					}
					
					else if (!self)
					{
						box.prop('checked', true).change();
					}
					
					form.lastCheck = box;
				}
				
				else
				{
					if (e.shiftKey && form.lastCheck)
					{
						var start = form.boxes.index(box);
						
						public.select({
							'range' : [Math.min(start, end), Math.max(start, end)+1],
							'checked' : false
						});
					}
					else if (!self)
					{
						box.prop('checked', false).change();
					}
				
					form.lastCheck = null;
				}
			});
			
			return private;
		};
		
		/**
		 * Tracks row checks
		 */
		
		private.checked = function()
		{
			form.boxes.live('change', function(e) {
				var box = $(this);
				
				if (box.prop('checked'))
				{
					$(this).parents('tr').addClass(opt.selectedClass);
				}
				else
				{
					$(this).parents('tr').removeClass(opt.selectedClass);
				}
			});
			
			return private;
		};
		
		/**
		 * Handles edit method selecting
		 */
		
		private.changeMethod = function()
		{
			var button = $('.multi-edit input[type="submit"], .multi-edit button[type="submit"]').hide();
			
			form.editMethod.change(function(e) {
				var selected = $(this).find('option:selected');
				$this.find('.multi-step').remove();

				if (selected.length < 1 || selected.val() === '')
				{
					button.hide();
					return private;
				}

				button.show();

				if (selected.data('method'))
				{
					$(this).after($('<div />').attr('class', 'multi-step multi-option').html(selected.data('method')));
				}
				else 
				{
					$(this).parents('form').submit();
				}
			});

			return private;
		};
		
		if(!$this.data('_txpMultiEdit'))
		{
			private.bindRows().highlight().extendedClick().checked().changeMethod();

			$this.find('.multi-option:not(.multi-step)').each(function() {
				public.addOption({
					'label' : null,
					'html' : $(this).contents(),
					'value' : $(this).attr('id').substring(13)
				});
			}).remove();

			$this.find(opt.selectAll).live('change', function(e) {
				public.select({
					'checked' : $(this).prop('checked')
				});
			});
		}

		if (method && public[method])
		{
			public[method].call($this, args);
		}

		$this.data('_txpMultiEdit', form);
	});

	return this;
};

// -------------------------------------------------------------
// event handling
// By S.Andrew -- http://www.scottandrew.com/

function addEvent(elm, evType, fn, useCapture)
{
	if (elm.addEventListener)
	{
		elm.addEventListener(evType, fn, useCapture);
		return true;
	}

	else if (elm.attachEvent)
	{
		var r = elm.attachEvent('on' + evType, fn);
		return r;
	}

	else
	{
		elm['on' + evType] = fn;
	}
}

// -------------------------------------------------------------
// cookie handling

function setCookie(name, value, days)
{
	if (days)
	{
		var date = new Date();

		date.setTime(date.getTime() + (days*24*60*60*1000));

		var expires = '; expires=' + date.toGMTString();
	}

	else
	{
		var expires = '';
	}

	document.cookie = name + '=' + value + expires + '; path=/';
}

function getCookie(name)
{
	var nameEQ = name + '=';

	var ca = document.cookie.split(';');

	for (var i = 0; i < ca.length; i++)
	{
		var c = ca[i];

		while (c.charAt(0)==' ')
		{
			c = c.substring(1, c.length);
		}

		if (c.indexOf(nameEQ) == 0)
		{
			return c.substring(nameEQ.length, c.length);
		}
	}

	return null;
}

function deleteCookie(name)
{
	setCookie(name, '', -1);
}

// -------------------------------------------------------------
// @see http://www.snook.ca/archives/javascript/your_favourite_1/
function getElementsByClass(classname, node)
{
	var a = [];
	var re = new RegExp('(^|\\s)' + classname + '(\\s|$)');
	if(node == null) node = document;
	var els = node.getElementsByTagName("*");
	for(var i=0,j=els.length; i<j; i++)
		if(re.test(els[i].className)) a.push(els[i]);
	return a;
}

// -------------------------------------------------------------
// direct show/hide

function toggleDisplay(id)
{
	var obj = $('#' + id);
	if (obj) {
		obj.toggle();
		// send state of toggle pane to server
		sendAsyncEvent(
			{
				event: textpattern.event,
				step: 'save_pane_state',
				pane: $(obj).attr('id'),
				visible: ($(obj).is(':visible'))
			}
		);
	}
	return false;
}

// -------------------------------------------------------------
// direct show/hide referred #segment; decorate parent lever

function toggleDisplayHref()
{
	var href = $(this).attr('href');
	var lever = $(this).parent('.lever');
	if (href) toggleDisplay(href.substr(1));
	if (lever) {
		if ($(href+':visible').length) {
			lever.addClass('expanded');
		} else {
			lever.removeClass('expanded');
		}
	}
	return false;
}

/**
 * Shows/hides matching elements
 * @param className string Targeted element's class
 * @param show bool|int 1 to display, 0 to hide
 */

function setClassDisplay(className, show)
{
	var obj = $('.'+className);
	
	if (show == 1)
	{
		obj.show();
	}
	else
	{
		obj.hide();
	}
}

// -------------------------------------------------------------
// toggle show/hide matching elements, and set a cookie to remember

function toggleClassRemember(className)
{
	var v = getCookie('toggle_' + className);
	v = (v == 1 ? 0 : 1);

	setCookie('toggle_' + className, v, 365);

	setClassDisplay(className, v);
	setClassDisplay(className+'_neg', 1-v);
}

// -------------------------------------------------------------
// show/hide matching elements based on cookie value

function setClassRemember(className, force)
{
	if (typeof(force) != 'undefined')
		setCookie('toggle_' + className, force, 365);
	var v = getCookie('toggle_' + className);

	setClassDisplay(className, v);
	setClassDisplay(className+'_neg', 1-v);
}

/**
 * Send/receive AJAX posts
 *
 * @param data 	POST payload
 * @param fn 	success handler
 * @param format response data format ['xml']
 * @see http://api.jquery.com/jQuery.post/
 */
function sendAsyncEvent(data, fn, format)
{
	if($.type(data) === 'string' && data.length > 0) {
		// Got serialized data
		data = data + '&app_mode=async&_txp_token=' + textpattern._txp_token;
	} else {
		data.app_mode = 'async';
		data._txp_token = textpattern._txp_token;
	}
	format = format || 'xml';
	$.post('index.php', data, fn, format);
}

/**
 * A pub/sub hub for client side events
 * @since   4.5.0
 */
textpattern.Relay =
{
    /**
     * Publish an event to all registered subscribers
     * @param   event string
     * @param   data object
     * @return  the Relay object
     */
    callback: function(event, data)
    {
        return $(this).trigger(event, data);
        return this;
    },
    /**
     * Subscribe to an event
     * @param   event string
     * @param   fn callback(event, data); // see individual events for details on data members
     * @return  the Relay object
     */
    register: function(event, fn)
    {
        $(this).bind(event, fn);
        return this;
    }
};

/**
 * txpAsyncForm jQuery plugin. Sends a form's entry elements as AJAX data and processes the response javascript.
 *
 * @param   object  options-object {dataType, error: function error_callback(){}, success: function success_callback(){}} | undefined
 * @return  object this form
 * @since   4.5.0
 */

jQuery.fn.txpAsyncForm = function(options)
{
	options = $.extend({
		dataType: 'script',
		success: null,
		error: null
	}, options);

    // Send form data to application, process response as script.
    this.submit(function(event) {
	    try {
		    var form = $(this);
		    var s;

		    // Show feedback while processing
		    form.addClass('busy');
		    $('body').addClass('busy');

		    s = form.find('input[type="submit"]:focus');
		    if (s.length == 0) {
			    // WebKit does not set :focus on button-click: use first submit input as a fallback
			    s = form.find('input[type="submit"]');
		    }
		    if (s.length > 0) {
			    s = s.slice(0,1);
		    }

		    s.attr('disabled', true).after('<span class="spinner"></span>');

		    // error handler
		    form.ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
			    // do not pile up error handlers upon repeat submissions
			    $(this).off('ajaxError');
			    // remove feedback elements
			    form.removeClass('busy');
			    s.removeAttr('disabled');
			    $('body').removeClass('busy');
			    $('span.spinner').remove();
			    if (options.error) options.error(form, event, jqXHR, ajaxSettings, thrownError);
                textpattern.Relay.callback('txpAsyncForm.error', {'this': form, 'event': event, 'jqXHR': jqXHR, 'ajaxSettings': ajaxSettings, 'thronwError': thrownError});
            });

            sendAsyncEvent(
				form.serialize() + '&' + (s.attr('name') || '_txp_submit') + '=' + (s.val() || '_txp_submit'),
				function(data, textStatus, jqXHR) {
	                // remove feedback elements
					form.removeClass('busy');
					s.removeAttr('disabled');
					$('body').removeClass('busy');
	                $('span.spinner').remove();
					form.ajaxError = null;
					if (options.success) options.success(form, event, data, textStatus, jqXHR);
                    textpattern.Relay.callback('txpAsyncForm.success', {'this': form, 'event': event, 'data': data, 'textStatus': textStatus, 'jqXHR': jqXHR});
				},
				options.dataType
			);
			event.preventDefault();
	    } catch(e) {}
	});
	return this;
};

jQuery.fn.txpAsyncHref = function(options) {
    options = $.extend({
        dataType: 'text',
        success: null,
        error: null
    }, options);

    this.click(function(event) {
        try {
            event.preventDefault();
            var obj = $(this);
            var value = obj.text();
            sendAsyncEvent(
                // query string contains request params
                this.search.replace('?', '') + '&value=' + value,
                function(data, textStatus, jqXHR) {
                    obj.html(data);
                    if (options.success) options.success(obj, event, data, textStatus, jqXHR);
                    textpattern.Relay.callback('txpAsyncHref.success', {'this': obj, 'event': event, 'data': data, 'textStatus': textStatus, 'jqXHR': jqXHR});
                },
                options.dataType
            );
        } catch(e){}

    });
    return this;
}

/**
 * Returns a l18n string.
 * @param string l18n The l18n string to output
 * @param object atts Replacement map
 * @param boolean escape Escape HTML. Default TRUE
 * @return string
 */

textpattern.gTxt = function(l18n, atts, escape)
{
	var tags = atts || {};
	var string = l18n;
	var name = string.toLowerCase();

	if ($.type(textpattern.textarray[name]) !== 'undefined') {
		string = textpattern.textarray[name];
	}

	if (escape !== false) {
		string = $('<div/>').text(string).html();

		$.each(tags, function(key, value) {
			tags[key] = $('<div/>').text(value).html();
		});
	}

	$.each(tags, function(key, value) {
		string = string.replace(key, value);
	});

	return string;
}

/**
 * jQuery plugin for textpattern.gTxt. Sets HTML contents of each matched element.
 * @param object options-object {string, tags : {}, escape : TRUE} | string The l18n string
 * @param object|undefined tags Replacement tags
 * @param boolean|undefined escape Escape HTML
 * @return object this
 */

jQuery.fn.gTxt = function(opts, tags, escape)
{
	var options = opts;

	if ($.type(options) !== 'object') {
		options = {
			string : opts,
			tags : tags,
			escape : escape
		};
	}

	$(this).html(textpattern.gTxt(options.string, options.tags, options.escape));
	return this;
};

//-------------------------------------------------------------
// global admin-side behaviour
$(document).ready(function() {
	// disable spellchecking on all elements of class "code" in capable browsers
	var c = $(".code")[0];
	if(c && "spellcheck" in c) {$(".code").prop("spellcheck", false);}
	// enable spellcheck for all elements mentioned in textpattern.do_spellcheck
	c = $(textpattern.do_spellcheck)[0];
	if(c && "spellcheck" in c) {$(textpattern.do_spellcheck).prop("spellcheck", true);}
	// attach toggle behaviours
	$('.lever a[class!=pophelp]').click(toggleDisplayHref);
	$('.multi_edit_form').txpMultiEditForm();
	// establish AJAX timeout from prefs
	if($.ajaxSetup().timeout === undefined) {
		$.ajaxSetup( {timeout : textpattern.ajax_timeout} );
	}
	// setup and submit async forms
	if(!textpattern.ajaxally_challenged) {
        $('form.async').txpAsyncForm({
            error: function() {window.alert(textpattern.gTxt('form_submission_error'));}
        });
        $('a.async').txpAsyncHref({
            error: function() {window.alert(textpattern.gTxt('form_submission_error'));}
        });
    }
});
