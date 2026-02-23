(function () {
  const apiBase = 'assets/php/file_api.php';

  let state = {
    projectId: null,
    projectName: '',
    currentPath: '',
    currentEditPath: null,
  };

  let modalEl,
      modal,
      tableBody,
      projectNameEl,
      currentPathEl,
      uploadInput,
      uploadBtn,
      upBtn,
      editorFilenameEl,
      editorTextarea,
      saveBtn;

  function ensureElements() {
    if (!modalEl) modalEl = document.getElementById('fileManagerModal');
    if (!tableBody) tableBody = document.getElementById('file-manager-body');
    if (!projectNameEl) projectNameEl = document.getElementById('file-manager-project-name');
    if (!currentPathEl) currentPathEl = document.getElementById('file-manager-current-path');
    if (!uploadInput) uploadInput = document.getElementById('file-manager-upload-file');
    if (!uploadBtn) uploadBtn = document.getElementById('file-manager-upload-btn');
    if (!upBtn) upBtn = document.getElementById('file-manager-up-btn');
    if (!editorFilenameEl) editorFilenameEl = document.getElementById('file-manager-editor-filename');
    if (!editorTextarea) editorTextarea = document.getElementById('file-manager-editor-content');
    if (!saveBtn) saveBtn = document.getElementById('file-manager-save-btn');

    if (modalEl && !modal && window.bootstrap) {
      modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    }
  }

  function fmtBytes(size) {
    if (size === null || typeof size === 'undefined') return '';
    const n = Number(size) || 0;
    if (n < 1024) return n + ' B';
    const kb = n / 1024;
    if (kb < 1024) return kb.toFixed(1) + ' KB';
    const mb = kb / 1024;
    if (mb < 1024) return mb.toFixed(1) + ' MB';
    const gb = mb / 1024;
    return gb.toFixed(1) + ' GB';
  }

  function fmtPath(path) {
    if (!path) return '/';
    return '/' + path;
  }

  function apiList(projectId, path) {
    const params = new URLSearchParams();
    params.set('action', 'list');
    params.set('project_id', projectId);
    if (path) params.set('path', path);
    return fetch(apiBase + '?' + params.toString(), {
      credentials: 'same-origin'
    }).then(r => {
      if (!r.ok) throw new Error('HTTP_' + r.status);
      return r.json();
    });
  }

  function apiGet(projectId, path) {
    const params = new URLSearchParams();
    params.set('action', 'get');
    params.set('project_id', projectId);
    params.set('path', path);
    return fetch(apiBase + '?' + params.toString(), {
      credentials: 'same-origin'
    }).then(r => {
      if (!r.ok) throw new Error('HTTP_' + r.status);
      return r.json();
    });
  }

  function apiSave(projectId, path, content) {
    const params = new URLSearchParams();
    params.set('action', 'save');
    params.set('project_id', projectId);
    params.set('path', path);
    return fetch(apiBase + '?' + params.toString(), {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: 'content=' + encodeURIComponent(content || '')
    }).then(r => {
      if (!r.ok) throw new Error('HTTP_' + r.status);
      return r.json();
    });
  }

  function apiDelete(projectId, path) {
    const fd = new FormData();
    fd.append('project_id', projectId);
    fd.append('path', path);
    return fetch(apiBase + '?action=delete', {
      method: 'POST',
      credentials: 'same-origin',
      body: fd
    }).then(r => {
      if (!r.ok) throw new Error('HTTP_' + r.status);
      return r.json();
    });
  }

  function apiRename(projectId, path, newName) {
    const fd = new FormData();
    fd.append('project_id', projectId);
    fd.append('path', path);
    fd.append('new_name', newName);
    return fetch(apiBase + '?action=rename', {
      method: 'POST',
      credentials: 'same-origin',
      body: fd
    }).then(r => {
      if (!r.ok) throw new Error('HTTP_' + r.status);
      return r.json();
    });
  }

  function apiUpload(projectId, path, file) {
    const fd = new FormData();
    fd.append('project_id', projectId);
    if (path) fd.append('path', path);
    fd.append('file', file);
    return fetch(apiBase + '?action=upload', {
      method: 'POST',
      credentials: 'same-origin',
      body: fd
    }).then(r => {
      if (!r.ok) throw new Error('HTTP_' + r.status);
      return r.json();
    });
  }

  function renderList(entries) {
    if (!tableBody) return;
    tableBody.innerHTML = '';

    if (!entries || !entries.length) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = 4;
      td.className = 'text-center text-secondary';
      td.textContent = 'Folder is empty.';
      tr.appendChild(td);
      tableBody.appendChild(tr);
      return;
    }

    const sorted = entries.slice().sort((a, b) => {
      const ad = a.type === 'dir' ? 0 : 1;
      const bd = b.type === 'dir' ? 0 : 1;
      if (ad !== bd) return ad - bd;
      return (a.name || '').localeCompare(b.name || '');
    });

    sorted.forEach(entry => {
      const tr = document.createElement('tr');
      tr.dataset.path = entry.path || '';
      tr.dataset.type = entry.type || '';
      tr.dataset.name = entry.name || '';

      const icon = entry.type === 'dir'
        ? '<i class="bi bi-folder2 me-1"></i>'
        : '<i class="bi bi-file-earmark-text me-1"></i>';

      tr.innerHTML =
        '<td class="text-nowrap">' +
          icon +
          (entry.name || '') +
        '</td>' +
        '<td class="text-capitalize">' + (entry.type || '') + '</td>' +
        '<td>' + (entry.type === 'dir' ? '' : fmtBytes(entry.size)) + '</td>' +
        '<td class="text-end text-nowrap">' +
          (entry.type === 'dir'
            ? '<button type="button" class="btn btn-sm btn-outline-secondary me-1 js-fm-open-dir" title="Open folder"><i class="bi bi-door-open"></i></button>'
            : '<button type="button" class="btn btn-sm btn-outline-success me-1 js-fm-rename" title="Rename"><i class="bi bi-journal-check"></i></button>' +
              '<button type="button" class="btn btn-sm btn-outline-danger js-fm-delete" title="Delete"><i class="bi bi-trash"></i></button>'
          ) +
        '</td>';

      tableBody.appendChild(tr);
    });
  }

  function refreshList() {
    if (!state.projectId) return;
    apiList(state.projectId, state.currentPath)
      .then(data => {
        renderList(data.entries || []);
      })
      .catch(err => {
        console.error(err);
        if (window.showFeedback) window.showFeedback('Failed to load files.', 'danger');
      });
  }

  function setPath(path) {
    state.currentPath = path || '';
    if (currentPathEl) currentPathEl.textContent = fmtPath(state.currentPath);
    // Changing directory closes any file currently being edited without saving
    clearEditor();
    refreshList();
  }

  function goUp() {
    if (!state.currentPath) return;
    const parts = state.currentPath.split('/').filter(Boolean);
    parts.pop();
    setPath(parts.join('/'));
  }

  function clearEditor() {
    state.currentEditPath = null;
    if (editorFilenameEl) editorFilenameEl.textContent = 'Select a file to view or edit';
    if (editorTextarea) editorTextarea.value = '';
    if (saveBtn) saveBtn.disabled = true;
  }

  function openFile(path) {
    if (!state.projectId || !path) return;
    apiGet(state.projectId, path)
      .then(data => {
        state.currentEditPath = path;
        if (editorFilenameEl) editorFilenameEl.textContent = path;
        if (editorTextarea) editorTextarea.value = data.content || '';
        if (saveBtn) saveBtn.disabled = false;
      })
      .catch(err => {
        console.error(err);
        if (window.showFeedback) window.showFeedback('Failed to load file.', 'danger');
      });
  }

  function bindEvents() {
    ensureElements();
    if (!modalEl) return;

    if (upBtn) {
      upBtn.addEventListener('click', () => {
        goUp();
      });
    }

    if (uploadBtn && uploadInput) {
      uploadBtn.addEventListener('click', () => {
        if (!state.projectId) return;
        const file = uploadInput.files && uploadInput.files[0];
        if (!file) {
          if (window.showFeedback) window.showFeedback('Choose a file to upload.', 'warning');
          return;
        }
        apiUpload(state.projectId, state.currentPath, file)
          .then(() => {
            uploadInput.value = '';
            refreshList();
            if (window.showFeedback) window.showFeedback('File uploaded.', 'success');
          })
          .catch(err => {
            console.error(err);
            if (window.showFeedback) window.showFeedback('Upload failed.', 'danger');
          });
      });
    }

    if (saveBtn && editorTextarea) {
      saveBtn.addEventListener('click', () => {
        if (!state.projectId || !state.currentEditPath) return;
        const content = editorTextarea.value || '';
        apiSave(state.projectId, state.currentEditPath, content)
          .then(() => {
            refreshList();
            if (window.showFeedback) window.showFeedback('File saved.', 'success');
          })
          .catch(err => {
            console.error(err);
            if (window.showFeedback) window.showFeedback('Save failed.', 'danger');
          });
      });
    }

    if (tableBody) {
      tableBody.addEventListener('click', e => {
        const row = e.target.closest('tr');
        if (!row) return;
        const path = row.dataset.path || '';
        const type = row.dataset.type || '';
        const name = row.dataset.name || '';

        const openDirBtn = e.target.closest('.js-fm-open-dir');
        if (openDirBtn) {
          if (type === 'dir') setPath(path);
          return;
        }

        const editFileBtn = e.target.closest('.js-fm-edit-file');
        if (editFileBtn) {
          if (type === 'file') openFile(path);
          return;
        }

        const renameBtn = e.target.closest('.js-fm-rename');
        if (renameBtn) {
          if (!path) return;
          const currentName = name || path.split('/').pop();
          const input = window.prompt('Rename to:', currentName);
          if (input === null) return;
          const trimmed = (input || '').trim();
          if (!trimmed) {
            if (window.showFeedback) window.showFeedback('Name cannot be empty.', 'warning');
            return;
          }
          apiRename(state.projectId, path, trimmed)
            .then(data => {
              // If a file currently open was renamed, close it
              if (state.currentEditPath === path || (state.currentEditPath && state.currentEditPath.indexOf(path + '/') === 0)) {
                clearEditor();
              }
              refreshList();
              if (window.showFeedback) window.showFeedback('Renamed.', 'success');
            })
            .catch(err => {
              console.error(err);
              if (window.showFeedback) window.showFeedback('Rename failed.', 'danger');
            });
          return;
        }

        const deleteBtn = e.target.closest('.js-fm-delete');
        if (deleteBtn) {
          if (!path) return;
          const msg = type === 'dir'
            ? 'Delete folder and all its contents?'
            : 'Delete file?';
          if (!window.confirm(msg)) return;
          apiDelete(state.projectId, path)
            .then(() => {
              if (state.currentEditPath === path) clearEditor();
              refreshList();
              if (window.showFeedback) window.showFeedback('Deleted.', 'success');
            })
            .catch(err => {
              console.error(err);
              if (window.showFeedback) window.showFeedback('Delete failed.', 'danger');
            });
          return;
        }

        // Clicking on name: navigate into dir or open file
        const nameCell = row.cells[0];
        if (nameCell && (e.target === nameCell || nameCell.contains(e.target))) {
          if (type === 'dir') setPath(path);
          else if (type === 'file') openFile(path);
        }
      });
    }

    modalEl.addEventListener('hidden.bs.modal', () => {
      state.projectId = null;
      state.projectName = '';
      state.currentPath = '';
      state.currentEditPath = null;
      if (projectNameEl) projectNameEl.textContent = '';
      if (currentPathEl) currentPathEl.textContent = '/';
      if (uploadInput) uploadInput.value = '';
      if (tableBody) tableBody.innerHTML = '';
      clearEditor();
    });
  }

  function openFileManager(projectId, projectName) {
    ensureElements();
    if (!modalEl || !modal) return;

    state.projectId = projectId;
    state.projectName = projectName || projectId;
    state.currentPath = '';
    state.currentEditPath = null;

    if (projectNameEl) projectNameEl.textContent = state.projectName;
    if (currentPathEl) currentPathEl.textContent = '/';
    clearEditor();
    refreshList();
    modal.show();
  }

  document.addEventListener('DOMContentLoaded', () => {
    ensureElements();
    bindEvents();
  });

  window.openFileManager = openFileManager;
})();
