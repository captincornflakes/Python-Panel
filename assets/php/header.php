<?php

require_once __DIR__ . '/config.php';

function render_head(string $title): void
{
    ?>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
      <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <?php
}

function render_nav(): void
{
    $logged_in = is_user_logged_in();
    $username  = $logged_in ? ($_SESSION['user'] ?? '') : '';
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary">
      <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $logged_in ? 'dashboard' : 'index'; ?>">Python Bot Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <?php if ($logged_in): ?>
              <li class="nav-item me-3 align-self-center text-secondary">
                Logged in as <button type="button" class="btn btn-link btn-sm p-0 align-baseline text-decoration-none text-secondary" data-bs-toggle="modal" data-bs-target="#userModal">
                  <span class="fw-semibold"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span>
                </button>
              </li>
              <li class="nav-item">
                <a class="btn btn-outline-light" href="assets/php/auth.php?action=logout" title="Logout" aria-label="Logout">
                  <i class="bi bi-door-open"></i>
                </a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#authModal">Login / Register</button>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
    <?php
}
