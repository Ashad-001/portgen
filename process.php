<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?message=" . urlencode("Please log in to generate your portfolio."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = (int)$_SESSION['user_id'];

    // Basic Info
    $fullname = trim($_POST['fullname'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $years_experience = trim($_POST['years_experience'] ?? '');
    $availability = trim($_POST['availability'] ?? '');
    // Handle optional profile photo upload
    $photo_url = '';
    if (isset($_FILES['photo_file']) && is_uploaded_file($_FILES['photo_file']['tmp_name'])) {
        $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp'];
        if (in_array($_FILES['photo_file']['type'], $allowedTypes)) {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            $ext = pathinfo($_FILES['photo_file']['name'], PATHINFO_EXTENSION);
            $safeName = 'profile_' . uniqid() . '.' . $ext;
            $destPath = 'uploads/' . $safeName;
            if (move_uploaded_file($_FILES['photo_file']['tmp_name'], $destPath)) {
                $photo_url = $destPath;
            }
        }
    }
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $skills_data = trim($_POST['skills_data'] ?? '[]');
    
    // New Contact & Links
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $github = trim($_POST['github'] ?? '');
    $website = trim($_POST['website'] ?? '');
    
    // New Education & Achievements
    $achievements = trim($_POST['achievements'] ?? '');
    $education_data = trim($_POST['education_data'] ?? '[]');
    $edu_school = trim($_POST['edu_school'] ?? '');
    $edu_degree = trim($_POST['edu_degree'] ?? '');
    $edu_start = $_POST['edu_start'] ?? null;
    $edu_end = $_POST['edu_end'] ?? null;

    // Normalize skill-level data for safety and consistency.
    $decodedSkills = json_decode($skills_data, true);
    if (!is_array($decodedSkills)) {
        $decodedSkills = [];
    }
    $normalizedSkills = [];
    foreach ($decodedSkills as $item) {
        if (!is_array($item)) continue;
        $skillName = trim((string)($item['name'] ?? ''));
        $skillLevel = (int)($item['level'] ?? 0);
        if ($skillName === '') continue;
        $skillLevel = max(10, min(100, $skillLevel));
        $normalizedSkills[] = ['name' => $skillName, 'level' => $skillLevel];
        if (count($normalizedSkills) >= 10) break;
    }
    if (!empty($normalizedSkills)) {
        $skills = implode(', ', array_map(fn($s) => $s['name'], $normalizedSkills));
        $skills_data = json_encode($normalizedSkills, JSON_UNESCAPED_UNICODE);
    } else {
        $skills_data = '[]';
    }

    // Normalize education history entries and keep one legacy summary row in sync.
    $decodedEducation = json_decode($education_data, true);
    if (!is_array($decodedEducation)) {
        $decodedEducation = [];
    }

    $normalizeDate = static function ($value) {
        $value = trim((string)$value);
        if ($value === '') return null;
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    };

    $normalizedEducation = [];
    foreach ($decodedEducation as $entry) {
        if (!is_array($entry)) continue;
        $school = trim((string)($entry['school'] ?? ''));
        $degree = trim((string)($entry['degree'] ?? ''));
        $start = $normalizeDate($entry['start'] ?? '');
        $end = $normalizeDate($entry['end'] ?? '');

        if ($school === '' && $degree === '' && !$start && !$end) continue;

        $normalizedEducation[] = [
            'school' => $school,
            'degree' => $degree,
            'start' => $start,
            'end' => $end,
        ];
        if (count($normalizedEducation) >= 10) break;
    }

    if (empty($normalizedEducation) && ($edu_school !== '' || $edu_degree !== '' || !empty($edu_start) || !empty($edu_end))) {
        $normalizedEducation[] = [
            'school' => $edu_school,
            'degree' => $edu_degree,
            'start' => $normalizeDate($edu_start),
            'end' => $normalizeDate($edu_end),
        ];
    }

    if (!empty($normalizedEducation)) {
        $primaryEducation = $normalizedEducation[0];
        $edu_school = (string)$primaryEducation['school'];
        $edu_degree = (string)$primaryEducation['degree'];
        $edu_start = $primaryEducation['start'];
        $edu_end = $primaryEducation['end'];
        $education_data = json_encode($normalizedEducation, JSON_UNESCAPED_UNICODE);
    } else {
        $education_data = '[]';
        $edu_school = '';
        $edu_degree = '';
        $edu_start = null;
        $edu_end = null;
    }

    $sql = "INSERT INTO portfolios (user_id, fullname, email, phone, location, linkedin, github, website, title, tagline, years_experience, availability, bio, skills, skills_data, achievements, education_data, edu_school, edu_degree, edu_start, edu_end, photo_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error: " . $conn->error);
    }

    $stmt->bind_param(
        "isssssssssssssssssssss",
        $user_id,
        $fullname,
        $email,
        $phone,
        $location,
        $linkedin,
        $github,
        $website,
        $title,
        $tagline,
        $years_experience,
        $availability,
        $bio,
        $skills,
        $skills_data,
        $achievements,
        $education_data,
        $edu_school,
        $edu_degree,
        $edu_start,
        $edu_end,
        $photo_url
    );

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Generating Portfolio...</title>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600&display=swap" rel="stylesheet">
            <style>
                :root {
                    --bg: #f8fafc;
                    --text: #0f172a;
                    --text-label: #334155;
                    --spinner-top: #f59e0b;
                    --spinner-bg: #e2e8f0;
                }

                body[data-theme="warm"] {
                    --bg: #fff7ed;
                    --text: #2c1a0c;
                    --text-label: #6b4b3a;
                    --spinner-top: #f59e0b;
                    --spinner-bg: #f1d7c0;
                }

                body[data-theme="light"] {
                    --bg: #f8fafc;
                    --text: #0f172a;
                    --text-label: #334155;
                    --spinner-top: #2563eb;
                    --spinner-bg: #e2e8f0;
                }

                body {
                    background: var(--bg);
                    color: var(--text);
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    font-family: 'Plus Jakarta Sans', sans-serif;
                }
                
                /* The Loading Spinner */
                .loader {
                    width: 60px;
                    height: 60px;
                    border: 5px solid var(--spinner-bg);
                    border-top: 5px solid var(--spinner-top);
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-bottom: 20px;
                }

                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                .status-text {
                    font-size: 1.2rem;
                    letter-spacing: 0.5px;
                    color: var(--text-label);
                }

                /* Dots animation */
                .dots::after {
                    content: '';
                    animation: dots 1.5s steps(4, end) infinite;
                }

                @keyframes dots {
                    0%, 20% { content: ''; }
                    40% { content: '.'; }
                    60% { content: '..'; }
                    80%, 100% { content: '...'; }
                }
            </style>
        </head>
        <body>
            <script>
                (function() {
                    const saved = localStorage.getItem('portgen-theme');
                    if (saved === 'warm' || saved === 'light') {
                        document.body.setAttribute('data-theme', saved);
                    }
                })();
            </script>
            <div class="loader"></div>
            <div class="status-text">Generating your professional portfolio<span class="dots"></span></div>

            <script>
                // This replaces the history entry so "Back" skips this page
                setTimeout(function() {
                    window.location.replace("view.php?id=<?php echo $last_id; ?>");
                }, 3000);
            </script>
        </body>
        </html>
        <?php
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    header("Location: builder.php?message=" . urlencode("Invalid request. Please submit the form again."));
    exit;
}
?>
