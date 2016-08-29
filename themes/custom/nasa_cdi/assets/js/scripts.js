(function($) {
	function toggleMenuActiveClass() {
		var menu_btn = $('.navbar-toggle');

		$( menu_btn ).on('click', function() {
			$( this ).toggleClass('active');
		});
	}

	function toggleMenuSearchActiveClass() {
		var menu = $('.navbar-primary');
		var menu_search_btn = $('.menu .search-submit');
		var menu_search_form = $('.menu .search-form');
		var menu_search_field = $('.menu .search-field');

		$( menu_search_btn ).on('click', function( event ) {
			if( ! $( menu ).hasClass('in') && ! $( menu_search_form ).hasClass('active') ) {
				event.preventDefault();
				$( menu_search_form ).addClass('active');
				$( menu_search_field ).focus();
			}
		});

		// If click target is outside search form remove the active class from the search form
		$( document ).click( function( event ) {
		    if( $( event.target ).closest( menu_search_form ).length == 0 ) {
				$( menu_search_form ).removeClass('active');
		    }
		});
	}

	Drupal.behaviors.nasa_cdi = {
	    attach: function( context, settings ) {
	    	toggleMenuActiveClass();
	    	toggleMenuSearchActiveClass();
	    }
	};
})(jQuery);