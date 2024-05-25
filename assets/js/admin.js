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

  // Remove form update notice after 2 seconds
  if ($('.fly-form-notice').length) {
    setTimeout(function () {
      $('.fly-form-notice').fadeOut('slow', function () {
        $(this).remove();
      });
    }, 2000);
  }

})(jQuery);
