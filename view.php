<?php
session_start();
include 'db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    die('Portfolio not found!');
}

$stmt = $conn->prepare('SELECT * FROM portfolios WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    die('Portfolio not found!');
}

$showUpdatedNotice = isset($_GET['updated']) && $_GET['updated'] === '1';

function esc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$isLoggedIn = isset($_SESSION['user_id']);
$profileFullname = $_SESSION['user_fullname'] ?? 'Profile';
$profileFirstName = explode(' ', trim($profileFullname))[0] ?: 'Profile';
$profileInitial = strtoupper(substr($profileFirstName, 0, 1));

$fullName = trim((string) ($row['fullname'] ?? ''));
$title = trim((string) ($row['title'] ?? ''));
$email = trim((string) ($row['email'] ?? ''));
$phone = trim((string) ($row['phone'] ?? ''));
$location = trim((string) ($row['location'] ?? ''));
$linkedin = trim((string) ($row['linkedin'] ?? ''));
$github = trim((string) ($row['github'] ?? ''));
$website = trim((string) ($row['website'] ?? ''));
$tagline = trim((string) ($row['tagline'] ?? ''));
$yearsExperience = trim((string) ($row['years_experience'] ?? ''));
$availability = trim((string) ($row['availability'] ?? ''));
$bio = trim((string) ($row['bio'] ?? ''));
$achievements = trim((string) ($row['achievements'] ?? ''));
$eduSchool = trim((string) ($row['edu_school'] ?? ''));
$eduDegree = trim((string) ($row['edu_degree'] ?? ''));
$photoUrl = trim((string) ($row['photo_url'] ?? ''));
$skillsDataRaw = trim((string) ($row['skills_data'] ?? ''));
$educationDataRaw = trim((string) ($row['education_data'] ?? ''));

$skills = array_values(array_filter(array_map('trim', explode(',', (string) ($row['skills'] ?? '')))));
$skillEntries = [];
$decodedSkillsData = json_decode($skillsDataRaw, true);
if (is_array($decodedSkillsData)) {
    foreach ($decodedSkillsData as $item) {
        if (!is_array($item)) continue;
        $name = trim((string) ($item['name'] ?? ''));
        $level = (int) ($item['level'] ?? 0);
        if ($name === '') continue;
        $skillEntries[] = ['name' => $name, 'level' => max(10, min(100, $level))];
    }
}

if (empty($skillEntries)) {
    if (empty($skills)) {
        $skills = ['Problem Solving', 'Communication', 'Design Thinking'];
    }
    $fallbackBars = [90, 82, 76, 70, 64, 58];
    foreach (array_slice($skills, 0, 6) as $idx => $name) {
        $skillEntries[] = ['name' => $name, 'level' => $fallbackBars[$idx] ?? 60];
    }
}

$skills = array_map(fn($skill) => $skill['name'], $skillEntries);

$educationEntries = [];
$decodedEducationData = json_decode($educationDataRaw, true);
if (is_array($decodedEducationData)) {
    foreach ($decodedEducationData as $entry) {
        if (!is_array($entry)) continue;
        $school = trim((string) ($entry['school'] ?? ''));
        $degree = trim((string) ($entry['degree'] ?? ''));
        $start = trim((string) ($entry['start'] ?? ''));
        $end = trim((string) ($entry['end'] ?? ''));

        if ($school === '' && $degree === '' && $start === '' && $end === '') continue;

        $educationEntries[] = [
            'school' => $school,
            'degree' => $degree,
            'start' => $start,
            'end' => $end,
        ];
        if (count($educationEntries) >= 10) break;
    }
}

if (empty($educationEntries) && ($eduSchool !== '' || $eduDegree !== '' || !empty($row['edu_start']) || !empty($row['edu_end']))) {
    $educationEntries[] = [
        'school' => $eduSchool,
        'degree' => $eduDegree,
        'start' => (string) ($row['edu_start'] ?? ''),
        'end' => (string) ($row['edu_end'] ?? ''),
    ];
}

if (!empty($educationEntries)) {
    $primaryEducation = $educationEntries[0];
    $eduSchool = trim((string) ($primaryEducation['school'] ?? ''));
    $eduDegree = trim((string) ($primaryEducation['degree'] ?? ''));
}

