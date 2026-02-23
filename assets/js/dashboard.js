(function () {
  const body = document.getElementById('projects-body');
  let projectsCache = [];

  function sanitizeScreenName(value) {
    return (value || '').toString().replace(/[^a-zA-Z0-9_-]/g, '_');
  }

  function badge(status) {
    status = (status || '').toLowerCase();
    let cls = 'bg-secondary';
    if (status === 'running') cls = 'bg-success';
    else if (status === 'error') cls = 'bg-danger';
    else if (status === 'stopped') cls = 'bg-secondary';
    return '<span class="badge ' + cls + '">' + (status || 'unknown') + '</span>';
  }

  function render(list) {
    body.innerHTML = '';
    if (!list || !list.length) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = 6;
      td.className = 'text-center text-secondary';
      td.textContent = 'No projects yet.';
      tr.appendChild(td);
      body.appendChild(tr);
      return;
    }

    list.forEach(p => {
      const tr = document.createElement('tr');
      const screenName = p.screen || '';
      const status = (p.status || '').toLowerCase();
      const startDisabled = status === 'running';
      const stopDisabled = status === 'offline';
      const runDisabled = status === 'running';
      tr.innerHTML =
        '<td>' + (p.name || '') + '</td>' +
        '<td>' + (p.mode || '') + '</td>' +
        '<td>' + (screenName ? '<button class="btn btn-sm btn-outline-info js-view-screen" type="button">View</button>' : '') + '</td>' +
        '<td>' + (p.start_file || '') + '</td>' +
        '<td>' + badge(p.status) + '</td>' +
        '<td class="text-end">' +
          '<button class="btn btn-sm btn-success me-1 js-start-project" type="button"' + (startDisabled ? ' disabled' : '') + '><i class="bi bi-play-fill"></i></button>' +
          '<button class="btn btn-sm btn-warning me-1 js-stop-project" type="button"' + (stopDisabled ? ' disabled' : '') + '><i class="bi bi-stop-fill"></i></button>' +
          '<button class="btn btn-sm btn-outline-info me-1 js-status-project" type="button"><i class="bi bi-arrow-repeat"></i></button>' +
          '<button class="btn btn-sm btn-outline-secondary me-1 js-project-files" type="button"><i class="bi bi-folder2-open"></i></button>' +
          '<button class="btn btn-sm btn-outline-light me-1 js-edit-project" type="button"' + (runDisabled ? ' disabled' : '') + '><i class="bi bi-pencil-square"></i></button>' +
          '<button class="btn btn-sm btn-outline-danger js-delete-project" type="button"' + (runDisabled ? ' disabled' : '') + '><i class="bi bi-trash"></i></button>' +
        '</td>';
      tr.dataset.projectId = p.id || '';
      tr.dataset.projectName = p.name || '';
      tr.dataset.screenName = screenName || '';
      body.appendChild(tr);
    });
  }

  function api(url) {
    return fetch(url, { credentials: 'same-origin' }).then(r => {
      if (!r.ok) throw new Error('HTTP_' + r.status);
      return r.json();
    });
  }

  function updateRowStatus(id, status, screen) {
    const tr = body.querySelector('tr[data-project-id="' + id + '"]');
    if (!tr) return;
    const cells = tr.querySelectorAll('td');
    if (cells.length >= 5) {
      cells[4].innerHTML = badge(status);
    }
    // Enable/disable start/stop/edit/delete buttons based on status
    const startBtn = tr.querySelector('.js-start-project');
    const stopBtn = tr.querySelector('.js-stop-project');
    const editBtn = tr.querySelector('.js-edit-project');
    const deleteBtn = tr.querySelector('.js-delete-project');
    const s = (status || '').toLowerCase();
    if (startBtn) startBtn.disabled = (s === 'running');
    if (stopBtn) stopBtn.disabled = (s === 'offline');
    const running = (s === 'running');
    if (editBtn) editBtn.disabled = running;
    if (deleteBtn) deleteBtn.disabled = running;
    // Optionally update the screen column and dataset as well if provided
    if (typeof screen !== 'undefined' && cells.length >= 3) {
      const newScreen = screen || '';
      tr.dataset.screenName = newScreen;
      cells[2].innerHTML = newScreen ? '<button class="btn btn-sm btn-outline-info js-view-screen" type="button">View</button>' : '';
    }
  }

  function loadProjects() {
    api('assets/php/project_api.php?action=get')
      .then(d => {
        projectsCache = d.projects || [];
        render(projectsCache);
      })
      .catch(console.error);
  }

  function bindTableActions() {
    if (!body) return;
    body.addEventListener('click', e => {
      const filesBtn = e.target.closest('.js-project-files');
      if (filesBtn) {
        const tr = filesBtn.closest('tr');
        if (!tr) return;
        const id = tr.dataset.projectId;
        const name = tr.dataset.projectName || '';
        if (window.openFileManager && id) {
          window.openFileManager(id, name || id);
        } else if (window.showFeedback) {
          window.showFeedback('File manager is not available.', 'danger');
        }
        return;
      }

      const deleteBtn = e.target.closest('.js-delete-project');
      if (deleteBtn) {
        const tr = deleteBtn.closest('tr');
        if (!tr) return;
        const id = tr.dataset.projectId;
        const name = tr.dataset.projectName || '';
        if (!id) return;
        if (!window.confirm('Delete project "' + (name || id) + '"? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('id', id);
        fetch('assets/php/project_api.php?action=delete', {
          method: 'POST',
          credentials: 'same-origin',
          body: fd
        })
          .then(r => {
            if (!r.ok) throw new Error('HTTP_' + r.status);
            return r.json();
          })
          .then(() => {
            loadProjects();
            if (window.showFeedback) window.showFeedback('Project deleted.', 'success');
          })
          .catch(console.error);
        return;
      }

      const editBtn = e.target.closest('.js-edit-project');
      if (editBtn) {
        const tr = editBtn.closest('tr');
        if (!tr) return;
        const id = tr.dataset.projectId;
        if (!id) return;
        const project = projectsCache.find(p => p.id === id);
        const modalEl = document.getElementById('editProjectModal');
        const form = document.getElementById('edit-project-form');
        if (!modalEl || !form || !project) return;
        form.querySelector('#edit-project-id').value = project.id || '';
        form.querySelector('#edit-project-name').value = project.name || '';
        form.querySelector('#edit-project-start').value = project.start_file || '';
        const fileInput = form.querySelector('#edit-project-archive');
        if (fileInput) fileInput.value = '';
        if (window.bootstrap) {
          const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
          instance.show();
        }
        return;
      }

      const startBtn = e.target.closest('.js-start-project');
      if (startBtn) {
        const tr = startBtn.closest('tr');
        if (!tr) return;
        const id = tr.dataset.projectId;
        if (!id) return;
        const fd = new FormData();
        fd.append('id', id);
        fetch('assets/php/project_api.php?action=start', {
          method: 'POST',
          credentials: 'same-origin',
          body: fd
        })
          .then(r => {
            if (!r.ok) throw new Error('HTTP_' + r.status);
            return r.json();
          })
          .then(() => {
            loadProjects();
            if (window.showFeedback) window.showFeedback('Start command sent.', 'info');
          })
          .catch(console.error);
        return;
      }

      const stopBtn = e.target.closest('.js-stop-project');
      if (stopBtn) {
        const tr = stopBtn.closest('tr');
        if (!tr) return;
        const id = tr.dataset.projectId;
        if (!id) return;
        const fd = new FormData();
        fd.append('id', id);
        fetch('assets/php/project_api.php?action=stop', {
          method: 'POST',
          credentials: 'same-origin',
          body: fd
        })
          .then(r => {
            if (!r.ok) throw new Error('HTTP_' + r.status);
            return r.json();
          })
          .then(() => {
            loadProjects();
            if (window.showFeedback) window.showFeedback('Stop command sent.', 'info');
          })
          .catch(console.error);
        return;
      }

      const statusBtn = e.target.closest('.js-status-project');
      if (statusBtn) {
        const tr = statusBtn.closest('tr');
        if (!tr) return;
        const id = tr.dataset.projectId;
        if (!id) return;
        fetch('assets/php/project_api.php?action=status&id=' + encodeURIComponent(id), {
          credentials: 'same-origin'
        })
          .then(r => {
            if (!r.ok) throw new Error('HTTP_' + r.status);
            return r.json();
          })
          .then(data => {
            if (data && data.status) {
              updateRowStatus(id, data.status, data.screen);
            }
            if (window.showFeedback) {
              const status = data && data.status ? data.status : 'unknown';
              window.showFeedback('Status: ' + status, 'info');
            }
          })
          .catch(console.error);
      }
    });
  }

  function bindNewProjectForm() {
    const form = document.getElementById('new-project-form');
    if (!form) return;
    form.addEventListener('submit', e => {
      e.preventDefault();
      const fd = new FormData(form);
      // status defaults to offline in API
      fetch('assets/php/project_api.php?action=create', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      })
        .then(r => {
          if (!r.ok) throw new Error('HTTP_' + r.status);
          return r.json();
        })
        .then(() => {
          const modalEl = document.getElementById('newProjectModal');
          if (modalEl && window.bootstrap) {
            const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            instance.hide();
          }
          form.reset();
          loadProjects();
          if (window.showFeedback) window.showFeedback('Project created.', 'success');
        })
        .catch(console.error);
    });
  }

  function bindEditProjectForm() {
    const form = document.getElementById('edit-project-form');
    if (!form) return;
    form.addEventListener('submit', e => {
      e.preventDefault();
      const id = form.querySelector('#edit-project-id').value.trim();
      const name = form.querySelector('#edit-project-name').value.trim();
      const start = form.querySelector('#edit-project-start').value.trim();
      const fileInput = form.querySelector('#edit-project-archive');
      const file = fileInput && fileInput.files ? fileInput.files[0] : null;
      if (!id) return;

      const fdUpdate = new FormData();
      fdUpdate.append('id', id);
      if (name) fdUpdate.append('name', name);
      if (start) fdUpdate.append('start', start);

      const doUpload = () => {
        if (!file) return Promise.resolve();
        const fdUpload = new FormData();
        fdUpload.append('id', id);
        fdUpload.append('archive', file);
        return fetch('assets/php/project_api.php?action=upload', {
          method: 'POST',
          credentials: 'same-origin',
          body: fdUpload
        }).then(r => {
          if (!r.ok) throw new Error('HTTP_' + r.status);
          return r.json();
        });
      };

      fetch('assets/php/project_api.php?action=update', {
        method: 'POST',
        credentials: 'same-origin',
        body: fdUpdate
      })
        .then(r => {
          if (!r.ok) throw new Error('HTTP_' + r.status);
          return r.json();
        })
        .then(() => doUpload())
        .then(() => {
          const modalEl = document.getElementById('editProjectModal');
          if (modalEl && window.bootstrap) {
            const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            instance.hide();
          }
          form.reset();
          loadProjects();
          if (window.showFeedback) window.showFeedback('Project updated.', 'success');
        })
        .catch(console.error);
    });
  }

  function init() {
    loadProjects();
    bindNewProjectForm();
    bindTableActions();
    bindEditProjectForm();
    // Periodically refresh just the status from the API once per minute
    setInterval(() => {
      if (!projectsCache || !projectsCache.length) return;
      projectsCache.forEach(p => {
        if (!p.id) return;
        fetch('assets/php/project_api.php?action=status&id=' + encodeURIComponent(p.id), {
          credentials: 'same-origin'
        })
          .then(r => {
            if (!r.ok) throw new Error('HTTP_' + r.status);
            return r.json();
          })
          .then(data => {
            if (data && data.status) {
              updateRowStatus(p.id, data.status, data.screen);
            }
          })
          .catch(() => {});
      });
    }, 60000);
  }

  document.addEventListener('DOMContentLoaded', init);
})();
