(function ($, Drupal) {
  Drupal.behaviors.expandingSearchBlock = {
    attach: function (context, settings) {
      $('#header-search-button').on('click', function () {
        $('#header-search-input').focus();
      });
    }
  };
})(jQuery, Drupal);