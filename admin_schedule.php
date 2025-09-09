<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// This part it handle AJAX POST for updating status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $apptId = intval($_POST['appointment_id']);
    $newStatus = $_POST['status'];
    $allowedStatuses = ['pending', 'approved', 'rejected', 'completed'];
    if (in_array($newStatus, $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $apptId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
    }
    exit;
}

// this handle AJAX GET for fetching appointment details for a given date
if (isset($_GET['fetch_appointments']) && isset($_GET['date'])) {
    $date = $_GET['date'];
    // Fetch appointments and join students for full_name
    $sql = "SELECT a.id, a.user_type, a.user_number, a.appointment_date, a.appointment_time, a.service, a.status, a.created_at, a.additional_details, 
                   COALESCE(s.full_name, 'Guest') AS full_name
            FROM appointments a
            LEFT JOIN students s ON s.id = a.user_id
            WHERE a.appointment_date = ?
            ORDER BY a.appointment_time";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $res = $stmt->get_result();
    $appointments = [];
    while ($row = $res->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
    echo json_encode($appointments);
    exit;
}

// -- Calendar logic for scheduling --

date_default_timezone_set('Asia/Manila');

$currentMonth = date('n');
$currentYear = date('Y');

$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// Limit earliest month to June 2025
if ($year < 2025 || ($year == 2025 && $month < 6)) {
    $year = 2025;
    $month = 6;
}
if ($month > 12) {
    $month = 1;
    $year++;
} elseif ($month < 1) {
    $month = 12;
    $year--;
}

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDay = date('w', $firstDay);
$monthName = date('F', $firstDay);

$prevMonth = $month - 1;
$prevYear = $year;
$nextMonth = $month + 1;
$nextYear = $year;

if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$prevMonthDays = date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));

$startDate = "$year-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-01";
$endDate = "$year-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-$daysInMonth";

// this gets all appointments this month (but this exclude rejected appointments)
$sql = "
    SELECT 
      a.appointment_date, 
      a.appointment_time, 
      a.status, 
      a.user_id,
      s.full_name 
    FROM appointments a
    LEFT JOIN students s ON s.id = a.user_id
    WHERE a.appointment_date BETWEEN '$startDate' AND '$endDate' AND a.status != 'rejected'
    ORDER BY a.appointment_date, a.appointment_time
";

