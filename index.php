<?php
require __DIR__ . '/assets/php/config.php';
require __DIR__ . '/assets/php/header.php';

if (is_user_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);
?>
<!doctype html>
<html lang="en">
  <?php render_head('Bot Deployment Panel - Login'); ?>
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

      .glass-card .card-title {
        color: #e5e7eb;
      }

      .glass-card .card-text {
        color: #9ca3af;
      }
    </style>

    <main class="container py-5">
      <div class="row justify-content-center mb-5">
        <div class="col-lg-8 text-center">
          <h1 class="display-4 mb-3">Python Bot Control Panel</h1>
          <p class="lead mb-3 text-secondary">Deploy, monitor, and manage your screen-based Python bots from a single dark-mode dashboard.</p>
          <p class="text-secondary mb-0">Create projects, upload code, start and stop bots in isolated screen sessions, and watch live logs without leaving your browser.</p>
        </div>
      </div>

      <?php if ($error): ?>
      <div class="row justify-content-center mb-4">
        <div class="col-lg-6">
          <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="card h-100 glass-card text-light">
            <div class="card-body">
              <h5 class="card-title">Per-user projects</h5>
              <p class="card-text small text-secondary">
                Each account gets its own project space. Create and edit projects with a name and start file, upload a ZIP of your bot code, and keep everything neatly separated per user.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 glass-card text-light">
            <div class="card-body">
              <h5 class="card-title">Start, stop &amp; status</h5>
              <p class="card-text small text-secondary">
                Bots run inside Linux <code class="text-light">screen</code> sessions with per-project logs. Start and stop from the dashboard, see live status, and let a cron job keep status in sync in the background.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 glass-card text-light">
            <div class="card-body">
              <h5 class="card-title">Live log viewer</h5>
              <p class="card-text small text-secondary">
                Open a full-height modal for any running project to stream its <code class="text-light">screen.log</code>. Output auto-refreshes, auto-scrolls to the bottom, and can be paused while you scroll back.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mt-1">
        <div class="col-md-4">
          <div class="card h-100 glass-card text-light">
            <div class="card-body">
              <h5 class="card-title">Environment aware</h5>
              <p class="card-text small text-secondary">
                When a project includes <code class="text-light">requirements.txt</code>, the panel creates a per-project virtualenv, installs dependencies there, and runs your bot in that isolated environment.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 glass-card text-light">
            <div class="card-body">
              <h5 class="card-title">Account controls</h5>
              <p class="card-text small text-secondary">
                Manage your login directly from the dashboard: update your password with validation or delete your account entirely, all from a simple, self-contained panel.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 glass-card text-light">
            <div class="card-body">
              <h5 class="card-title">Safe defaults</h5>
              <p class="card-text small text-secondary">
                Projects and logs are stored with per-user isolation and cleaned up when you delete a project, so old files and virtualenvs don&rsquo;t pile up on the server.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="row justify-content-center mt-5">
        <div class="col-lg-10">
          <div class="card glass-card text-light">
            <div class="card-body">
              <h4 class="card-title mb-3">How it works</h4>
              <div class="row g-4 small text-secondary">
                <div class="col-md-4">
                  <h6 class="text-light">1. Create a project</h6>
                  <ul class="mb-0 ps-3">
                    <li>Click <span class="text-light">New Project</span> on the dashboard.</li>
                    <li>Give your bot a name and the Python start file (for example <code class="text-light">main.py</code>).</li>
                    <li>Save the project; an isolated area is prepared for its code and logs.</li>
                  </ul>
                </div>
                <div class="col-md-4">
                  <h6 class="text-light">2. Upload your bot</h6>
                  <ul class="mb-0 ps-3">
                    <li>Open the project&rsquo;s <span class="text-light">Edit</span> dialog.</li>
                    <li>Upload a ZIP that contains your bot files and optional <code class="text-light">requirements.txt</code>.</li>
                    <li>The panel extracts everything into the project&rsquo;s own folder, ready to run.</li>
                  </ul>
                </div>
                <div class="col-md-4">
                  <h6 class="text-light">3. Manage your bot</h6>
                  <ul class="mb-0 ps-3">
                    <li>Use the play / stop controls to start or stop the bot in a background screen session.</li>
                    <li>Check status from the dashboard; a scheduler also keeps statuses fresh in the background.</li>
                    <li>Click <span class="text-light">View</span> in the Screen column to watch live logs, auto-refreshing in a full-height modal.</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title" id="authModalLabel">Welcome</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-login-tab" data-bs-toggle="pill" data-bs-target="#pills-login" type="button" role="tab" aria-controls="pills-login" aria-selected="true">Login</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-register-tab" data-bs-toggle="pill" data-bs-target="#pills-register" type="button" role="tab" aria-controls="pills-register" aria-selected="false">Register</button>
              </li>
            </ul>
            <div class="tab-content" id="pills-tabContent">
              <div class="tab-pane fade show active" id="pills-login" role="tabpanel" aria-labelledby="pills-login-tab">
                <form method="post" action="assets/php/auth.php">
                  <input type="hidden" name="action" value="login">
                  <div class="mb-3">
                    <label for="login-username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="login-username" name="username" required autocomplete="username">
                  </div>
                  <div class="mb-3">
                    <label for="login-password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="login-password" name="password" required autocomplete="current-password">
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
              </div>
              <div class="tab-pane fade" id="pills-register" role="tabpanel" aria-labelledby="pills-register-tab">
                <form method="post" action="assets/php/auth.php">
                  <input type="hidden" name="action" value="register">
                  <div class="mb-3">
                    <label for="register-username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="register-username" name="username" required autocomplete="username">
                  </div>
                  <div class="mb-3">
                    <label for="register-password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="register-password" name="password" required autocomplete="new-password" pattern="[A-Za-z0-9]{8,12}" minlength="8" maxlength="12" title="Password must be 8-12 characters and contain only letters and numbers.">
                  </div>
                  <div class="mb-3">
                    <label for="register-password-confirm" class="form-label">Confirm password</label>
                    <input type="password" class="form-control" id="register-password-confirm" name="password_confirm" required autocomplete="new-password" pattern="[A-Za-z0-9]{8,12}" minlength="8" maxlength="12" title="Password must match and be 8-12 characters of letters and numbers.">
                  </div>
                  <button type="submit" class="btn btn-success w-100">Create Account</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="tosModal" tabindex="-1" aria-labelledby="tosModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title" id="tosModalLabel">Terms of Service</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body small text-secondary">
            <p class="mb-2">
              This panel is provided as-is to help you manage and monitor your own Python bots.
              You are solely responsible for the code you upload, the bots you run, and any
              effects they have on the systems and services they interact with.
            </p>
            <p class="mb-2">
              By using this panel you agree that:
            </p>
            <ul class="mb-2">
              <li>You will only deploy code and bots that you are allowed to run.</li>
              <li>You will not use this panel to perform abusive, illegal, or harmful actions.</li>
              <li>You understand that no uptime, reliability, or data durability is guaranteed.</li>
              <li>You are responsible for configuring, monitoring, and stopping your own bots.</li>
            </ul>
            <p class="mb-0">
              The maintainers of this panel are not liable for any loss, damage, or issues arising
              from its use. If you do not agree with these terms, please do not use this panel.
            </p>
          </div>
        </div>
      </div>
    </div>

    <footer class="bg-black border-top border-secondary mt-5 py-3">
      <div class="container small text-secondary">
        <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap text-center">
          <div>&copy; <?php echo date('Y'); ?> EchoGrid. All rights reserved.</div>
          <button type="button" class="btn btn-link p-0 text-secondary text-decoration-none" data-bs-toggle="modal" data-bs-target="#tosModal">
            Terms of Service
          </button>
          <a href="mailto:dev@echogrid.win" class="text-secondary text-decoration-none">
            Contact: dev@echogrid.win
          </a>
        </div>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>
