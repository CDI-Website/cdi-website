(function($, Drupal){
  Drupal.behaviors.contentLimiter = {
    attach: function (context, settings) {
      $('.entity-list').hideMaxListItems({
        'max': 10,
        'speed': 500,
        'moreText': Drupal.t('Display More ([COUNT])'),
        'lessText': Drupal.t('Display Less'),
        'moreHTML': '<p class="maxlist-more"><a class="btn btn-default" href="#"></a></p>'
      });
    }
  };
})(jQuery, Drupal);