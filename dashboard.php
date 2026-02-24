<?php
require __DIR__ . '/assets/php/config.php';
require __DIR__ . '/assets/php/header.php';

require_login();
$username = $_SESSION['user'] ?? 'unknown';
?>
<!doctype html>
<html lang="en">
  <?php render_head('Bot Deployment Panel - Dashboard'); ?>
  <body class="bg-dark text-light">
    <?php render_nav(); ?>

    <style>
      .glass-card {
        background: rgba(15, 23, 42, 0.75);
        border: 1px solid rgba(148, 163, 184, 0.45);
        box-shadow: 0 18px 45px rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
      }
    </style>

    <main class="container py-4">
      <div class="row mb-4">
        <div class="col">
          <h1 class="h3 mb-0">Dashboard</h1>
          <p class="text-secondary mb-0">Central command for your Python bot fleet.</p>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col">
          <div class="card glass-card text-light">
            <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
              <span class="fw-semibold">Projects</span>
              <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#newProjectModal"><i class="bi bi-plus-lg me-1"></i>New Project</button>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-dark table-hover table-striped mb-0 align-middle">
                  <thead>
                    <tr>
                      <th scope="col">Name</th>
                      <th scope="col">Mode</th>
                      <th scope="col">Start File</th>
                      <th scope="col">Timestamps</th>
                      <th scope="col">Status</th>
                      <th scope="col">Screen</th>
                      <th scope="col" class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="projects-body"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <div class="modal fade" id="newProjectModal" tabindex="-1" aria-labelledby="newProjectLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title" id="newProjectLabel">New Project</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="new-project-form">
              <div class="mb-3">
                <label for="project-name" class="form-label">Name</label>
                <input type="text" class="form-control" id="project-name" name="name" required>
              </div>
              <div class="mb-3">
                <label for="project-start" class="form-label">Start file</label>
                <input type="text" class="form-control" id="project-start" name="start" placeholder="main.py" required>
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title" id="editProjectLabel">Edit Project</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="edit-project-form" enctype="multipart/form-data">
              <input type="hidden" id="edit-project-id" name="id">
              <div class="mb-3">
                <label for="edit-project-name" class="form-label">Name</label>
                <input type="text" class="form-control" id="edit-project-name" name="name" required>
              </div>
              <div class="mb-3">
                <label for="edit-project-start" class="form-label">Start file</label>
                <input type="text" class="form-control" id="edit-project-start" name="start" placeholder="main.py" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Source type</label>
                <div class="btn-group" role="group" aria-label="Project source type">
                  <input type="radio" class="btn-check" name="edit-project-mode" id="edit-project-mode-zip" value="project" autocomplete="off">
                  <label class="btn btn-sm btn-outline-secondary" for="edit-project-mode-zip">Upload ZIP</label>

                  <input type="radio" class="btn-check" name="edit-project-mode" id="edit-project-mode-git" value="git" autocomplete="off">
                  <label class="btn btn-sm btn-outline-secondary" for="edit-project-mode-git">Git repository</label>
                </div>
                <div class="form-text text-secondary">Choose whether this project uses an uploaded ZIP or a Git repository as its source.</div>
              </div>
              <div class="mb-3" id="edit-project-zip-group">
                <label for="edit-project-archive" class="form-label">Upload project zip</label>
                <input type="file" class="form-control" id="edit-project-archive" name="archive" accept=".zip">
                <div class="form-text text-secondary">Optional: upload a .zip containing the project files to extract into this project's folder.</div>
              </div>
              <div class="mb-3 d-none" id="edit-project-git-group">
                <label for="edit-project-git-url" class="form-label">Git repository URL</label>
                <input type="url" class="form-control" id="edit-project-git-url" name="git_url" placeholder="https://github.com/you/repo.git">
                <div class="form-text text-secondary">On save, the panel will clone or pull this repository into the project folder.</div>
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="screenModal" tabindex="-1" aria-labelledby="screenModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title" id="screenModalLabel">Screen Output</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex justify-content-between align-items-center mb-2 small text-secondary">
              <div id="screen-modal-meta"></div>
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="screen-auto-scroll-toggle">
                <label class="form-check-label" for="screen-auto-scroll-toggle">Pause auto-scroll</label>
              </div>
            </div>
            <pre id="screen-modal-output" class="bg-black text-success p-3 mb-0" style="height: 80vh; max-height: 80vh; overflow:auto; font-family: monospace; font-size: 0.85rem; white-space: pre-wrap; word-wrap: break-word;"></pre>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="fileManagerModal" tabindex="-1" aria-labelledby="fileManagerLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title" id="fileManagerLabel">File Manager - <span id="file-manager-project-name"></span></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex justify-content-between align-items-center mb-3 small text-secondary flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="file-manager-up-btn"><i class="bi bi-arrow-up-short me-1"></i>Up</button>
                <span>Path: <span id="file-manager-current-path">/</span></span>
              </div>
              <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="max-width: 280px;">
                  <input type="file" class="form-control form-control-sm" id="file-manager-upload-file">
                  <button class="btn btn-outline-primary" type="button" id="file-manager-upload-btn">Upload</button>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-12 col-lg-6">
                <div class="table-responsive border border-secondary rounded" style="max-height: 55vh; overflow:auto;">
                  <table class="table table-dark table-sm table-hover mb-0 align-middle">
                    <thead class="table-secondary text-dark">
                      <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Type</th>
                        <th scope="col">Size</th>
                        <th scope="col" class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody id="file-manager-body"></tbody>
                  </table>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="border border-secondary rounded h-100 d-flex flex-column">
                  <div class="px-3 py-2 border-bottom border-secondary small text-secondary" id="file-manager-editor-filename">Select a file to view or edit</div>
                  <div class="p-2 flex-grow-1">
                    <textarea id="file-manager-editor-content" class="form-control bg-black text-light border-secondary" style="height: 40vh; max-height: 40vh; font-family: monospace; font-size: 0.85rem;"></textarea>
                  </div>
                  <div class="px-3 py-2 text-end border-top border-secondary">
                    <button type="button" class="btn btn-sm btn-primary" id="file-manager-save-btn" disabled>Save</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title" id="userModalLabel">Account Settings</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" disabled>
            </div>

            <hr class="border-secondary">

            <h6 class="mb-3">Change Password</h6>
            <form id="user-password-form" class="mb-4">
              <div class="mb-3">
                <label for="user-current-password" class="form-label">Current password</label>
                <input type="password" class="form-control" id="user-current-password" name="current_password" autocomplete="current-password" required>
              </div>
              <div class="mb-3">
                <label for="user-new-password" class="form-label">New password</label>
                <input type="password" class="form-control" id="user-new-password" name="new_password" autocomplete="new-password" required>
              </div>
              <div class="mb-3">
                <label for="user-new-password-confirm" class="form-label">Confirm new password</label>
                <input type="password" class="form-control" id="user-new-password-confirm" autocomplete="new-password" required>
              </div>
              <div class="text-end">
                <button type="submit" class="btn btn-primary">Update password</button>
              </div>
            </form>

            <hr class="border-secondary">

            <h6 class="text-danger mb-2">Danger zone</h6>
            <p class="small text-secondary mb-3">Deleting your account will remove your login. Project files and data may remain on disk.</p>
            <form id="user-delete-form">
              <div class="mb-3">
                <label for="user-delete-password" class="form-label">Confirm password</label>
                <input type="password" class="form-control" id="user-delete-password" name="password" autocomplete="current-password" required>
              </div>
              <div class="text-end">
                <button type="submit" class="btn btn-outline-danger">Delete account</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div id="feedback-root" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="assets/js/feedback.js"></script>
    <script src="assets/js/filemanager.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/screen.js"></script>
    <script src="assets/js/user.js"></script>
  </body>
</html>
