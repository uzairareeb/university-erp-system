<?php
// One-time setup: create tables from database/schema.sql and seed demo data.
// Open /setup.php once after the first deploy, then DELETE this file.
// It does nothing if users already exist (safe to open twice).
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>University ERP — Setup</h2><pre>";

$sql = file_get_contents(__DIR__ . '/database/schema.sql');
if ($sql === false) { echo "schema.sql not found.\n"; exit; }
// Strip full-line -- comments first (otherwise the first CREATE TABLE,
// which follows the header comment, would be skipped as a comment).
$sql = preg_replace('/^--.*$/m', '', $sql);
// Split on ";\n" — schema.sql has no stored procedures, so this is safe.
$parts = preg_split('/;\s*\n/', $sql);
$ok = 0; $fail = 0;
foreach ($parts as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') continue;
    if ($conn->query($stmt)) { $ok++; }
    else { $fail++; echo "FAIL: " . $conn->error . "\n-- " . substr($stmt, 0, 120) . "\n"; }
}
echo "Schema statements: $ok ok, $fail failed.\n";

$cnt = $conn->query("SELECT COUNT(*) AS c FROM users");
$n = $cnt ? (int) $cnt->fetch_assoc()['c'] : 0;
echo "Existing users: $n\n";
if ($n === 0) {
    $demos = [
        ['admin', 'admin123', 'admin'],
        ['faculty1', 'faculty123', 'faculty'],
        ['student1', 'student123', 'student'],
        ['accountant1', 'account123', 'accountant'],
    ];
    $ids = [];
    foreach ($demos as $d) {
        $hash = password_hash($d[1], PASSWORD_DEFAULT);
        $st = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $st->bind_param("sss", $d[0], $hash, $d[2]);
        $st->execute();
        $ids[$d[2]] = $conn->insert_id;
        echo "Created user: {$d[0]} / {$d[2]}\n";
    }
    // Linked records
    $st = $conn->prepare("INSERT INTO faculty (user_id, name, email, phone, department, designation) VALUES (?, 'Dr. Ayesha Khan', 'ayesha@uni.edu', '0300-1234567', 'CS', 'Lecturer')");
    $st->bind_param("i", $ids['faculty']);
    $st->execute();
    $fac_id = $conn->insert_id;
    $st = $conn->prepare("INSERT INTO students (user_id, name, roll_no, email, phone, department) VALUES (?, 'Ali Ahmed', 'BCS001', 'ali@student.uni.edu', '0301-7654321', 'CS')");
    $st->bind_param("i", $ids['student']);
    $st->execute();
    $stu_id = $conn->insert_id;
    $conn->query("INSERT INTO courses (course_code, course_name, credits, department, faculty_id) VALUES ('CS101', 'Introduction to Programming', 3, 'CS', $fac_id)");
    $course_id = $conn->insert_id;
    $conn->query("INSERT IGNORE INTO enrollments (student_id, course_id, enrolled_at) VALUES ($stu_id, $course_id, CURDATE())");
    $conn->query("INSERT IGNORE INTO attendance (student_id, course_id, attendance_date, status) VALUES ($stu_id, $course_id, CURDATE(), 'Present')");
    $conn->query("INSERT INTO marks (student_id, course_id, exam_type, marks_obtained, total_marks, exam_date) VALUES ($stu_id, $course_id, 'Quiz', 18, 20, CURDATE())");
    $conn->query("INSERT INTO fee_payments (student_id, fee_id, amount_paid, payment_date, receipt_no, collected_by, payment_method) VALUES ($stu_id, 1, 50000, CURDATE(), 'REC-DEMO-001', {$ids['accountant']}, 'Cash')");
    echo "Seeded: 1 faculty, 1 student, 1 course (CS101), enrollment, attendance, marks, fee payment.\n";
}

echo "\nDemo logins:\n";
echo "  Admin:      admin / admin123\n";
echo "  Faculty:    faculty1 / faculty123\n";
echo "  Student:    student1 / student123\n";
echo "  Accountant: accountant1 / account123\n";
echo "\nDELETE setup.php after this, then go to /login.php\n</pre>";
?>
