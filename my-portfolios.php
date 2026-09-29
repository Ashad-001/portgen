<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?message=' . urlencode('Please log in to view your portfolios.'));
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userFullname = $_SESSION['user_fullname'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

$portfolios = [];
$query = $conn->prepare('SELECT id, fullname, title, email, photo_url, created_at FROM portfolios WHERE user_id IS NULL OR user_id = ? ORDER BY id DESC LIMIT 50');
if ($query) {
    $query->bind_param('i', $userId);
    $query->execute();
    $result = $query->get_result();
    while ($row = $result->fetch_assoc()) {
        $portfolios[] = $row;
    }
    $query->close();
}

$firstName = explode(' ', trim($userFullname))[0] ?: 'Profile';
$initial = strtoupper(substr($firstName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolios | PortGen</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-page {
            padding: 44px 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(14, 165, 233, 0.06));
            min-height: calc(100vh - 90px);
        }

        .dashboard-container {
            width: min(1200px, 92%);
            margin: 0 auto;
        }

        .dashboard-header {
            margin-bottom: 32px;
        }

        .dashboard-header h1 {
            margin: 0 0 10px;
            font-size: 32px;
            color: #0f172a;
        }

        .dashboard-header p {
            margin: 0;
            color: #475569;
        }

        .create-btn {
            display: inline-block;
            margin-top: 12px;
            padding: 12px 20px;
            background: #2563eb;
            color: #ffffff;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
        }

        .create-btn:hover {
            background: #1d4ed8;
        }

        .portfolios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .portfolio-card {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .portfolio-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.15);
        }

        .portfolio-preview {
            height: 200px;
            background: linear-gradient(135deg, #f8fafc, #eef4ff);
            display: grid;
            place-items: center;
            position: relative;
            overflow: hidden;
        }

        .portfolio-preview-content {
            text-align: center;
            padding: 20px;
            width: 100%;
        }

        .portfolio-avatar {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            background: linear-gradient(120deg, #2563eb, #1d4ed8);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .portfolio-name {
            font-weight: 700;
            font-size: 16px;
            color: #0f172a;
            margin: 0;
        }

        .portfolio-title {
            font-size: 13px;
            color: #475569;
            margin: 4px 0 0;
        }

        .portfolio-body {
            padding: 16px;
        }

        .portfolio-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 13px;
            color: #475569;
        }

        .portfolio-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .portfolio-actions {
            display: flex;
            gap: 8px;
        }

        .portfolio-actions a,
        .portfolio-actions button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }

        .view-btn {
            background: #2563eb;
            color: #ffffff;
        }

        .view-btn:hover {
            background: #1d4ed8;
        }

        .edit-btn {
            background: #e0f2fe;
            color: #0369a1;
        }

        .edit-btn:hover {
            background: #bae6fd;
        }

        .delete-btn {
            background: #fee2e2;
            color: #b91c1c;
            border: none;
        }

        .delete-btn:hover {
            background: #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
        }

        .empty-state h2 {
            margin: 0 0 10px;
            color: #0f172a;
            font-size: 24px;
        }

        .empty-state p {
            margin: 0 0 20px;
            color: #475569;
        }

        .profile-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .profile-avatar-nav {
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
            .portfolios-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-header h1 {
                font-size: 24px;
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
                        <li><a class="nav-link is-active" href="my-portfolios.php">My Portfolios</a></li>
                        <li>
                            <a class="nav-link profile-pill" href="profile.php" aria-label="Profile">
                                <span class="profile-avatar-nav"><?php echo htmlspecialchars($initial); ?></span>
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

    <main class="dashboard-page">
        <div class="dashboard-container">
            <section class="dashboard-header">
                <h1>My Portfolios</h1>
                <p>View, edit, and manage all your professional portfolios.</p>
                <a href="builder.php" class="create-btn">+ Create New Portfolio</a>
            </section>

            <?php if (count($portfolios) === 0): ?>
                <div class="empty-state">
                    <h2>No portfolios yet</h2>
                    <p>Start building your first professional portfolio today.</p>
                    <a href="builder.php" class="create-btn">Create Portfolio</a>
                </div>
            <?php else: ?>
                <div class="portfolios-grid">
                    <?php foreach ($portfolios as $portfolio): ?>
                        <?php
                            $portfolioName = $portfolio['fullname'] ?? 'Unnamed';
                            $portfolioTitle = $portfolio['title'] ?? 'Professional';
                            $portfolioEmail = $portfolio['email'] ?? '';
                            $avatarInitials = strtoupper(substr($portfolioName, 0, 1) . substr(explode(' ', $portfolioName)[1] ?? '', 0, 1));
                            $createdDate = date('M d, Y', strtotime($portfolio['created_at']));
                        ?>
                        <article class="portfolio-card">
                            <div class="portfolio-preview">
                                <div class="portfolio-preview-content">
                                    <div class="portfolio-avatar"><?php echo htmlspecialchars($avatarInitials); ?></div>
                                    <h3 class="portfolio-name"><?php echo htmlspecialchars($portfolioName); ?></h3>
                                    <p class="portfolio-title"><?php echo htmlspecialchars($portfolioTitle); ?></p>
                                </div>
                            </div>
                            <div class="portfolio-body">
                                <div class="portfolio-meta">
                                    <div class="portfolio-meta-item">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($portfolioEmail); ?>
                                    </div>
                                    <div class="portfolio-meta-item">
                                        <strong>Created:</strong> <?php echo htmlspecialchars($createdDate); ?>
                                    </div>
                                </div>
                                <div class="portfolio-actions">
                                    <a href="view.php?id=<?php echo (int)$portfolio['id']; ?>" class="view-btn">View</a>
                                    <a href="edit-portfolio.php?id=<?php echo (int)$portfolio['id']; ?>" class="edit-btn">Edit</a>
                                    <button class="delete-btn" onclick="deletePortfolio(<?php echo (int)$portfolio['id']; ?>)">Delete</button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

    <script>
        async function deletePortfolio(portfolioId) {
            if (!confirm('Are you sure you want to delete this portfolio? This cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('delete-portfolio.php?id=' + portfolioId, {
                    method: 'DELETE',
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    location.reload();
                } else {
                    alert('Failed to delete portfolio. Please try again.');
                }
            } catch (error) {
                console.error('Error deleting portfolio:', error);
                alert('An error occurred while deleting the portfolio.');
            }
        }
    </script>
</body>
</html>
