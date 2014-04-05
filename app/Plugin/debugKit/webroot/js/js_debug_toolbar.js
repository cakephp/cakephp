/**
 * Debug Toolbar Javascript.
 *
 * Creates the DEBUGKIT namespace and provides methods for extending
 * and enhancing the Html toolbar. Includes library agnostic Event, Element,
 * Cookie and Request wrappers.
 *
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/* jshint jquery: true */

var DEBUGKIT = function () {
	var undef;
	return {
		module: function (newmodule) {
			if (this[newmodule] === undef) {
				this[newmodule] = {};
				return this[newmodule];
			}
			return this[newmodule];
		}
	};
}();

(function () {
	function versionGTE(a, b) {
		var len = Math.min(a.length, b.length);
		for (var i = 0; i < len; i++) {
			a[i] = parseInt(a[i], 10);
			b[i] = parseInt(b[i], 10);
			if (a[i] > b[i]) {
				return true;
			}
			if (a[i] < b[i]) {
				return false;
			}
		}
		return true;
	}

	function versionWithin(version, min, max) {
		version = version.split('.');
		min = min.split('.');
		max = max.split('.');
		return versionGTE(version, min) && versionGTE(max, version);
	}

	// Look for existing jQuery that matches the requirements.
	if (window.jQuery && versionWithin(jQuery.fn.jquery, "1.8", "2.1")) {
		DEBUGKIT.$ = window.jQuery;
	} else {
		// sync load the file. Using document.write() does not block
		// in recent versions of chrome.
		var req = new XMLHttpRequest();
		req.onload = function () {
			eval(this.responseText);
			// Restore both $ and jQuery to the original values.
			DEBUGKIT.$ = jQuery.noConflict(true);
		};
		req.open('get', window.DEBUGKIT_JQUERY_URL, false);
		req.send();
	}
})();

DEBUGKIT.loader = function () {
	return {
		// List of methods to run on startup.
		_startup: [],

		// Register a new method to be run on dom ready.
		register: function (method) {
			this._startup.push(method);
		},

		init: function () {
			for (var i = 0, callback; callback = this._startup[i]; i++) {
				callback.init();
			}
		}
	};
}();

DEBUGKIT.module('sqlLog');
DEBUGKIT.sqlLog = function () {
	var $ = DEBUGKIT.$;

	return {
		init : function () {
			var sqlPanel = $('#sqllog-tab');
			var buttons = sqlPanel.find('input');

			// Button handling code for explain links.
			// Performs XHR request to get explain query.
			var handleButton = function (event) {
				event.preventDefault();
				var form = $(this.form),
					data = form.serialize(),
					dbName = form.find('input[name*=ds]').val() || 'default';

				var fetch = $.ajax({
					url: this.form.action,
					data: data,
					type: 'POST',
					success : function (response) {
						$('#sql-log-explain-' + dbName).html(response);
					},
					error : function () {
						alert('Could not fetch EXPLAIN for query.');
					}
				});
			};

			buttons.filter('.sql-explain-link').on('click', handleButton);
		}
	};
}();
DEBUGKIT.loader.register(DEBUGKIT.sqlLog);

