<?php
// Created for the Vercel deploy: faculty_dashboard.php linked here,
// but the file did not exist in the original repo.
session_start();
require_once __DIR__ . '/../includes/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'faculty') { header("Location: ../login.php"); exit(); }
$fac = $conn->query("SELECT id FROM faculty WHERE user_id=" . (int)$_SESSION['user_id'])->fetch_assoc();
if (!$fac) die("Faculty record not found. Contact Admin.");
$rows = $conn->query("SELECT m.exam_type, m.marks_obtained, m.total_marks, m.exam_date, s.roll_no, s.name, c.course_code FROM marks m JOIN students s ON m.student_id=s.id JOIN courses c ON m.course_id=c.id WHERE c.faculty_id=" . (int)$fac['id'] . " ORDER BY m.exam_date DESC LIMIT 100");
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>View Marks</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light"><div class="container mt-4">
<a href="faculty_dashboard.php" class="btn btn-secondary mb-3">Back</a>
<div class="card"><div class="card-header bg-success text-white">Marks — My Courses</div><div class="card-body"><table class="table table-bordered"><thead><tr><th>Date</th><th>Roll No</th><th>Student</th><th>Course</th><th>Exam</th><th>Marks</th></tr></thead><tbody>
<?php if ($rows && $rows->num_rows > 0) { while ($r = $rows->fetch_assoc()) { echo "<tr><td>" . htmlspecialchars($r['exam_date']) . "</td><td>" . htmlspecialchars($r['roll_no']) . "</td><td>" . htmlspecialchars($r['name']) . "</td><td>" . htmlspecialchars($r['course_code']) . "</td><td>" . htmlspecialchars($r['exam_type']) . "</td><td>" . htmlspecialchars($r['marks_obtained']) . " / " . htmlspecialchars($r['total_marks']) . "</td></tr>"; } } else { echo "<tr><td colspan='6' class='text-center text-muted'>No marks records yet.</td></tr>"; } ?>
</tbody></table></div></div></div></body></html>
