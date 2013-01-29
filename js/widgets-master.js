jQuery(document).ready(function() {
	jQuery('.widgets-master-tabs li a').each(function(i) {
		var thisTab = jQuery(this).parent().attr('class').replace(/active /, '');
		if ( 'active' != jQuery(this).attr('class') ) {
			jQuery('div.' + thisTab).hide();
		}
		jQuery('div.' + thisTab).addClass('widgets-master-tab-content');
	});
	
	jQuery('.widgets-master-tabs li a').live('click', function() {
		var thisTab = jQuery(this).parent().attr('class').replace(/active /, '');
		// hide all child content
		jQuery(this).parent().parent().parent().children('div').hide();

		// remove all active tabs
		jQuery(this).parent().parent('ul').find('li.active').removeClass('active');

		// show selected content
		jQuery(this).parent().parent().parent().find('div.' + thisTab).show();
		jQuery(this).parent().parent().parent().find('li.' + thisTab).addClass('active');
	});

	jQuery('.widgets-master-tabs').show();
});