//
// NOTE DEBUGKIT.Util.Element is Deprecated.
//
// Util module and Element utility class.
DEBUGKIT.module('Util');
DEBUGKIT.Util.Element = {

	// Test if an element is a name node.
	nodeName: function (element, name) {
		return element.nodeName && element.nodeName.toLowerCase() === name.toLowerCase();
	},

	// Return a boolean if the element has the classname
	hasClass: function (element, className) {
		if (!element.className) {
			return false;
		}
		return element.className.indexOf(className) > -1;
	},

	addClass: function (element, className) {
		if (!element.className) {
			element.className = className;
			return;
		}
		element.className = element.className.replace(/^(.*)$/, '$1 ' + className);
	},

	removeClass: function (element, className) {
		if (DEBUGKIT.Util.isArray(element)) {
			DEBUGKIT.Util.Collection.apply(element, function (element) {
				DEBUGKIT.Util.Element.removeClass(element, className);
			});
		}
		if (!element.className) {
			return false;
		}
		element.className = element.className.replace(new RegExp(' ?(' + className + ') ?'), '');
	},

	swapClass: function (element, removeClass, addClass) {
		if (!element.className) {
			return false;
		}
		element.className = element.className.replace(removeClass, addClass);
	},

	show: function (element) {
		element.style.display = 'block';
	},

	hide: function (element) {
		element.style.display = 'none';
	},

	// Go between hide() and show() depending on element.style.display
	toggle: function (element) {
		if (element.style.display === 'none') {
			this.show(element);
			return;
		}
		this.hide(element);
	},

	_walk: function (element, walk) {
		var sibling = element[walk];
		while (true) {
			if (sibling.nodeType == 1) {
				break;
			}
			sibling = sibling[walk];
		}
		return sibling;
	},

	getNext: function (element) {
		return this._walk(element, 'nextSibling');
	},

	getPrevious: function (element) {
		return this._walk(element, 'previousSibling');
	},

	// Get or set an element's height, omit value to get, add value (integer) to set.
	height: function (element, value) {
		// Get value
		if (value === undefined) {
			return parseInt(this.getStyle(element, 'height'), 10);
		}
		element.style.height = value + 'px';
	},

	// Gets the style in css format for property
	getStyle: function (element, property) {
		if (element.currentStyle) {
			property = property.replace(/-[a-z]/g, function (match) {
				return match.charAt(1).toUpperCase();
			});
			return element.currentStyle[property];
		}
		if (window.getComputedStyle) {
			return document.defaultView.getComputedStyle(element, null).getPropertyValue(property);
		}
	}
};

//
// NOTE DEBUGKIT.Util.Collection is Deprecated.
//
DEBUGKIT.Util.Collection = {
	/**
	 * Apply the passed function to each item in the collection.
	 * The current element in the collection will be `this` in the callback
	 * The callback is also passed the element and the index as arguments.
	 * Optionally you can supply a binding parameter to change `this` in the callback.
	 */
	apply: function (collection, callback, binding) {
		var name, thisVar, i = 0, len = collection.length;

		if (len === undefined) {
			for (name in collection) {
				thisVar = (binding === undefined) ? collection[name] : binding;
				callback.apply(thisVar, [collection[name], name]);
			}
		} else {
			for (; i < len; i++) {
				thisVar = (binding === undefined) ? collection[i] : binding;
				callback.apply(thisVar, [collection[i], i]);
			}
		}
	}
};

