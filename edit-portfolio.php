<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?message=' . urlencode('Please log in to edit your portfolio.'));
    exit;
}

$portfolioId = (int)($_GET['id'] ?? 0);

if ($portfolioId === 0) {
    header('Location: my-portfolios.php');
    exit;
}

$portfolio = null;
$query = $conn->prepare('SELECT * FROM portfolios WHERE id = ? LIMIT 1');
$query->bind_param('i', $portfolioId);
$query->execute();
$result = $query->get_result();
$portfolio = $result->fetch_assoc();
$query->close();

if (!$portfolio) {
    header('Location: my-portfolios.php?error=' . urlencode('Portfolio not found.'));
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = $_POST['phone'] ?? '';
    $location = $_POST['location'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $github = $_POST['github'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $achievements = $_POST['achievements'] ?? '';
    $edu_school = $_POST['edu_school'] ?? '';
    $edu_degree = $_POST['edu_degree'] ?? '';
    $edu_start = $_POST['edu_start'] ?? null;
    $edu_end = $_POST['edu_end'] ?? null;

    if ($fullname === '' || $title === '' || $email === '') {
        $error = 'Name, title, and email are required.';
    } else {
        $updateStmt = $conn->prepare('UPDATE portfolios SET fullname = ?, title = ?, email = ?, phone = ?, location = ?, linkedin = ?, github = ?, bio = ?, skills = ?, achievements = ?, edu_school = ?, edu_degree = ?, edu_start = ?, edu_end = ? WHERE id = ?');
        
        if ($updateStmt) {
            $updateStmt->bind_param(
                'ssssssssssssssi',
                $fullname,
                $title,
                $email,
                $phone,
                $location,
                $linkedin,
                $github,
                $bio,
                $skills,
                $achievements,
                $edu_school,
                $edu_degree,
                $edu_start,
                $edu_end,
                $portfolioId
            );

            if ($updateStmt->execute()) {
                $updateStmt->close();
                header('Location: view.php?id=' . (int)$portfolioId . '&updated=1');
                exit;
            } else {
                $error = 'Failed to update portfolio.';
            }
            $updateStmt->close();
        }
    }
}

$userFullname = $_SESSION['user_fullname'] ?? 'User';
$firstName = explode(' ', trim($userFullname))[0] ?: 'Profile';
$initial = strtoupper(substr($firstName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Portfolio | PortGen</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-page {
            padding: 44px 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(14, 165, 233, 0.06));
            min-height: calc(100vh - 90px);
        }

        .edit-container {
            width: min(800px, 92%);
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
        }

        .edit-header {
            margin-bottom: 28px;
        }

        .edit-header h1 {
            margin: 0 0 10px;
            font-size: 28px;
            color: #0f172a;
        }

        .edit-header p {
            margin: 0;
            color: #475569;
        }

        .form-section {
            margin-bottom: 24px;
        }

        .form-section h2 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-grid.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            font-size: 14px;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group textarea {
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .save-btn {
            flex: 1;
            padding: 12px 20px;
            background: #2563eb;
            color: #ffffff;
            border: 0;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .save-btn:hover {
            background: #1d4ed8;
        }

        .cancel-btn {
            padding: 12px 20px;
            background: #f1f5f9;
            color: #475569;
            border: 0;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .cancel-btn:hover {
            background: #e2e8f0;
        }

        .feedback {
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 10px;
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

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .edit-container {
                padding: 20px;
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
                            <a class="nav-link profile-pill" href="profile.php" aria-label="Profile">
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

    <main class="edit-page">
        <div class="edit-container">
            <section class="edit-header">
                <h1>Edit Portfolio</h1>
                <p>Update the details of your professional portfolio.</p>
            </section>

            <?php if ($error !== ''): ?>
                <p class="feedback error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <p class="feedback success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>

            <form method="POST" action="edit-portfolio.php?id=<?php echo (int)$portfolioId; ?>">
                <section class="form-section">
                    <h2>Personal Details</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fullname">Full Name *</label>
                            <input id="fullname" type="text" name="fullname" required value="<?php echo htmlspecialchars($portfolio['fullname'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="title">Professional Title *</label>
                            <input id="title" type="text" name="title" required value="<?php echo htmlspecialchars($portfolio['title'] ?? ''); ?>">
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h2>Contact Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input id="email" type="email" name="email" required value="<?php echo htmlspecialchars($portfolio['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input id="phone" type="text" name="phone" value="<?php echo htmlspecialchars($portfolio['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input id="location" type="text" name="location" value="<?php echo htmlspecialchars($portfolio['location'] ?? ''); ?>">
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h2>Social Links</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="linkedin">LinkedIn URL</label>
                            <input id="linkedin" type="url" name="linkedin" value="<?php echo htmlspecialchars($portfolio['linkedin'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="github">GitHub URL</label>
                            <input id="github" type="url" name="github" value="<?php echo htmlspecialchars($portfolio['github'] ?? ''); ?>">
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h2>Education</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edu_school">School/University</label>
                            <input id="edu_school" type="text" name="edu_school" value="<?php echo htmlspecialchars($portfolio['edu_school'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="edu_degree">Degree</label>
                            <input id="edu_degree" type="text" name="edu_degree" value="<?php echo htmlspecialchars($portfolio['edu_degree'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="edu_start">Start Date</label>
                            <input id="edu_start" type="date" name="edu_start" value="<?php echo htmlspecialchars($portfolio['edu_start'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="edu_end">End Date</label>
                            <input id="edu_end" type="date" name="edu_end" value="<?php echo htmlspecialchars($portfolio['edu_end'] ?? ''); ?>">
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h2>Professional Content</h2>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio"><?php echo htmlspecialchars($portfolio['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="skills">Skills (comma separated)</label>
                            <textarea id="skills" name="skills"><?php echo htmlspecialchars($portfolio['skills'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="achievements">Achievements</label>
                            <textarea id="achievements" name="achievements"><?php echo htmlspecialchars($portfolio['achievements'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </section>

                <div class="action-buttons">
                    <button class="save-btn" type="submit">Save Changes</button>
                    <a href="my-portfolios.php" class="cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-content">
            <p>© 2026 PortGen · Automated Portfolio Builder</p>
        </div>
    </footer>

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
