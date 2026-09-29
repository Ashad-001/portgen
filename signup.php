<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: builder.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullname === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $existingUser = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existingUser) {
            $error = 'An account with this email already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare('INSERT INTO users (fullname, email, password_hash) VALUES (?, ?, ?)');
            $insertStmt->bind_param('sss', $fullname, $email, $passwordHash);

            if ($insertStmt->execute()) {
                $_SESSION['user_id'] = $insertStmt->insert_id;
                $_SESSION['user_fullname'] = $fullname;
                $_SESSION['user_email'] = $email;
                $insertStmt->close();

                header('Location: builder.php');
                exit;
            }

            $error = 'Could not create account. Please try again.';
            $insertStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | PortGen</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-wrap {
            min-height: calc(100vh - 120px);
            display: grid;
            place-items: center;
            padding: 40px 16px;
            background: linear-gradient(120deg, rgba(37, 99, 235, 0.08), rgba(14, 165, 233, 0.06));
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
            <h1>Create account</h1>
            <p class="auth-sub">Sign up to start building your portfolio.</p>

            <?php if ($error !== ''): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="signup.php">
                <div>
                    <label for="fullname">Full Name</label>
                    <input id="fullname" type="text" name="fullname" required value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
                </div>
                <div>
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <div>
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" type="password" name="confirm_password" required>
                </div>
                <button class="auth-btn" type="submit">Sign Up</button>
            </form>

            <p class="auth-foot">Already have an account? <a href="login.php">Login</a></p>
            <p class="auth-foot"><a href="index.html">Back to Home</a></p>
        </section>
    </main>
</body>
</html>