//
// NOTE DEBUGKIT.Util.Event is Deprecated.
//
// Event binding
DEBUGKIT.Util.Event = function () {
	var _listeners = {},
		_eventId = 0;

	var preventDefault = function () {
		this.returnValue = false;
	};

	var stopPropagation = function () {
		this.cancelBubble = true;
	};

	// Fixes IE's broken event object, adds in common methods + properties.
	var fixEvent = function (event) {
		if (!event.preventDefault) {
			event.preventDefault = preventDefault;
		}
		if (!event.stopPropagation) {
			event.stopPropagation = stopPropagation;
		}
		if (!event.target) {
			event.target = event.srcElement || document;
		}
		if (event.pageX === null && event.clientX !== null) {
			var doc = document.body;
			event.pageX = event.clientX + (doc.scrollLeft || 0) - (doc.clientLeft || 0);
			event.pageY = event.clientY + (doc.scrollTop || 0) - (doc.clientTop || 0);
		}
		return event;
	};

	return {
		// Bind an event listener of type to element, handler is your method.
		addEvent: function (element, type, handler, capture) {
			capture = (capture === undefined) ? false : capture;

			var callback = function (event) {
				event = fixEvent(event || window.event);
				handler.apply(element, [event]);
			};

			if (element.addEventListener) {
				element.addEventListener(type, callback, capture);
			} else if (element.attachEvent) {
				type = 'on' + type;
				element.attachEvent(type, callback);
			} else {
				type = 'on' + type;
				element[type] = callback;
			}
			_listeners[++_eventId] = {element: element, type: type, handler: callback};
		},

		// Destroy an event listener. requires the exact same function as was used for attaching
		// the event.
		removeEvent: function (element, type, handler) {
			if (element.removeEventListener) {
				element.removeEventListener(type, handler, false);
			} else if (element.detachEvent) {
				type = 'on' + type;
				element.detachEvent(type, handler);
			} else {
				type = 'on' + type;
				element[type] = null;
			}
		},

		// Bind an event to the DOMContentLoaded or other similar event.
		domready: function (callback) {
			if (document.addEventListener) {
				return document.addEventListener('DOMContentLoaded', callback, false);
			}

			if (document.all && !window.opera) {
				// Define a "blank" external JavaScript tag
				document.write(
					'<script type="text/javascript" id="__domreadywatcher" defer="defer" src="javascript:void(0)"><\/script>'
				);
				var contentloadtag = document.getElementById('__domreadywatcher');
				contentloadtag.onreadystatechange = function () {
					if (this.readyState === 'complete') {
						callback();
					}
				};
				contentloadtag = null;
				return;
			}

			if (/Webkit/i.test(navigator.userAgent)) {
				var _timer = setInterval(function () {
					if (/loaded|complete/.test(document.readyState)) {
						clearInterval(_timer);
						callback();
					}
				}, 10);
			}
		},

		// Unload all the events attached by DebugKit. Fix any memory leaks.
		unload: function () {
			var listener;
			for (var i in _listeners) {
				listener = _listeners[i];
				try {
					this.removeEvent(listener.element, listener.type, listener.handler);
				} catch (e) {}
				delete _listeners[i];
			}
			delete _listeners;
		}
	};
}();

// Cookie utility
DEBUGKIT.Util.Cookie = function () {
	var cookieLife = 60;

// Public methods
	return {
		/**
		 * Write to cookie.
		 *
		 * @param [string] name Name of cookie to write.
		 * @param [mixed] value Value to write to cookie.
		 */
		write: function (name, value) {
			var date = new Date();
			date.setTime(date.getTime() + (cookieLife * 24 * 60 * 60 * 1000));
			var expires = '; expires=' + date.toGMTString();
			document.cookie = name + '=' + value + expires + '; path=/';
			return true;
		},

		/**
		 * Read from the cookie.
		 *
		 * @param [string] name Name of cookie to read.
		 */
		read: function (name) {
			name = name + '=';
			var cookieJar = document.cookie.split(';');
			var cookieJarLength = cookieJar.length;
			for (var i = 0; i < cookieJarLength; i++) {
				var chips = cookieJar[i];
				// Trim leading spaces
				while (chips.charAt(0) === ' ') {
					chips = chips.substring(1, chips.length);
				}
				if (chips.indexOf(name) === 0) {
					return chips.substring(name.length, chips.length);
				}
			}
			return false;
		},

		/**
		 * Delete a cookie by name.
		 *
		 * @param [string] name of cookie to delete.
		 */
		del: function (name) {
			var date = new Date();
			date.setFullYear(2000, 0, 1);
			var expires = ' ; expires=' + date.toGMTString();
			document.cookie = name + '=' + expires + '; path=/';
		}
	};
}();

//
// NOTE DEBUGKIT.Util.merge is Deprecated.
//

/**
 * Object merge takes any number of arguments and glues them together.
 *
 * @param [Object] one first object
 * @return object
 */
DEBUGKIT.Util.merge = function () {
	var out = {};
	var argumentsLength = arguments.length;
	for (var i = 0; i < argumentsLength; i++) {
		var current = arguments[i];
		for (var prop in current) {
			if (current[prop] !== undefined) {
				out[prop] = current[prop];
			}
		}
	}
	return out;
};

//
// NOTE DEBUGKIT.Util.isArray is Deprecated.
//

/**
 * Check if the given object is an array.
 */
DEBUGKIT.Util.isArray = function (test) {
	return Object.prototype.toString.call(test) === '[object Array]';
};

