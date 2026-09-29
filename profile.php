<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?message=' . urlencode('Please log in to access your profile.'));
    exit;
}

$userId = (int)$_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullname === '' || $email === '') {
        $error = 'Full name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $emailCheck = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $emailCheck->bind_param('si', $email, $userId);
        $emailCheck->execute();
        $existing = $emailCheck->get_result()->fetch_assoc();
        $emailCheck->close();

        if ($existing) {
            $error = 'That email is already used by another account.';
        }
    }

    if ($error === '' && $newPassword !== '') {
        if (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirm password do not match.';
        } else {
            $passwordStmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $passwordStmt->bind_param('i', $userId);
            $passwordStmt->execute();
            $passwordRow = $passwordStmt->get_result()->fetch_assoc();
            $passwordStmt->close();

            if (!$passwordRow || !password_verify($currentPassword, $passwordRow['password_hash'])) {
                $error = 'Current password is incorrect.';
            }
        }
    }

    if ($error === '') {
        if ($newPassword !== '') {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare('UPDATE users SET fullname = ?, email = ?, password_hash = ? WHERE id = ?');
            $updateStmt->bind_param('sssi', $fullname, $email, $newHash, $userId);
        } else {
            $updateStmt = $conn->prepare('UPDATE users SET fullname = ?, email = ? WHERE id = ?');
            $updateStmt->bind_param('ssi', $fullname, $email, $userId);
        }

        if ($updateStmt->execute()) {
            $_SESSION['user_fullname'] = $fullname;
            $_SESSION['user_email'] = $email;
            $success = 'Profile updated successfully.';
        } else {
            $error = 'Could not update your profile right now.';
        }

        $updateStmt->close();
    }
}

$userStmt = $conn->prepare('SELECT fullname, email, created_at FROM users WHERE id = ? LIMIT 1');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php?message=' . urlencode('Your session is no longer valid. Please log in again.'));
    exit;
}

