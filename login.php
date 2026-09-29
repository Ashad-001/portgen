<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: builder.php');
    exit;
}

$error = '';
$message = trim($_GET['message'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare('SELECT id, fullname, email, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_fullname'] = $user['fullname'];
            $_SESSION['user_email'] = $user['email'];

            header('Location: builder.php');
            exit;
        }

        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PortGen</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-wrap {
            min-height: calc(100vh - 120px);
            display: grid;
            place-items: center;
            padding: 40px 16px;
            background: linear-gradient(120deg, rgba(14, 165, 233, 0.08), rgba(37, 99, 235, 0.06));
        }

        .auth-card {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
            border: 1px solid #dbeafe;
        }

        .auth-card h1 {
            margin: 0 0 10px;
            font-size: 28px;
        }

        .auth-sub {
            margin: 0 0 22px;
            color: #475569;
        }

        .auth-form {
            display: grid;
            gap: 14px;
        }

        .auth-form label {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }

        .auth-form input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
        }

        .auth-form input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .auth-btn {
            margin-top: 8px;
            background: #2563eb;
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .auth-btn:hover {
            background: #1d4ed8;
        }

        .auth-error {
            margin-bottom: 14px;
            color: #b91c1c;
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 10px;
            font-size: 14px;
        }

        .auth-message {
            margin-bottom: 14px;
            color: #1e3a8a;
            background: #dbeafe;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 10px;
            font-size: 14px;
        }

        .auth-foot {
            margin-top: 16px;
            font-size: 14px;
            color: #475569;
        }

        .auth-foot a {
            color: #1d4ed8;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main class="auth-wrap">
        <section class="auth-card">
            <h1>Welcome back</h1>
            <p class="auth-sub">Login to continue building your portfolio.</p>

            <?php if ($message !== ''): ?>
                <div class="auth-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="login.php">
                <div>
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <button class="auth-btn" type="submit">Login</button>
            </form>

            <p class="auth-foot">No account yet? <a href="signup.php">Create one</a></p>
            <p class="auth-foot"><a href="index.html">Back to Home</a></p>
        </section>
    </main>
</body>
</html>