$formatEducationPeriod = static function (array $entry): string {
    $start = trim((string) ($entry['start'] ?? ''));
    $end = trim((string) ($entry['end'] ?? ''));
    $startLabel = '';
    $endLabel = '';

    if ($start !== '' && strtotime($start) !== false) {
        $startLabel = date('M Y', strtotime($start));
    }

    if ($end !== '' && strtotime($end) !== false) {
        $endLabel = date('M Y', strtotime($end));
    } elseif ($start !== '') {
        $endLabel = 'Present';
    }

    if ($startLabel === '' && $endLabel === '') {
        return 'Timeline not specified';
    }

    return ($startLabel !== '' ? $startLabel : '...') . ' - ' . ($endLabel !== '' ? $endLabel : '...');
};

$nameParts = preg_split('/\s+/', $fullName ?: 'Portfolio Owner');
$initials = '';
if (!empty($nameParts)) {
    $initials .= strtoupper(substr($nameParts[0], 0, 1));
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}
if ($initials === '') {
    $initials = 'PO';
}

$primaryEduStart = !empty($educationEntries) ? (string) ($educationEntries[0]['start'] ?? '') : (string) ($row['edu_start'] ?? '');
$primaryEduEnd = !empty($educationEntries) ? (string) ($educationEntries[0]['end'] ?? '') : (string) ($row['edu_end'] ?? '');
$startYear = ($primaryEduStart !== '' && strtotime($primaryEduStart) !== false) ? date('Y', strtotime($primaryEduStart)) : '';
$endYear = ($primaryEduEnd !== '' && strtotime($primaryEduEnd) !== false) ? date('Y', strtotime($primaryEduEnd)) : '';
$eduYears = '';
if ($startYear !== '' || $endYear !== '') {
    $eduYears = ($startYear !== '' ? $startYear : '...') . ' - ' . ($endYear !== '' ? $endYear : '...');
}

$achievementLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $achievements))));
if (empty($achievementLines) && $achievements !== '') {
    $achievementLines = [
        $achievements,
    ];
}
if (empty($achievementLines)) {
    $achievementLines = ['Focused on building impactful digital experiences and practical solutions.'];
}

$skillsForBars = array_slice($skillEntries, 0, 6);

$heroHeadline = $tagline !== '' ? $tagline : (($title !== '' ? $title : 'Professional') . ' Portfolio Overview');

$heroSummaryParts = [];
if ($location !== '') $heroSummaryParts[] = 'Based in ' . $location;
if ($eduSchool !== '') $heroSummaryParts[] = 'Studied at ' . $eduSchool;
if ($eduDegree !== '') $heroSummaryParts[] = 'Specialized in ' . $eduDegree;
if ($email !== '') $heroSummaryParts[] = 'Contact: ' . $email;
$heroSummary = implode(' · ', array_slice($heroSummaryParts, 0, 3));
if ($availability !== '') $heroSummaryParts[] = 'Availability: ' . $availability;
if ($heroSummary === '') $heroSummary = 'Portfolio details are generated from your submitted form information.';

$experienceMetric = '';
$badgeExperience = '';
if ($yearsExperience !== '') {
    if (is_numeric($yearsExperience)) {
        $yearsNumeric = (float) $yearsExperience;
        if ($yearsNumeric > 0) {
            $yearsLabel = rtrim(rtrim(number_format($yearsNumeric, 1, '.', ''), '0'), '.');
            $experienceMetric = $yearsLabel . ' years';
            $badgeExperience = $yearsLabel . '+ years experience';
        }
    } else {
        $experienceMetric = $yearsExperience;
        $badgeExperience = $yearsExperience;
    }
}

$badgePrimary = $title !== '' ? $title : ($eduDegree !== '' ? $eduDegree : 'Portfolio');
$badgeSecondary = $badgeExperience !== '' ? $badgeExperience : ($availability !== '' ? $availability : 'Profile Snapshot');

