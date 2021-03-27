
(function(){

	var callback = function(action, el, pos) {
        	var job = $(el).attr('job')
				,url;
        	
        	switch(action){
				case 'viewRoutings':
					url = 'http://intranet/job%20order/routing%20lookup/?jobOrder=' + job;
					break;
				case 'viewBom':
					url = 'http://intranet/job%20order/bom%20lookup/?jobOrder=' + job;
					break;
				case 'viewLabor':
					url= 'http://intranet/job order/routing lookup/labor_lookup.php?jobOrder=' + job;
					break;
			}
			window.open(url);
    	};

	var el = $(".contextMenu");
	
	var o = {
		inSpeed: 75
		,outSpeed: 50
		,menu: 'jobMenu'
	};

$(document.body).on('contextmenu',function(e){

	var srcElement = $(e.target);
	
	if(!srcElement.hasClass('job')){
		return true;
	}

	e.preventDefault();
	e.stopPropagation();
	
	
	$("#jobMenuJob").html(srcElement.attr('job'));
	
	// Hide context menus that may be showing
	$(".contextMenu").hide();
	// Get this context menu
	var menu = $('#' + o.menu)
		,offset = srcElement.offset();
		
	if( $(el).hasClass('disabled') ) return false;
	
	// Detect mouse position
	var d = {}, x, y;
	if( self.innerHeight ) {
		d.pageYOffset = self.pageYOffset;
		d.pageXOffset = self.pageXOffset;
		d.innerHeight = self.innerHeight;
		d.innerWidth = self.innerWidth;
	} else if( document.documentElement &&
		document.documentElement.clientHeight ) {
		d.pageYOffset = document.documentElement.scrollTop;
		d.pageXOffset = document.documentElement.scrollLeft;
		d.innerHeight = document.documentElement.clientHeight;
		d.innerWidth = document.documentElement.clientWidth;
	} else if( document.body ) {
		d.pageYOffset = document.body.scrollTop;
		d.pageXOffset = document.body.scrollLeft;
		d.innerHeight = document.body.clientHeight;
		d.innerWidth = document.body.clientWidth;
	}
	(e.pageX) ? x = e.pageX : x = e.clientX + d.scrollLeft;
	(e.pageY) ? y = e.pageY : y = e.clientY + d.scrollTop;
	
	// Show the menu
	$(document).unbind('click');
	$(menu).css({ top: y, left: x }).fadeIn(o.inSpeed);
	// Hover events
	$(menu).find('A').mouseover( function() {
		$(menu).find('LI.hover').removeClass('hover');
		$(this).parent().addClass('hover');
	}).mouseout( function() {
		$(menu).find('LI.hover').removeClass('hover');
	});
	
	// Keyboard
	$(document).keypress( function(e) {
		switch( e.keyCode ) {
			case 38: // up
				if( $(menu).find('LI.hover').size() == 0 ) {
					$(menu).find('LI:last').addClass('hover');
				} else {
					$(menu).find('LI.hover').removeClass('hover').prevAll('LI:not(.disabled)').eq(0).addClass('hover');
					if( $(menu).find('LI.hover').size() == 0 ) $(menu).find('LI:last').addClass('hover');
				}
			break;
			case 40: // down
				if( $(menu).find('LI.hover').size() == 0 ) {
					$(menu).find('LI:first').addClass('hover');
				} else {
					$(menu).find('LI.hover').removeClass('hover').nextAll('LI:not(.disabled)').eq(0).addClass('hover');
					if( $(menu).find('LI.hover').size() == 0 ) $(menu).find('LI:first').addClass('hover');
				}
			break;
			case 13: // enter
				$(menu).find('LI.hover A').trigger('click');
			break;
			case 27: // esc
				$(document).trigger('click');
			break
		}
});

// When items are selected
$('#' + o.menu).find('A').unbind('click');
$('#' + o.menu).find('LI:not(.disabled) A').click( function() {
	$(document).unbind('click').unbind('keypress');
	$(".contextMenu").hide();
	// Callback
	if( callback ) callback( $(this).attr('href').substr(1), $(srcElement), {x: x - offset.left, y: y - offset.top, docX: x, docY: y} );
	return false;
});

// Hide bindings
setTimeout( function() { // Delay for Mozilla
	$(document).click( function() {
		$(document).unbind('click').unbind('keypress');
		$(menu).fadeOut(o.outSpeed);
		return false;
	});
}, 0);

});

})();
