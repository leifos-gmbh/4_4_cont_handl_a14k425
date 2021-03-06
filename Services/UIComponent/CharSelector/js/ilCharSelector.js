/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Character selector object 
 * (anonymous constructor function)
 */
il.CharSelector = new function() {
	
	/**
	 * Self reference for usage in event handlers
	 * @type object
	 * @private
	 */
	var self = this;
	
	/**
	 * Maximum number of buttons shown on one sub page 
	 * @type integer
	 * @private
	 */
	var page_limit = 64;
			
	/**
	 * Number of sub pages
	 * (Needs to be calculated when a page changes)
	 * @type integer
	 * @private
	 */
	var page_subpages = 0;
		
			
	/**
	 * Configuration of the panel
	 * Has to be provided as JSON when init() is called
	 * @type object
	 * @private
	 */		
	var config = {
		pages: [],				// list character pages
		open: 0,				// panel is open
		current_page: 0,		// current block page 
		current_subpage: 0,		// current sub page
		ajax_url: ''			// ajax_url
	};
	
	/**
	 * Texts to be dynamically rendered
	 * @type object
	 * @private
	 */
	var texts = {
		page: ''
	};
	
	
	/**
	 * Initialize the selector
	 * called from ilTemplate::addOnLoadCode, 
	 * added by ilCharSelectorGUI::addToPage()
	 * @param object	start configuration as JSON
	 * @param object	texts to be dynamically rendered
	 */
	this.init = function(a_config, a_texts) {
		config = a_config;
		texts = a_texts;
	
		// basic condition		
		if (config.pages.length < 1) { return; }
		
		if (config.current_page >= config.pages.length) {
			config.current_page = 0;
		}
		self.countSubPages();
		if (config.current_subpage >= page_subpages) {
			config.current_subpage = 0;
		}
		
		if (config.open) {
			self.openPanel();
		}
		
		$('.ilCharSelectorToggle').mousedown(function(){return false;});
		$('.ilCharSelectorToggle').click(self.togglePanel); 
	};
	
	
	/**
	 * Initialize the selector panel and adds it to the DOM
	 */
	this.initPanel = function() {
		if ($('#mainspacekeeper').length > 0)
		{
			$('#mainspacekeeper').prepend($('#ilCharSelectorTemplate').html());
		}
		else if ($('#tst_output').length > 0)
		{
			$('body').prepend($('#ilCharSelectorTemplate').html());
		}
		
		$('#ilCharSelectorScroll').mousedown(function(){return false;});
		$('#ilCharSelectorPrevPage').mousedown(function(){return false;});
		$('#ilCharSelectorNextPage').mousedown(function(){return false;});
		$('#ilCharSelectorPrevPage').click(self.previousPage);
		$('#ilCharSelectorNextPage').click(self.nextPage);
		$('#ilCharSelectorSelPage').change(self.selectPage);
		$('#ilCharSelectorSelSubPage').change(self.selectSubPage);
		
		self.renderPage();
	};
	
	/**
	 * Open the selector panel
	 */
	this.openPanel = function() {
		if ($('#ilCharSelectorPanel').length == 0) 
		{
			self.initPanel();
		}
		$('#ilCharSelectorPanel').show();	
		
		if ($('#fixed_content').length > 0)
		{
			// normal page
			$('body').addClass('withCharSelector');
		}
		else if ($('#tst_output').length > 0)
		{
			// test kiosk mode 
			$('body').removeClass('kiosk');
			$('body').addClass('kioskWithCharSelector');
		}

		$('.ilCharSelectorToggle').addClass('ilCharSelectorToggleOpen');
		config.open = 1;
	};

	/**
	 * Close the selector panel
	 */
	this.closePanel = function() {
		$('#ilCharSelectorPanel').hide();
		
		if ($('#fixed_content').length > 0)
		{
			// normal page
			$('body').removeClass('withCharSelector');
		}
		else if ($('#tst_output').length > 0)
		{
			// test kiosk mode
			$('body').removeClass('kioskWithCharSelector');
			$('body').addClass('kiosk');		
		}

		$('.ilCharSelectorToggle').removeClass('ilCharSelectorToggleOpen');
		config.open = 0;
	};

	/**
	 * Toggle the visibility of the selector panel
	 * @return boolean false to prevent further event handling
	 */
	this.togglePanel = function() {
		if (config.open) {
			self.closePanel();		
		} else {
			self.openPanel();
		}
		self.sendState();
		return false;
	};
	
	
	/**
	 * Move to page chosen from the selector
	 */
	this.selectPage = function() {
		config.current_page = $(this).val();
		self.countSubPages();
		config.current_subpage = 0;
		self.renderPage();
		self.sendState();
	};
	
	
	/**
	 * Move to sub page chosen from the selector
	 */
	this.selectSubPage = function() {
		config.current_subpage = $(this).val();
		self.renderPage();
		self.sendState();
	};

	
	/**
	 * Move to the previous page
	 */
	this.previousPage = function() {
		if (config.current_subpage > 0) {
			config.current_subpage--;
			self.renderPage();
			self.sendState();
		}
		else if (config.current_page > 0) {
			config.current_page--;
			self.countSubPages();
			config.current_subpage = Math.max(0, page_subpages - 1);
			self.renderPage();
			self.sendState();
		}
	};
	
	
	/**
	 * Move to the next page
	 */
	this.nextPage = function() {
		if (config.current_subpage < page_subpages - 1) {
			config.current_subpage++;
			self.renderPage();
			self.sendState();
		}
		else if (config.current_page < config.pages.length - 1) {
			config.current_page++;
			self.countSubPages();
			config.current_subpage = 0;
			self.renderPage();
			self.sendState();
		}
	};

	/** 
	 * Send the current panel state per ajax
	 */
	this.sendState = function() {
		$.get(config.ajax_url, {
			'open': config.open, 
			'current_page': config.current_page,
			'current_subpage': config.current_subpage
		}).done(function(data) {
			// alert(data);
		});
	}
	
	/**
	 * Count the number of sub pages of the current page
	 * and set the private class variable page_subpages
	 */
	this.countSubPages = function () {
		var page = config.pages[config.current_page];
		var buttons = 0;
		// start with 1 (0 is page name)
		for (var i = 1; i < page.length; i++) {		
			if (page[i] instanceof Array) {
				buttons += Math.max(0, page[i][1] - page[i][0] + 1);
			} 
			else {
				buttons += 1;
			}
		}
		page_subpages = Math.ceil(buttons / page_limit);
	}
	
	/**
	 * Render the current page of characters
	 */
	this.renderPage = function() {
		
		// adjust the navigation
		//
		$('#ilCharSelectorSelPage').val(config.current_page);
		if (config.current_page == 0 && 
			config.current_subpage == 0) 
		{
			$('#ilCharSelectorPrevPage').addClass('ilCharSelectorDisabled');
		} else 
		{
			$('#ilCharSelectorPrevPage').removeClass('ilCharSelectorDisabled');
		}
		if (config.current_page >= config.pages.length - 1 && 
			config.current_subpage >= page_subpages -1) 
		{
			$('#ilCharSelectorNextPage').addClass('ilCharSelectorDisabled');
		} else 
		{
			$('#ilCharSelectorNextPage').removeClass('ilCharSelectorDisabled');
		}
		
		// fill the subpage navigation
		var options = '';
		for (var i = 0; i <= page_subpages - 1; i++) {
			options = options 
					+ '<option value="' + i + '">' 
					+ texts.page + ' ' + (i+1) + ' / ' + page_subpages
					+ '</option>';
		}
		$('#ilCharSelectorSelSubPage').html(options);
		$('#ilCharSelectorSelSubPage').val(config.current_subpage);
		
		// clear the character area
		$('#ilCharSelectorChars').off('mousedown');
		$('#ilCharSelectorChars').off('click');
		$('#ilCharSelectorChars').empty();
		
		// render the char buttons
		var page = config.pages[config.current_page];
		var first = config.current_subpage * page_limit;
		var last = config.current_subpage * page_limit + page_limit - 1;
		var button = 0;
		var html = '';
		
		// start with index 1 (0 is page name)
		for (var i = 1; i < page.length; i++) {
			
			if (page[i] instanceof Array) {
				// insert a range of characters
				for (var c = page[i][0]; c <= page[i][1]; c++) {
					if (button >= first && button <= last)
					{
						html = html + '<a>' + String.fromCharCode(c) + '</a> ';
					}
					button++;
				}
			} 
			else {
				// insert one or more chars on one button
				if (button >= first && button <= last)
				{
					html = html + '<a>' + page[i] + '</a> ';
				}
				button++;
			}
		}
		$('#ilCharSelectorChars').append(html);
		
		// bind the click event to all anchors
		$('#ilCharSelectorChars a').click(self.insertChar); 
		$('#ilCharSelectorChars a').mouseover(self.showPreview); 
		$('#ilCharSelectorChars a').mouseout(self.hidePreview); 
		
	};
	
	this.showPreview = function() {
		$('#ilCharSelectorPreview').html($(this).text());
		$('#ilCharSelectorPreview').show();
	}
	
	this.hidePreview = function() {
		$('#ilCharSelectorPreview').hide();
	}

	
	/**
	 * Insert a character to the current text field
	 * @return boolean false to prevent further event handling
	 */
	this.insertChar = function() {
		
		// 'this' is the element that raised the event
		var char = $(this).text();

		// get the focussed element an check its type
		var doc = document;
		var element = doc.activeElement;
		
		// special handling of tinyMCE
		if (element.tagName == 'IFRAME') {
			if ($(element).parent().hasClass('mceIframeContainer')) {
				tinymce.activeEditor.execCommand('mceInsertContent', false, char);
				return;
			}
		}
		
		// normal form elements
		switch (element.tagName) {
			case "INPUT":
				switch ($(element).attr('type').toLowerCase()) {
					case '':
					case 'text':
					case 'password':
					case 'email':
					case 'search':
					case 'url':
						break;					
					default:
						return false;	// no insertion possible
				}
				break;
			case "TEXTAREA":
				break;
			default:
				return false;			// no insertion possible
		}
		
		// insert the char in the active
		if (doc.selection) {
			var sel = doc.selection.createRange();
			sel.text = char;

		} else if (element.selectionStart || element.selectionStart === 0) 
		{
			var startPos = element.selectionStart;
			var endPos = element.selectionEnd;
			var scrollTop = element.scrollTop;
			element.value = element.value.substring(0, startPos) + char + element.value.substring(endPos, element.value.length);
			element.selectionStart = startPos + char.length;
			element.selectionEnd = startPos + char.length;
			element.scrollTop = scrollTop;
		} else {
			element.value += char;
		}
	
		return false;
	};
	
	/**
	 * Checks if the page has input targets
	 * @return boolean
	 */
	this.pageHasInput = function() {
	
		var inputs = 
			'textarea'
			+ ',input[type="text"]:not([readonly])'
			+ ',input[type="password"]:not([readonly])'
			+ ',input[type="email"]:not([readonly])'
			+ ',input[type="search"]:not([readonly])'
			+ ',input[type="url"]:not([readonly])';
		
		if ($('#fixed_content').has(inputs) 
			|| $('#tst_output').length > 0 ) {
			return true;
		} else {
			return false;
		}
	};
};
