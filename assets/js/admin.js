(function ($) {

  var $fcgiButton = $('#fastcgi-settings-button'),
    $fcgiClose = $('#fastcgi-settings-close'),
    $fcgiSettings = $('#fastcgi-settings');

  // show/hide page cache settings
  $fcgiButton.on('click', function (e) {
    $(this).toggleClass('active');

    if ($(this).hasClass('active')) {
      $fcgiSettings.slideDown('fast');
    } else {
      $fcgiSettings.slideUp('fast');
    }
  });

  // close page cache settings
  $fcgiClose.on('click', function (e) {
    $fcgiButton.removeClass('active');
    $fcgiSettings.slideUp('fast');
  });

  // Remove page cache notice after 2 seconds
  if ($('#fly-page-cache-notice').length) {
    setTimeout(function () {
      $('#fly-page-cache-notice').fadeOut('slow');
    }, 2000);
  }
})(jQuery);