$result = $conn->query($sql);
$appointmentsByDate = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['appointment_date'];
    if (!isset($appointmentsByDate[$date])) {
        $appointmentsByDate[$date] = [];
    }
    $appointmentsByDate[$date][] = [
        'time' => substr($row['appointment_time'], 0, 5),
        'full_name' => $row['full_name'] ?? 'Guest'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Schedule - Digital Reporting System</title>
<style>
  
  body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f4f4;
    color: #333;
  }
  .admin-header {
    background-color: maroon;
    color: white;
    display: flex;
    align-items: center;
    padding: 14px 25px;
    position: relative;
    height: 60px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    user-select: none;
  }
  .header-title {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-weight: 600;
    font-size: 24px;
    letter-spacing: 1px;
  }
  .nav-links {
    margin-left: auto;
    display: flex;
    align-items: center;
    font-weight: 500;
    font-size: 17px;
  }
  .nav-links a {
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    position: relative;
    transition:
      color 0.25s ease,
      transform 0.2s ease;
    cursor: pointer;
  }
  .nav-links a + a::before {
    content: "";
    position: absolute;
    left: 0;
    top: 20%;
    height: 60%;
    width: 1.5px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 1px;
  }
  .nav-links a:hover {
    color: #ffb3b3;
    transform: scale(1.07);
    text-shadow: 0 0 6px #ffb3b3;
  }
  .main-content {
    max-width: 1000px;
    margin: 30px auto 60px;
    background: white;
    padding: 28px 32px;
    border-radius: 10px;
    box-shadow: 0 7px 20px rgba(0,0,0,0.08);
  }
  table.calendar {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
    font-size: 16px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0,0,0,0.07);
  }
  table.calendar th, table.calendar td {
    border: 1px solid #ddd;
    width: 14.28%;
    vertical-align: top;
    padding: 10px 8px;
    min-height: 110px;
    position: relative;
    background: white;
    border-radius: 8px;
  }
  table.calendar th {
    background: #f9d6d6;
    color: maroon;
    font-weight: 700;
    text-align: center;
    user-select: none;
    font-size: 15px;
  }
  .weekday {
    background: #ffe6e6;
  }
  .dimmed {
    color: #bbb;
    font-size: 14px;
    background: #fafafa !important;
    user-select: none;
  }
  .date-number {
    font-weight: 700;
    font-size: 18px;
    margin-bottom: 8px;
    color: maroon;
    user-select: none;
  }
  td.booked {
    background: #f8c1c1;
    box-shadow: inset 0 0 10px #a00000cc;
    border-color: #a00000;
    cursor: pointer;
  }
  .appointments {
    margin-top: 5px;
    font-size: 13px;
    max-height: 72px;
    overflow-y: auto;
    padding-right: 4px;
    user-select: text;
  }
  .appointment-entry {
    margin-bottom: 3px;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    line-height: 1.2;
  }
  .appointment-entry strong {
    color: #700000;
  }
  .note {
    margin-top: 18px;
    font-style: italic;
    font-size: 14px;
    color: #444;
    text-align: center;
    user-select: none;
  }
  .note span {
    display: inline-block;
    width: 14px;
    height: 14px;
    background: #f8c1c1;
    border: 1.5px solid #a00000;
    margin-right: 6px;
    vertical-align: middle;
    border-radius: 3px;
  }
  .nav-buttons {
    text-align: center;
    margin-bottom: 25px;
  }
  .nav-buttons a {
    background: maroon;
    color: white;
    padding: 12px 25px;
    margin: 0 12px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 16px;
    text-decoration: none;
    box-shadow: 0 3px 6px rgba(163,0,0,0.7);
    transition: background-color 0.3s ease, transform 0.2s ease;
    user-select: none;
  }
  .nav-buttons a:hover {
    background: #8b0000;
    transform: scale(1.05);
  }
  /* Modal styles */
  .modal-bg {
    display: none;
    position: fixed;
    z-index: 1100;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.48);
    overflow-y: auto;
  }
  .modal-box {
    background: white;
    max-width: 900px; /* wider */
    margin: 60px auto 100px;
    padding: 25px 30px;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.18);
    font-size: 15px;
  }

  .modal-box h3 {
    color: maroon;
    margin-top: 0;
    font-weight: 600;
    font-size: 22px;
  }
  .modal-close {
    float: right;
    font-size: 24px;
    font-weight: 700;
    color: #a00;
    cursor: pointer;
    user-select: none;
  }
  .modal-close:hover {
    color: #600;
  }
  table.modal-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
  }
  table.modal-table th, table.modal-table td {
    border: 1px solid #ccc;
    padding: 8px 10px;
    text-align: left;
  }
  table.modal-table th {
    background: #f5d6d6;
    color: maroon;
    font-weight: 600;
  }
  select.status-select {
    padding: 6px 10px;
    font-size: 14px;
    border-radius: 5px;
    border: 1px solid #ccc;
    min-width: 120px;
  }
  button.status-update-btn {
    background: maroon;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    cursor: pointer;
    font-weight: 600;
    margin-top: 12px;
    transition: background-color 0.3s ease;
  }
  button.status-update-btn:hover {
    background-color: #c04040;
  }
  .status-form-row {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .status-form-row label {
    min-width: 90px;
    font-weight: 600;
    color: #555;
  }

  /* small red variant for Reject button */
  .btn-reject {
    background: #8b0000;
  }
  .btn-reject:hover {
    background: #6d0000;
  }

 
  #rejectConfirm.modal-bg {
    z-index: 1205;
  }

  /* Responsive for narrow screens on mobile devices */
  @media screen and (max-width: 650px) {
    .header-title {
      font-size: 20px;
    }
    .nav-links {
      font-size: 15px;
    }
    table.calendar th, table.calendar td {
      font-size: 13px;
      min-height: 90px;
      padding: 6px 8px;
    }
    .appointments {
      font-size: 11px;
      max-height: 55px;
    }
    .nav-buttons a {
      padding: 8px 15px;
      font-size: 14px;
      margin: 0 6px;
    }
    .modal-box {
      width: 90%;
      margin: 40px auto 80px;
      padding: 15px 20px;
    }
  }
</style>
</head>
<body>

<header class="admin-header">
  <div class="header-title">Digital Records & Reporting System </div>
  <nav class="nav-links" aria-label="Admin navigation">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="admin_profile.php">Profile</a>
     <a href="more.php">More</a>
    <a href="admin_logout.php" onclick="return confirmLogout()">Logout</a>
  </nav>
</header>

