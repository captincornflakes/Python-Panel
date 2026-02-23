(function () {
  function getRoot() {
    var root = document.getElementById('feedback-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'feedback-root';
      root.className = 'position-fixed bottom-0 end-0 p-3';
      root.style.zIndex = '1080';
      document.body.appendChild(root);
    }
    return root;
  }

  function showFeedback(message, type) {
    type = (type || 'info').toLowerCase();
    var bsType = 'info';
    if (type === 'success') bsType = 'success';
    else if (type === 'error' || type === 'danger') bsType = 'danger';
    else if (type === 'warning') bsType = 'warning';

    var root = getRoot();
    var el = document.createElement('div');
    el.className = 'alert alert-' + bsType + ' shadow-sm mb-2 py-2 px-3 fade show';
    el.textContent = message;
    root.appendChild(el);

    setTimeout(function () {
      el.classList.remove('show');
      el.classList.add('hide');
      setTimeout(function () {
        if (el.parentNode) el.parentNode.removeChild(el);
      }, 300);
    }, 3000);
  }

  window.showFeedback = showFeedback;
})();