$personalInfoItems = [];
if ($email !== '') $personalInfoItems[] = ['label' => 'Email', 'value' => $email];
if ($phone !== '') $personalInfoItems[] = ['label' => 'Phone', 'value' => $phone];
if ($location !== '') $personalInfoItems[] = ['label' => 'Location', 'value' => $location];
if ($linkedin !== '') $personalInfoItems[] = ['label' => 'LinkedIn', 'value' => $linkedin];
if ($availability !== '') $personalInfoItems[] = ['label' => 'Availability', 'value' => $availability];
if (empty($personalInfoItems)) {
    $personalInfoItems[] = ['label' => 'Details', 'value' => 'Add contact info in the form'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($fullName); ?> | Portfolio</title>
    <script>
        (function() {
            document.documentElement.setAttribute('data-theme', 'light');
        })();
    </script>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-2: #f8fbff;
            --ink: #0f172a;
            --muted: #475569;
            --line: #e2e8f0;
            --accent: #2563eb;
            --accent-2: #60a5fa;
            --shadow: 0 12px 32px rgba(15, 23, 42, 0.1);
            --profile-top-bg: linear-gradient(160deg, #f8fbff, #eef5ff);
            --profile-frame-bg: #e6edf9;
            --profile-initial-bg: linear-gradient(135deg, #3b82f6, #1d4ed8);
            --info-grid-bg: radial-gradient(circle at 100% 0%, rgba(37, 99, 235, 0.08), transparent 38%), linear-gradient(180deg, #f8fbff 0%, #f2f7ff 100%);
            --info-card-bg: linear-gradient(145deg, #ffffff, #f4f8ff);
            --info-card-border: #dbeafe;
            --info-label: #3f4f77;
            --badge-bg: linear-gradient(135deg, #2563eb, #60a5fa);
            --hero-panel-bg: linear-gradient(120deg, #f7fbff 0%, #eef5ff 55%, #e6f0ff 100%);
            --hero-badge-bg: linear-gradient(135deg, #2563eb, #60a5fa);
            --bio-border: #dbeafe;
            --bio-bg: #f8fbff;
            --skill-bar-bg: #dbeafe;
            --skill-fill-bg: linear-gradient(90deg, #2563eb, #60a5fa);
            --education-item-border: #dbeafe;
            --education-item-bg: #f8fbff;
        }

        :root[data-theme="warm"] {
            --bg: #f3efe8;
            --surface: #fffdf9;
            --surface-2: #fff6f0;
            --ink: #181512;
            --muted: #6f655d;
            --line: #e7ddd3;
            --accent: #e45f31;
            --accent-2: #ffb088;
            --shadow: 0 12px 32px rgba(71, 42, 23, 0.12);
            --profile-top-bg: linear-gradient(160deg, #fff9f4, #fff1e7);
            --profile-frame-bg: #f0e6de;
            --profile-initial-bg: linear-gradient(135deg, #f08f65, #de5a2b);
            --info-grid-bg: radial-gradient(circle at 100% 0%, rgba(228, 95, 49, 0.08), transparent 38%), linear-gradient(180deg, #fffaf5 0%, #fff7f1 100%);
            --info-card-bg: linear-gradient(145deg, #fff6ef, #ffefe3);
            --info-card-border: #f1d2bf;
            --info-label: #9a755f;
            --badge-bg: linear-gradient(135deg, #d85c2f, #f39c73);
            --hero-panel-bg: linear-gradient(120deg, #fff8f2 0%, #ffefe3 55%, #ffe4d2 100%);
            --hero-badge-bg: linear-gradient(135deg, #e45f31, #f49e77);
            --bio-border: #f1d8c7;
            --bio-bg: #fff7f0;
            --skill-bar-bg: #f2e6dd;
            --skill-fill-bg: linear-gradient(90deg, #df5a2c, #f39c73);
            --education-item-border: #efd9c8;
            --education-item-bg: #fff8f2;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background:
                radial-gradient(circle at 15% 20%, #f7faff 0%, transparent 36%),
                radial-gradient(circle at 85% 80%, #ebf2ff 0%, transparent 30%),
                var(--bg);
            color: var(--ink);
            font-family: 'Sora', sans-serif;
            min-height: 100vh;
        }

        :root[data-theme="warm"] body {
            background:
                radial-gradient(circle at 15% 20%, #fff7ef 0%, transparent 36%),
                radial-gradient(circle at 85% 80%, #ffe7da 0%, transparent 30%),
                var(--bg);
        }

        .portfolio-shell {
            max-width: 1400px;
            margin: 22px auto;
            padding: 0 20px 20px;
            display: grid;
            grid-template-columns: 330px 1fr;
            gap: 16px;
            min-height: calc(100vh - 110px);
        }

        .left-column,
        .right-column {
            min-height: 0;
        }

        .left-column {
            display: grid;
            grid-template-rows: auto auto;
            gap: 14px;
            align-content: start;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .portfolio-updated-note {
            max-width: 1400px;
            margin: 14px auto 0;
            padding: 0 20px;
        }

        .portfolio-updated-note .message {
            background: #ecfdf3;
            border: 1px solid #86efac;
            color: #166534;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 13px;
            font-weight: 700;
        }

        .profile-panel {
            display: block;
        }

        .info-panel {
            display: grid;
            grid-template-rows: auto auto;
        }

        .profile-top {
            padding: 22px;
            background: var(--profile-top-bg);
            border-bottom: 1px solid var(--line);
        }

        .profile-frame {
            width: 100%;
            aspect-ratio: 4 / 5;
            border-radius: 14px;
            background: var(--profile-frame-bg);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .profile-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-initials {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 74px;
            color: #fff;
            background: var(--profile-initial-bg);
        }

        .profile-name {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            font-size: 39px;
            line-height: 0.9;
            letter-spacing: -0.7px;
            margin-bottom: 8px;
        }

        .profile-title {
            font-size: 13px;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .chip-row {
            padding: 16px 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            border-bottom: 1px solid var(--line);
        }

        .chip {
            font-size: 10px;
            font-weight: 700;
            color: #6c4c3c;
            background: #ffe8db;
            border: 1px solid #ffd7c3;
            border-radius: 999px;
            padding: 6px 10px;
            letter-spacing: 0.4px;
        }

        .personal-info-grid {
            padding: 16px 22px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            background: var(--info-grid-bg);
        }

        .personal-info-card {
            background: var(--info-card-bg);
            border: 1px solid var(--info-card-border);
            border-radius: 12px;
            padding: 11px 12px;
            min-height: 64px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.55);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            opacity: 0;
            transform: translateY(7px);
            animation: infoCardIn 0.35s ease forwards;
            animation-delay: calc(var(--stagger, 0) * 80ms);
        }

        .personal-info-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(90, 56, 32, 0.12);
        }

        .personal-info-card .k {
            font-size: 9px;
            font-weight: 700;
            color: var(--info-label);
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .personal-info-card .k-badge {
            width: 20px;
            height: 20px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #fff;
            background: var(--badge-bg);
        }

        .personal-info-card .v {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
            color: #3f3630;
            word-break: break-word;
        }

        .personal-info-card .v a {
            color: var(--accent);
            text-decoration: none;
            display: inline-block;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .personal-info-card .v a:hover {
            text-decoration: underline;
        }

        @keyframes infoCardIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-links {
            padding: 14px 22px 20px;
            border-top: 1px solid var(--line);
            display: grid;
            gap: 8px;
            background: #fffdfb;
        }

        .profile-link {
            text-decoration: none;
            color: #1f4fd0;
            font-size: 11px;
            font-weight: 700;
            word-break: break-all;
            padding: 8px 10px;
            border: 1px solid #dbe6ff;
            border-radius: 8px;
            background: #f5f9ff;
        }

        .info-panel .personal-info-grid {
            border-top: 0;
        }

        .right-column {
            display: grid;
            grid-template-rows: auto auto 1fr;
            gap: 14px;
        }

        .hero-panel {
            padding: 26px 28px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
            align-items: center;
            background: var(--hero-panel-bg);
        }

        .hero-eyebrow {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #956f59;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .hero-headline {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(30px, 3.2vw, 50px);
            line-height: 0.95;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }

        .hero-copy {
            font-size: 13px;
            color: #655b54;
            line-height: 1.65;
            max-width: 58ch;
        }

        .hero-badge {
            justify-self: end;
            width: min(100%, 260px);
            aspect-ratio: 1 / 1;
            border-radius: 16px;
            background: var(--hero-badge-bg);
            display: grid;
            place-content: center;
            color: #fff;
            padding: 18px;
            text-align: center;
        }

        .hero-badge strong {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            line-height: 0.9;
            margin-bottom: 8px;
        }

        .hero-badge span {
            font-size: 12px;
            font-weight: 600;
            opacity: 0.95;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .metric-card {
            padding: 16px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: var(--shadow);
        }

        .metric-label {
            font-size: 10px;
            color: #8b7f75;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 16px;
            line-height: 1.3;
            font-weight: 700;
            color: #2a2522;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 14px;
            min-height: 0;
        }

        .section-card {
            padding: 18px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: var(--shadow);
            min-height: 0;
        }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 30px;
            line-height: 0.95;
            letter-spacing: -0.2px;
            margin-bottom: 12px;
            color: #241f1b;
        }

        .achievements-list {
            display: grid;
            gap: 10px;
        }

        .bio-block {
            margin-bottom: 14px;
            padding: 12px 14px;
            border: 1px solid var(--bio-border);
            background: var(--bio-bg);
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.6;
            color: #5f5650;
        }

        .achievement-item {
            display: grid;
            grid-template-columns: 9px 1fr;
            gap: 8px;
            align-items: start;
            font-size: 12px;
            line-height: 1.55;
            color: #5e5650;
        }

        .achievement-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            margin-top: 5px;
            background: linear-gradient(120deg, var(--accent), var(--accent-2));
        }

        .skills-stack {
            display: grid;
            gap: 11px;
        }

        .skill-row {
            display: grid;
            gap: 6px;
        }

        .skill-name {
            font-size: 11px;
            font-weight: 700;
            color: #574d46;
            text-transform: uppercase;
            letter-spacing: 0.65px;
        }

        .skill-bar {
            height: 8px;
            background: var(--skill-bar-bg);
            border-radius: 999px;
            overflow: hidden;
        }

        .skill-fill {
            height: 100%;
            background: var(--skill-fill-bg);
        }

        .education-card {
            grid-column: 1 / -1;
        }

        .education-timeline {
            display: grid;
            gap: 10px;
        }

        .education-item {
            padding: 12px 13px;
            border-radius: 10px;
            border: 1px solid var(--education-item-border);
            background: var(--education-item-bg);
        }

        .education-item-title {
            font-size: 13px;
            font-weight: 700;
            color: #2f2a26;
            line-height: 1.35;
        }

        .education-item-subtitle {
            margin-top: 4px;
            font-size: 12px;
            line-height: 1.45;
            color: #615850;
        }

        @media print {
            .site-header {
                display: none;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 10mm;
                size: A4;
            }

            html,
            body {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .portfolio-shell {
                max-width: none;
                margin: 0;
                padding: 0;
                grid-template-columns: 300px 1fr;
                gap: 10px;
            }

            .panel,
            .metric-card,
            .section-card {
                box-shadow: none;
                break-inside: avoid;
            }
        }

        @media (max-width: 1180px) {
            .portfolio-shell {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .right-column {
                grid-template-rows: auto;
            }

            .hero-panel {
                grid-template-columns: 1fr;
            }

            .hero-badge {
                justify-self: start;
            }

            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .personal-info-grid {
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
                    <li><a class="nav-link is-active" href="index.html">Home</a></li>
                    <li><a class="nav-link" href="builder.php">Create Portfolio</a></li>
                    <li data-auth="guest" hidden><a class="nav-link" href="login.php">Login</a></li>
                    <li data-auth="guest" hidden><a class="nav-link" href="signup.php">Sign Up</a></li>
                    <li data-auth="user" hidden>
                        <a class="nav-link profile-pill" href="profile.php" aria-label="Profile">
                            <span class="profile-avatar" data-profile-avatar><?php echo esc($profileInitial); ?></span>
                            <span data-profile-label><?php echo esc($profileFirstName); ?></span>
                        </a>
                    </li>
                    <li data-auth="user" hidden><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </nav>
            <a data-auth="user" hidden href="my-portfolios.php" class="my-portfolios-btn">My Portfolios</a>
            <button type="button" class="my-portfolios-btn no-print" onclick="downloadPortfolio()">Download Portfolio</button>
            <button class="theme-icon-button" type="button" data-theme-toggle aria-label="Toggle theme"></button>
        </div>
    </div>
</header>

<?php if ($showUpdatedNotice): ?>
<section class="portfolio-updated-note no-print" aria-live="polite">
    <div class="message">Portfolio updated successfully.</div>
</section>
<?php endif; ?>

<main class="portfolio-shell">
    <aside class="left-column">
        <section class="panel profile-panel">
            <div class="profile-top">
                <div class="profile-frame">
                    <?php if ($photoUrl !== ''): ?>
                        <img src="<?php echo esc($photoUrl); ?>" alt="Profile photo">
                    <?php else: ?>
                        <div class="profile-initials"><?php echo esc($initials); ?></div>
                    <?php endif; ?>
                </div>
                <h1 class="profile-name"><?php echo esc($fullName !== '' ? $fullName : 'Portfolio Owner'); ?></h1>
                <div class="profile-title"><?php echo esc($title !== '' ? $title : 'Professional'); ?></div>
            </div>
        </section>

        <section class="panel info-panel">
            <div class="personal-info-grid">
                <?php foreach ($personalInfoItems as $index => $item): ?>
                    <?php
                        $labelToken = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $item['label']), 0, 2));
                        if ($labelToken === 'LI') {
                            $labelToken = 'IN';
                        }
                    ?>
                    <div class="personal-info-card" style="--stagger: <?php echo (int) $index; ?>;">
                        <div class="k"><span class="k-badge"><?php echo esc($labelToken !== '' ? $labelToken : 'ID'); ?></span><?php echo esc($item['label']); ?></div>
                        <div class="v">
                            <?php if ($item['label'] === 'LinkedIn'): ?>
                                <?php $linkedInLabel = preg_replace('#^https?://(www\.)?#i', '', $item['value']); ?>
                                <a href="<?php echo esc($item['value']); ?>" target="_blank" rel="noopener" title="<?php echo esc($item['value']); ?>"><?php echo esc($linkedInLabel); ?></a>
                            <?php else: ?>
                                <?php echo esc($item['value']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="profile-links">
                <?php if ($github !== ''): ?><a class="profile-link" href="<?php echo esc($github); ?>" target="_blank" rel="noopener">GitHub</a><?php endif; ?>
            </div>
        </section>
    </aside>

    <section class="right-column">
        <section class="panel hero-panel">
            <div>
                <div class="hero-eyebrow">Professional Snapshot</div>
                <h2 class="hero-headline"><?php echo esc($heroHeadline); ?></h2>
                <p class="hero-copy">
                    <?php echo esc($heroSummary); ?>
                </p>
            </div>
            <div class="hero-badge">
                <strong><?php echo esc($badgePrimary); ?></strong>
                <span><?php echo esc($badgeSecondary); ?></span>
            </div>
        </section>

        <section class="metrics-grid">
            <article class="metric-card">
                <div class="metric-label">Education</div>
                <div class="metric-value"><?php echo esc($eduDegree !== '' ? $eduDegree : 'Not specified'); ?></div>
            </article>
            <article class="metric-card">
                <div class="metric-label">Institution</div>
                <div class="metric-value"><?php echo esc($eduSchool !== '' ? $eduSchool : 'Not specified'); ?></div>
            </article>
            <article class="metric-card">
                <div class="metric-label">Timeline</div>
                <div class="metric-value"><?php echo esc($eduYears !== '' ? $eduYears : 'Not specified'); ?></div>
            </article>
            <article class="metric-card">
                <div class="metric-label">Experience</div>
                <div class="metric-value"><?php echo esc($experienceMetric !== '' ? $experienceMetric : 'Not specified'); ?></div>
            </article>
        </section>

        <section class="content-grid">
            <article class="section-card">
                <h3 class="section-title">About &amp; Achievements</h3>
                <div class="bio-block">
                    <?php echo esc($bio !== '' ? $bio : 'Add your short bio in the form to showcase your professional story.'); ?>
                </div>
                <div class="achievements-list">
                    <?php foreach ($achievementLines as $line): ?>
                        <div class="achievement-item">
                            <span class="achievement-dot"></span>
                            <span><?php echo esc($line); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="section-card">
                <h3 class="section-title">Core Skills</h3>
                <div class="skills-stack">
                    <?php foreach ($skillsForBars as $skill):
                    ?>
                        <div class="skill-row">
                            <div class="skill-name"><?php echo esc($skill['name']); ?></div>
                            <div class="skill-bar"><div class="skill-fill" style="width: <?php echo (int) $skill['level']; ?>%;"></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="section-card education-card">
                <h3 class="section-title">Education Journey</h3>
                <div class="education-timeline">
                    <?php if (!empty($educationEntries)): ?>
                        <?php foreach ($educationEntries as $entry): ?>
                            <div class="education-item">
                                <div class="education-item-title">
                                    <?php echo esc(($entry['degree'] !== '' ? $entry['degree'] : 'Degree Not Specified') . ' · ' . ($entry['school'] !== '' ? $entry['school'] : 'Institution Not Specified')); ?>
                                </div>
                                <div class="education-item-subtitle"><?php echo esc($formatEducationPeriod($entry)); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="education-item">
                            <div class="education-item-title">Education details not provided</div>
                            <div class="education-item-subtitle">Add one or more education entries in the form builder.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </section>
</main>

<script>
    (() => {
        const toggleBtn = document.querySelector('[data-theme-toggle]');
        const body = document.body;
                body.dataset.theme = 'light';
                document.documentElement.setAttribute('data-theme', 'light');

        const sun = '<svg class="theme-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2.4m0 14.2v2.4M21.5 12h-2.4M4.9 12H2.5m14.2 7.1-1.7-1.7M8 6.1 6.3 4.4m11.9 0L16.5 6.1M8 17.9 6.3 19.6"/></svg>';
        const moon = '<svg class="theme-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19.5 14.5a7.8 7.8 0 1 1-9-9.8 7 7 0 1 0 9 9.8Z"/></svg>';
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
                        document.documentElement.setAttribute('data-theme', next);
            setIcon();
        });
    })();
</script>

<script>
        (() => {
            const setAuthNav = async () => {
                try {
                    const response = await fetch('auth-status.php', { credentials: 'same-origin' });
                    if (!response.ok) return;
                    const auth = await response.json();
                    const loggedIn = !!auth.loggedIn;

                    document.querySelectorAll('[data-auth="guest"]').forEach((el) => {
                        el.hidden = loggedIn;
                    });
                    document.querySelectorAll('[data-auth="user"]').forEach((el) => {
                        el.hidden = !loggedIn;
                    });

                    if (loggedIn) {
                        const fullname = (auth.fullname || 'Profile').trim();
                        const firstName = fullname.split(/\s+/)[0] || 'Profile';
                        const initial = firstName.charAt(0).toUpperCase() || 'P';
                        document.querySelectorAll('[data-profile-label]').forEach((el) => {
                            el.textContent = firstName;
                        });
                        document.querySelectorAll('[data-profile-avatar]').forEach((el) => {
                            el.textContent = initial;
                        });
                    }
                } catch (error) {
                    console.error('Could not determine auth status.', error);
                }
            };

            setAuthNav();
        })();
</script>

<script>
    async function downloadPortfolio() {
        const clone = document.documentElement.cloneNode(true);
        clone.querySelectorAll('.site-header, .portfolio-toolbar, .no-print').forEach((el) => el.remove());
        clone.querySelectorAll('script').forEach((el) => el.remove());

        const savedTheme = document.documentElement.getAttribute('data-theme') || 'light';
        clone.setAttribute('data-theme', savedTheme);
        const cloneBody = clone.querySelector('body');
        if (cloneBody) {
            cloneBody.setAttribute('data-theme', document.body.dataset.theme || savedTheme);
        }

        const imgs = Array.from(clone.querySelectorAll('img'));
        async function toDataUrl(src) {
            const absolute = new URL(src, window.location.href).href;
            const resp = await fetch(absolute, { credentials: 'same-origin' });
            if (!resp.ok) throw new Error('Failed to fetch image');
            const blob = await resp.blob();
            return await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
        }

        for (const img of imgs) {
            try {
                img.src = await toDataUrl(img.src);
            } catch (error) {
                console.warn('Skipping image in download:', img.src);
            }
        }

        const html = '<!DOCTYPE html>\n' + clone.outerHTML;
        const blob = new Blob([html], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = '<?php echo esc(str_replace(' ', '_', $fullName !== '' ? $fullName : 'portfolio')); ?>_portfolio.html';
        a.click();
        URL.revokeObjectURL(url);
    }
</script>
</body>
</html>
