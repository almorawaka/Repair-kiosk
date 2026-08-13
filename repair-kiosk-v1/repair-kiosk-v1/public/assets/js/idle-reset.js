(function () {
  var script = document.currentScript;
  var seconds = parseInt(script.getAttribute('data-idle-seconds'), 10) || 90;
  var timer = null;

  function reset() {
    clearTimeout(timer);
    timer = setTimeout(function () {
      window.location.href = window.location.origin + window.location.pathname.split('/kiosk')[0] + '/kiosk';
    }, seconds * 1000);
  }

  ['click', 'touchstart', 'keydown'].forEach(function (evt) {
    document.addEventListener(evt, reset);
  });
  reset();
})();