//
// NOTE DEBUGKIT.Util.Request is Deprecated.
//
// Simple wrapper for XmlHttpRequest objects.
DEBUGKIT.Util.Request = function (options) {
	var _defaults = {
		onComplete : function () {},
		onRequest : function () {},
		onFail : function () {},
		method : 'GET',
		async : true,
		headers : {
			'X-Requested-With': 'XMLHttpRequest',
			'Accept': 'text/javascript, text/html, application/xml, text/xml, */*'
		}
	};

	var self = this;
	this.options = DEBUGKIT.Util.merge(_defaults, options);
	this.options.method = this.options.method.toUpperCase();

	var ajax = this.createObj();
	this.transport = ajax;

	// Event assignment
	this.onComplete = this.options.onComplete;
	this.onRequest = this.options.onRequest;
	this.onFail = this.options.onFail;

	this.send = function (url, data) {
		if (this.options.method === 'GET' && data) {
			url = url + ((url.charAt(url.length - 1) === '?') ? '&' : '?') + data; //check for ? at the end of the string
			data = null;
		}
		// Open connection
		this.transport.open(this.options.method, url, this.options.async);

		// Set statechange and pass the active XHR object to it. From here it handles all status changes.
		this.transport.onreadystatechange = function () {
			self.onReadyStateChange.apply(self, arguments);
		};
		for (var key in this.options.headers) {
			this.transport.setRequestHeader(key, this.options.headers[key]);
		}
		if (typeof data === 'object') {
			data = this.serialize(data);
		}
		if (data) {
			this.transport.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		}
		this.onRequest();
		this.transport.send(data);
	};
};

DEBUGKIT.Util.Request.prototype.onReadyStateChange = function () {
	if (this.transport.readyState !== 4) {
		return;
	}
	if (this.transport.status === 200 || this.transport.status > 300 && this.transport.status < 400) {
		this.response = {
			xml: this.transport.responseXML,
			text: this.transport.responseText
		};

		if (typeof this.onComplete === 'function') {
			this.onComplete.apply(this, [this, this.response]);
		} else {
			return this.response;
		}
	} else if (this.transport.status > 400) {
		if (typeof this.onFail === 'function') {
			this.onFail.apply(this, []);
		} else {
			console.error('Request failed');
		}
	}
};

/**
 * Creates cross-broswer XHR object used for requests.
 * Tries using the standard XmlHttpRequest, then IE's wacky ActiveX Objects.
 */