<div class="main-content" role="main">
  <h2>Appointment Schedule Availability — <?= htmlspecialchars($monthName . " " . $year) ?></h2>

  <div class="nav-buttons" role="navigation" aria-label="Month navigation">
    <?php if (!($prevYear < 2025 || ($prevYear == 2025 && $prevMonth < 6))): ?>
      <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" aria-label="Previous month">&larr; Previous</a>
    <?php endif; ?>
    <a href="?month=<?= $currentMonth ?>&year=<?= $currentYear ?>" aria-label="Current month">Today</a>
    <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" aria-label="Next month">Next &rarr;</a>
  </div>

  <table class="calendar" role="grid" aria-labelledby="calendarHeading">
    <thead>
      <tr>
        <th scope="col">Sun</th><th scope="col">Mon</th><th scope="col">Tue</th><th scope="col">Wed</th><th scope="col">Thu</th><th scope="col">Fri</th><th scope="col">Sat</th>
      </tr>
    </thead>
    <tbody>
      <tr>
      <?php
      $day = 1;
      $cell = 0;

      // Previous month's trailing days
      $prevMonthDayStart = $prevMonthDays - $startDay + 1;
      for ($i = 0; $i < $startDay; $i++, $cell++) {
          $weekday = $cell % 7;
          $class = ($weekday >= 1 && $weekday <= 5) ? 'weekday dimmed' : 'dimmed';
          $prevMonthLabel = date('M', mktime(0,0,0,$prevMonth,1,$prevYear));
          echo "<td class='$class'>{$prevMonthDayStart}<br><small>$prevMonthLabel</small></td>";
          $prevMonthDayStart++;
      }

      // Current month days
      while ($day <= $daysInMonth) {
          $weekday = $cell % 7;
          $isWeekday = ($weekday >= 1 && $weekday <= 5);

          $dateStr = "$year-" . str_pad($month,2,"0",STR_PAD_LEFT) . "-" . str_pad($day,2,"0",STR_PAD_LEFT);
          $hasAppointment = isset($appointmentsByDate[$dateStr]);

          $class = '';
          if ($isWeekday) {
              $class .= 'weekday';
          }
          if ($hasAppointment) {
              $class .= ' booked';
              // Make date clickable only if has appointment
              echo "<td class='$class' tabindex='0' role='button' aria-pressed='false' data-date='$dateStr' onclick='openModal(\"$dateStr\")' onkeypress='if(event.key === \"Enter\") openModal(\"$dateStr\")'>";
          } else {
              echo "<td class='$class'>";
          }

          echo "<span class='date-number'>$day</span>";

          if ($hasAppointment) {
              echo "<div class='appointments'>";
              foreach ($appointmentsByDate[$dateStr] as $appt) {
                  $fullName = htmlspecialchars($appt['full_name']);
                  $time = htmlspecialchars($appt['time']);
                  echo "<div class='appointment-entry'><strong>$time</strong> - $fullName</div>";
              }
              echo "</div>";
          }

          echo "</td>";
          $day++;
          $cell++;
          if ($cell % 7 === 0 && $day <= $daysInMonth) echo "</tr><tr>";
      }

      // Next month's leading days
      $nextDay = 1;
      while ($cell % 7 !== 0) {
          $weekday = $cell % 7;
          $class = ($weekday >= 1 && $weekday <= 5) ? 'weekday dimmed' : 'dimmed';
          $nextMonthLabel = date('M', mktime(0,0,0,$nextMonth,1,$nextYear));
          echo "<td class='$class'>$nextDay<br><small>$nextMonthLabel</small></td>";
          $nextDay++;
          $cell++;
      }
      ?>
      </tr>
    </tbody>
  </table>

  <p class="note"><span></span> Days with booked appointments</p>
</div>

<!-- Modal for appointments -->
<div id="appointmentModal" class="modal-bg" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDesc">
  <div class="modal-box">
    <span class="modal-close" role="button" aria-label="Close modal" tabindex="0" onclick="closeModal()" onkeypress="if(event.key==='Enter') closeModal()">×</span>
    <h3 id="modalTitle">Appointments on <span id="modalDate"></span></h3>
    <div id="modalDesc">
      <table class="modal-table" aria-describedby="modalDate">
        <thead>
          <tr>
            <th>User Type</th>
            <th>User Number</th>
            <th>Full Name</th>
            <th>Time</th>
            <th>Service</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Additional Details</th>
            <th>Edit Status</th>
          </tr>
        </thead>
        <tbody id="modalBody">
          <!-- Filled dynamically -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Reject confirmation modal -->
<div id="rejectConfirm" class="modal-bg" role="dialog" aria-modal="true" aria-labelledby="rejectTitle" aria-describedby="rejectDesc">
  <div class="modal-box" style="max-width:480px">
    <span class="modal-close" role="button" aria-label="Close modal" tabindex="0" onclick="closeRejectConfirm()" onkeypress="if(event.key==='Enter') closeRejectConfirm()">×</span>
    <h3 id="rejectTitle">Confirm Rejection</h3>
    <div id="rejectDesc">
      <p>Are you sure you want to <strong>reject</strong> this appointment?</p>
      <div class="status-form-row" style="justify-content:flex-end; margin-top:10px;">
        <button type="button" class="status-update-btn" onclick="closeRejectConfirm()">Cancel</button>
        <button type="button" class="status-update-btn btn-reject" onclick="confirmReject()">Yes, Reject</button>
      </div>
    </div>
  </div>
