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

  var colors = ['#F87171', '#16a34a', '#FBBF24',];
  var opcacheMemory = $('#opcache-memory'),
    opcacheKeys = $('#opcache-keys'),
    opcacheHits = $('#opcache-hits');

  new Chart(opcacheMemory, {
    type: 'doughnut',
    data: {
      labels: ['Used', 'Free', 'Wasted'],
      datasets: [{
        data: [80, 15, 5],
        backgroundColor: colors,
      }],
      hoverOffset: 10
    },
  });

  new Chart(opcacheKeys, {
    type: 'doughnut',
    data: {
      labels: ['Used', 'Free', 'Wasted'],
      datasets: [{
        data: [80, 15, 5],
        backgroundColor: colors,
      }],
      hoverOffset: 10
    },
  });

  new Chart(opcacheHits, {
    type: 'doughnut',
    data: {
      labels: ['Used', 'Free', 'Wasted'],
      datasets: [{
        data: [80, 15, 5],
        backgroundColor: colors,
      }],
      hoverOffset: 10
    },
  });

})(jQuery);