$firstName = explode(' ', trim($user['fullname']))[0] ?: 'Profile';
$initial = strtoupper(substr($firstName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | PortGen</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-page {
            padding: 44px 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(14, 165, 233, 0.06));
            min-height: calc(100vh - 90px);
        }

        .profile-shell {
            width: min(900px, 92%);
            margin: 0 auto;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 18px;
        }

        .profile-card,
        .profile-form-card {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.1);
        }

        .profile-card {
            padding: 22px;
            align-self: start;
        }

        .profile-avatar-big {
            width: 78px;
            height: 78px;
            border-radius: 999px;
            background: linear-gradient(120deg, #2563eb, #1d4ed8);
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .profile-name {
            margin: 0;
            font-size: 21px;
            color: #0f172a;
        }

        .profile-email {
            color: #475569;
            margin: 6px 0 14px;
            word-break: break-word;
        }

        .profile-meta {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .profile-logout {
            display: inline-flex;
            padding: 10px 12px;
            border-radius: 10px;
            background: #fee2e2;
            color: #b91c1c;
            font-weight: 700;
        }

        .profile-form-card {
            padding: 24px;
        }

        .profile-form-card h1 {
            margin: 0 0 18px;
            font-size: 28px;
            color: #0f172a;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .profile-grid .full {
            grid-column: 1 / -1;
        }

        .profile-form-card label {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            display: block;
            margin-bottom: 6px;
        }

        .profile-form-card input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 14px;
        }

        .profile-form-card input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        .save-btn {
            margin-top: 20px;
            border: 0;
            border-radius: 10px;
            padding: 12px 18px;
            background: #2563eb;
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
        }

        .save-btn:hover {
            background: #1d4ed8;
        }

        .feedback {
            margin: 0 0 12px;
            border-radius: 10px;
            padding: 10px;
            font-size: 14px;
        }

        .feedback.error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .feedback.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .profile-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .profile-avatar {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: linear-gradient(120deg, #2563eb, #1d4ed8);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        @media (max-width: 860px) {
            .profile-shell {
                grid-template-columns: 1fr;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <div class="logo" aria-label="PortGen logo">PortGen</div>
            <div class="header-actions">
                <nav aria-label="Main navigation">
                    <ul class="nav-list">
                        <li><a class="nav-link" href="index.html">Home</a></li>
                        <li><a class="nav-link" href="builder.php">Create Portfolio</a></li>
                        <li><a class="nav-link" href="my-portfolios.php">My Portfolios</a></li>
                        <li>
                            <a class="nav-link profile-pill is-active" aria-current="page" href="profile.php" aria-label="Profile">
                                <span class="profile-avatar"><?php echo htmlspecialchars($initial); ?></span>
                                <span><?php echo htmlspecialchars($firstName); ?></span>
                            </a>
                        </li>
                        <li><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </nav>
                <button class="theme-icon-button" type="button" data-theme-toggle aria-label="Toggle theme"></button>
            </div>
        </div>
    </header>

    <main class="profile-page">
        <div class="profile-shell">
            <aside class="profile-card">
                <div class="profile-avatar-big"><?php echo htmlspecialchars($initial); ?></div>
                <h2 class="profile-name"><?php echo htmlspecialchars($user['fullname']); ?></h2>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="profile-meta">Joined: <?php echo htmlspecialchars(date('M d, Y', strtotime($user['created_at']))); ?></p>
                <a class="profile-logout" href="logout.php">Log out</a>
            </aside>

            <section class="profile-form-card">
                <h1>My Profile</h1>

                <?php if ($error !== ''): ?>
                    <p class="feedback error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <p class="feedback success"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>

                <form method="POST" action="profile.php">
                    <div class="profile-grid">
                        <div>
                            <label for="fullname">Full Name</label>
                            <input id="fullname" name="fullname" type="text" required value="<?php echo htmlspecialchars($user['fullname']); ?>">
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="full">
                            <label for="current_password">Current Password (required only if changing password)</label>
                            <input id="current_password" name="current_password" type="password">
                        </div>
                        <div>
                            <label for="new_password">New Password (optional)</label>
                            <input id="new_password" name="new_password" type="password">
                        </div>
                        <div>
                            <label for="confirm_password">Confirm New Password</label>
                            <input id="confirm_password" name="confirm_password" type="password">
                        </div>
                    </div>
                    <button class="save-btn" type="submit">Save Changes</button>
                </form>
            </section>
        </div>
    </main>

    <script>
        (() => {
            const toggleBtn = document.querySelector('[data-theme-toggle]');
            const body = document.body;
            const sun = '<svg class="theme-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2.4m0 14.2v2.4M21.5 12h-2.4M4.9 12H2.5m14.2 7.1-1.7-1.7M8 6.1 6.3 4.4m11.9 0L16.5 6.1M8 17.9 6.3 19.6"/></svg>';
            const moon = '<svg class="theme-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19.5 14.5a7.8 7.8 0 1 1-9-9.8 7 7 0 1 0 9 9.8Z"/></svg>';
            let saved = localStorage.getItem('portgen-theme');
            if (saved === 'dark') saved = 'warm';
            if (saved === 'warm' || saved === 'light') body.dataset.theme = saved;

            const setIcon = () => {
                if (!toggleBtn) return;
                const isWarm = body.dataset.theme === 'warm';
                toggleBtn.innerHTML = isWarm ? sun : moon;
                toggleBtn.setAttribute('aria-label', isWarm ? 'Switch to light theme' : 'Switch to warm theme');
            };

            setIcon();
            toggleBtn?.addEventListener('click', () => {
                const next = body.dataset.theme === 'warm' ? 'light' : 'warm';
                body.dataset.theme = next;
                localStorage.setItem('portgen-theme', next);
                setIcon();
            });
        })();
    </script>
</body>
</html>