</div>

<script>
function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}

const modal = document.getElementById('appointmentModal');
const modalDateSpan = document.getElementById('modalDate');
const modalBody = document.getElementById('modalBody');

const rejectConfirmModal = document.getElementById('rejectConfirm');
let pendingRejectApptId = null;

/* ---------- Modal open/close ---------- */
function openModal(date) {
    modal.style.display = 'block';
    modalDateSpan.textContent = date;
    modalBody.innerHTML = '<tr><td colspan="9">Loading...</td></tr>';

    fetch(`<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>?fetch_appointments=1&date=${date}`)
      .then(response => response.json())
      .then(data => {
          if (!data || data.length === 0) {
              modalBody.innerHTML = '<tr><td colspan="9">No appointments found.</td></tr>';
              return;
          }
          modalBody.innerHTML = '';
          data.forEach(appt => {
              const statusColor = {
                  'pending': 'orange',
                  'approved': 'green',
                  'rejected': 'red',
                  'completed': 'blue'
              }[appt.status] || 'gray';

              const row = document.createElement('tr');

              // Build the "Edit Status" cell:
              // - If pending: show Approve + Reject buttons
              // - Else: read-only dash
              const editCellHtml = (appt.status === 'pending')
                ? `
                  <div class="status-form-row">
                    <button type="button" class="status-update-btn"
                      onclick="approveAppointment(${appt.id})">Approve</button>
                    <button type="button" class="status-update-btn btn-reject"
                      onclick="showRejectConfirm(${appt.id})">Reject</button>
                  </div>`
                : `-`;

              row.innerHTML = `
                <td>${appt.user_type}</td>
                <td>${appt.user_number}</td>
                <td>${escapeHtml(appt.full_name)}</td>
                <td>${appt.appointment_time.substring(0,5)}</td>
                <td>${escapeHtml(appt.service)}</td>
                <td><span style="color: white; background-color: ${statusColor}; padding: 5px 9px; border-radius: 12px; font-weight: 600; text-transform: capitalize;">${appt.status}</span></td>
                <td>${appt.created_at}</td>
                <td>${escapeHtml(appt.additional_details || '')}</td>
                <td>${editCellHtml}</td>
              `;

              modalBody.appendChild(row);
          });
      })
      .catch(() => {
          modalBody.innerHTML = '<tr><td colspan="9">Failed to load appointments.</td></tr>';
      });
}

function closeModal() {
    modal.style.display = 'none';
}

/* ---------- Reject confirmation ---------- */
function showRejectConfirm(apptId) {
    pendingRejectApptId = apptId;
    rejectConfirmModal.style.display = 'block';
}

function closeRejectConfirm() {
    rejectConfirmModal.style.display = 'none';
    pendingRejectApptId = null;
}

function confirmReject() {
    if (!pendingRejectApptId) return;
    changeStatus(pendingRejectApptId, 'rejected', function() {
        closeRejectConfirm();
        // reload modal contents to update labels
        openModal(modalDateSpan.textContent);
    });
}

/* ---------- Approve flow (redirect to report.php) ---------- */
function approveAppointment(apptId) {
    changeStatus(apptId, 'approved', function() {
        window.location.href = 'report.php?appointment_id=' + encodeURIComponent(apptId);
    });
}

/* ---------- Shared status changer (uses your existing POST handler) ---------- */
function changeStatus(apptId, newStatus, onSuccess) {
    fetch('<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            update_status: '1',
            appointment_id: apptId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.success) {
            if (typeof onSuccess === 'function') onSuccess();
        } else {
            alert('Update failed: ' + (data && data.error ? data.error : 'Unknown error'));
        }
    })
    .catch(() => alert('Network error while updating status.'));
}

/* ---------- Legacy form handler (kept if you still have forms elsewhere) ---------- */
function updateStatus(event, apptId) {
    event.preventDefault();
    const form = event.target;
    const statusSelect = form.querySelector('select[name="status"]');
    const newStatus = statusSelect.value;

    changeStatus(apptId, newStatus, function() {
        alert('Status updated.');
        openModal(modalDateSpan.textContent); // reload modal content
    });
    return false;
}

/* ---------- Simple escape ---------- */
function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function(m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m];
    });
}
</script>

</body>
</html>
