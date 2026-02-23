(function () {
  function handlePasswordForm() {
    const form = document.getElementById('user-password-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const currentEl = document.getElementById('user-current-password');
      const newEl = document.getElementById('user-new-password');
      const confirmEl = document.getElementById('user-new-password-confirm');

      const currentPassword = currentEl ? currentEl.value : '';
      const newPassword = newEl ? newEl.value : '';
      const confirmPassword = confirmEl ? confirmEl.value : '';

      if (!currentPassword || !newPassword || !confirmPassword) {
        if (window.showFeedback) window.showFeedback('All password fields are required.', 'danger');
        return;
      }

      if (newPassword !== confirmPassword) {
        if (window.showFeedback) window.showFeedback('New passwords do not match.', 'danger');
        return;
      }

      const fd = new FormData();
      fd.append('current_password', currentPassword);
      fd.append('new_password', newPassword);

      fetch('assets/php/user_api.php?action=change_password', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      })
        .then(function (r) { return r.json().catch(function () { return {}; }).then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (res) {
          if (!res.ok || !res.data || res.data.success === false) {
            var msg = res.data && res.data.error ? res.data.error : 'Unable to update password.';
            if (window.showFeedback) window.showFeedback(msg, 'danger');
            return;
          }

          if (currentEl) currentEl.value = '';
          if (newEl) newEl.value = '';
          if (confirmEl) confirmEl.value = '';

          if (window.showFeedback) window.showFeedback(res.data.message || 'Password updated successfully.', 'success');

          // Optionally close the modal after success
          var modalEl = document.getElementById('userModal');
          if (modalEl && window.bootstrap) {
            var instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            instance.hide();
          }
        })
        .catch(function () {
          if (window.showFeedback) window.showFeedback('Unable to update password.', 'danger');
        });
    });
  }

  function handleDeleteForm() {
    const form = document.getElementById('user-delete-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const passwordEl = document.getElementById('user-delete-password');
      const password = passwordEl ? passwordEl.value : '';

      if (!password) {
        if (window.showFeedback) window.showFeedback('Password is required to delete the account.', 'danger');
        return;
      }

      if (!window.confirm('Are you sure you want to delete your account? This cannot be undone.')) {
        return;
      }

      const fd = new FormData();
      fd.append('password', password);

      fetch('assets/php/user_api.php?action=delete_account', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      })
        .then(function (r) { return r.json().catch(function () { return {}; }).then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (res) {
          if (!res.ok || !res.data || res.data.success === false) {
            var msg = res.data && res.data.error ? res.data.error : 'Unable to delete account.';
            if (window.showFeedback) window.showFeedback(msg, 'danger');
            return;
          }

          if (window.showFeedback) window.showFeedback(res.data.message || 'Account deleted.', 'success');

          // Redirect to login page after deletion/logout
          window.location.href = 'index.php';
        })
        .catch(function () {
          if (window.showFeedback) window.showFeedback('Unable to delete account.', 'danger');
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    handlePasswordForm();
    handleDeleteForm();
  });
})();