DEBUGKIT.Util.Request.prototype.createObj = function () {
	var request = null;
	try {
		request = new XMLHttpRequest();
	} catch (MS) {
		try {
			request = new ActiveXObject('Msxml2.XMLHTTP');
		} catch (old_MS) {
			try {
				request = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (failure) {
				request = null;
			}
		}
	}
	return request;
};

/**
 * Serializes an object literal into a querystring.
 */
DEBUGKIT.Util.Request.prototype.serialize = function (data) {
	var out = '';
	for (var name in data) {
		if (data.hasOwnProperty(name)) {
			out += name + '=' + data[name] + '&';
		}
	}
	return out.substring(0, out.length - 1);
};


// Basic toolbar module.
DEBUGKIT.toolbar = function () {
	// Shortcuts
	var Cookie = DEBUGKIT.Util.Cookie,
		$ = DEBUGKIT.$,
		toolbarHidden = false;

	return {
		elements: {},
		panels: {},

		init: function () {
			var i, element, lists, index, _this = this;

			this.elements.toolbar = $('#debug-kit-toolbar');

			if (this.elements.toolbar.length === 0) {
				throw new Error('Toolbar not found, make sure you loaded it.');
			}

			this.elements.panel = $('#panel-tabs');
			this.elements.panel.find('.panel-tab').each(function (i, panel) {
				_this.addPanel(panel);
			});

			lists = this.elements.toolbar.find('.depth-0');

			this.makeNeatArray(lists);
			this.deactivatePanel(true);
		},

		// Add a panel to the toolbar
		addPanel: function (tab) {
			var button, content, _this = this;
			var panel = {
				id : false,
				element : tab,
				button : undefined,
				content : undefined,
				active : false
			};
			tab = $(tab);
			button = tab.children('a');

			panel.id = button.attr('href').replace(/^#/, '');
			panel.button = button;
			panel.content = tab.find('.panel-content');

			if (!panel.id || panel.content.length === 0) {
				return false;
			}
			this.makePanelDraggable(panel);
			this.makePanelMinMax(panel);

			button.on('click', function (event) {
				event.preventDefault();
				_this.togglePanel(panel.id);
			});

			this.panels[panel.id] = panel;
			return panel.id;
		},

		// Find the handle element and make the panel drag resizable.
		makePanelDraggable: function (panel) {

			// Create a variable in the enclosing scope, for scope tricks.
			var currentElement = null;

			// Use the elements startHeight stored Event.pageY and current Event.pageY to
			// resize the panel.
			var mouseMoveHandler = function (event) {
				event.preventDefault();
				if (!currentElement) {
					return;
				}
				var newHeight = currentElement.data('startHeight') + (event.pageY - currentElement.data('startY'));
				currentElement.parent().height(newHeight);
			};

			// Handle the mouseup event, remove the other listeners so the panel
			// doesn't continue to resize.
			var mouseUpHandler = function (event) {
				currentElement = null;
				$(document).off('mousemove', mouseMoveHandler).off('mouseup', mouseUpHandler);
			};

			var mouseDownHandler = function (event) {
				event.preventDefault();

				currentElement = $(this);
				currentElement.data('startY', event.pageY);
				currentElement.data('startHeight', currentElement.parent().height());

				// Attach to document so mouse doesn't have to stay precisely on the 'handle'.
				$(document).on('mousemove', mouseMoveHandler)
					.on('mouseup', mouseUpHandler);
			};

			panel.content.find('.panel-resize-handle').on('mousedown', mouseDownHandler);
		},

		// Make the maximize button work on the panels.
		makePanelMinMax: function (panel) {
			var _oldHeight;

			var maximize = function () {
				if (!_oldHeight) {
					_oldHeight = this.parentNode.offsetHeight;
				}
				var windowHeight = window.innerHeight;
				var panelHeight = windowHeight - this.parentNode.offsetTop;
				$(this.parentNode).height(panelHeight);
				$(this).text('-');
			};

			var minimize = function () {
				$(this.parentNode).height(_oldHeight);
				$(this).text('+');
				_oldHeight = null;
			};

			var state = 1;
			var toggle = function (event) {
				event.preventDefault();
				if (state === 1) {
					maximize.call(this);
					state = 0;
				} else {
					state = 1;
					minimize.call(this);
				}
			};

			panel.content.find('.panel-toggle').on('click', toggle);
		},

		// Toggle a panel
		togglePanel: function (id) {
			if (this.panels[id] && this.panels[id].active) {
				this.deactivatePanel(true);
			} else {
				this.deactivatePanel(true);
				this.activatePanel(id);
			}
		},

		// Make a panel active.
		activatePanel: function (id, unique) {
			if (this.panels[id] !== undefined && !this.panels[id].active) {
				var panel = this.panels[id];
				if (panel.content.length > 0) {
					panel.content.css('display', 'block');
				}

				var contentHeight = panel.content.find('.panel-content-data').height() + 70;
				if (contentHeight <= (window.innerHeight / 2)) {
					panel.content.height(contentHeight);
				}

				panel.button.addClass('active');
				panel.active = true;
				return true;
			}
			return false;
		},

		// Deactivate a panel. use true to hide all panels.
		deactivatePanel: function (id) {
			if (id === true) {
				for (var i in this.panels) {
					this.deactivatePanel(i);
				}
				return true;
			}
			if (this.panels[id] !== undefined) {
				var panel = this.panels[id];
				if (panel.content !== undefined) {
					panel.content.hide();
				}
				panel.button.removeClass('active');
				panel.active = false;
				return true;
			}
			return false;
		},

		// Bind events for all the collapsible arrays.
		makeNeatArray: function (lists) {
			lists.find('ul').hide()
				.parent().addClass('expandable collapsed');

			lists.on('click', 'li', function (event) {
				event.stopPropagation();
				$(this).children('ul').toggle().toggleClass('expanded collapsed');
			});
		}
	};
}();
DEBUGKIT.loader.register(DEBUGKIT.toolbar);

DEBUGKIT.module('historyPanel');
DEBUGKIT.historyPanel = function () {
	var toolbar = DEBUGKIT.toolbar,
		$ = DEBUGKIT.$,
		historyLinks;

	// Private methods to handle JSON response and insertion of
	// new content.
	var switchHistory = function (response) {

		historyLinks.removeClass('loading');

		$.each(toolbar.panels, function (id, panel) {
			if (panel.content === undefined || response[id] === undefined) {
				return;
			}

			var regionDiv = panel.content.find('.panel-resize-region');
			if (!regionDiv.length) {
				return;
			}

			var regionDivs = regionDiv.children();

			regionDivs.filter('div').hide();
			regionDivs.filter('.panel-history').each(function (i, panelContent) {
				var panelId = panelContent.id.replace('-history', '');
				if (response[panelId]) {
					panelContent = $(panelContent);
					panelContent.html(response[panelId]);
					var lists = panelContent.find('.depth-0');
					toolbar.makeNeatArray(lists);
				}
				panelContent.show();
			});
		});
	};

	// Private method to handle restoration to current request.
	var restoreCurrentState = function () {
		var id, i, panelContent, tag;

		historyLinks.removeClass('loading');

		$.each(toolbar.panels, function (panel, id) {
			if (panel.content === undefined) {
				return;
			}
			var regionDiv = panel.content.find('.panel-resize-region');
			if (!regionDiv.length) {
				return;
			}
			var regionDivs = regionDiv.children();
			regionDivs.filter('div').show()
				.end()
				.filter('.panel-history').hide();
		});
	};

	function handleHistoryLink(event) {
		event.preventDefault();

		historyLinks.removeClass('active');
		$(this).addClass('active loading');

		if (this.id === 'history-restore-current') {
			restoreCurrentState();
			return false;
		}

		var xhr = $.ajax({
			url: this.href,
			type: 'GET',
			dataType: 'json'
		});
		xhr.success(switchHistory).fail(function () {
			alert('History retrieval failed');
		});
	}

	return {
		init : function () {
			if (toolbar.panels.history === undefined) {
				return;
			}

			historyLinks = toolbar.panels.history.content.find('.history-link');
			historyLinks.on('click', handleHistoryLink);
		}
	};
}();
DEBUGKIT.loader.register(DEBUGKIT.historyPanel);

//Add events + behaviors for toolbar collapser.
DEBUGKIT.toolbarToggle = function () {
	var toolbar = DEBUGKIT.toolbar,
		$ = DEBUGKIT.$,
		Cookie = DEBUGKIT.Util.Cookie,
		toolbarHidden = false;

	return {
		init: function () {
			var button = $('#hide-toolbar'),
				self = this;

			button.on('click', function (event) {
				event.preventDefault();
				self.toggleToolbar();
			});

			var toolbarState = Cookie.read('toolbarDisplay');
			if (toolbarState !== 'show') {
				toolbarHidden = false;
				this.toggleToolbar();
			}
		},

		toggleToolbar: function () {
			var display = toolbarHidden ? 'show' : 'hide';
			$.each(toolbar.panels, function (i, panel) {
				$(panel.element)[display]();
				Cookie.write('toolbarDisplay', display);
			});
			toolbarHidden = !toolbarHidden;

			if (toolbarHidden) {
				$('#debug-kit-toolbar').addClass('minimized');
			} else {
				$('#debug-kit-toolbar').removeClass('minimized');
			}

			return false;
		}
	};
}();
DEBUGKIT.loader.register(DEBUGKIT.toolbarToggle);

DEBUGKIT.$(document).ready(function () {
	DEBUGKIT.loader.init();
});
