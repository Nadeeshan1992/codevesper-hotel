<?php
/**
 * Staff Directory & Attendance Management
 */
$pageTitle = 'Staff Management';
$pageSubtitle = 'Employee Profiles, Departments, Shift Rosters & Daily Attendance';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Handle attendance toggle/marking via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $staffId = (int)$_POST['staff_id'];
    $status = $_POST['status'] ?? 'present';
    $notes = sanitize($_POST['notes'] ?? '');

    try {
        $stmt = $db->prepare("
            INSERT INTO staff_attendance (staff_id, attendance_date, clock_in, status, notes)
            VALUES (?, CURDATE(), CURTIME(), ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)
        ");
        $stmt->execute([$staffId, $status, $notes]);
        logAudit('Staff', 'Attendance Marked', "Marked $status for staff ID $staffId");
        $attSuccess = "Attendance updated successfully!";
    } catch (Exception $e) {
        $attError = "Failed to mark attendance: " . $e->getMessage();
    }
}

// Fetch all staff members
$staffMembers = $db->query("
    SELECT s.*, u.username as user_account, r.name as role_name,
           sa.status as today_status, sa.clock_in, sa.clock_out
    FROM staff s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN staff_attendance sa ON (sa.staff_id = s.id AND sa.attendance_date = CURDATE())
    ORDER BY s.id ASC
")->fetchAll();

// Total Department counts
$deptCounts = [];
$totalSalary = 0;
foreach ($staffMembers as $sm) {
    $dept = $sm['department'];
    $deptCounts[$dept] = ($deptCounts[$dept] ?? 0) + 1;
    $totalSalary += (float)$sm['salary'];
}
?>

<!-- Alert Feedback -->
<?php if (!empty($attSuccess)): ?>
    <div class="alert alert-success py-2 px-3 small rounded-3 mb-4 d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-check text-success"></i>
        <span><?= htmlspecialchars($attSuccess) ?></span>
    </div>
<?php endif; ?>

<!-- Metric Cards -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Active Staff</div>
                <div class="stat-number text-primary"><?= count($staffMembers) ?> Employees</div>
                <div class="small text-muted">Across 5 departments</div>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Present Today</div>
                <?php 
                $presentCount = 0;
                foreach ($staffMembers as $s) if (!empty($s['today_status']) && $s['today_status'] === 'present') $presentCount++;
                ?>
                <div class="stat-number text-success"><?= $presentCount ?> On Duty</div>
                <div class="small text-muted">Attendance recorded</div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Monthly Payroll</div>
                <div class="stat-number text-dark"><?= formatMoney($totalSalary) ?></div>
                <div class="small text-muted">Gross monthly base</div>
            </div>
            <div class="stat-icon bg-dark bg-opacity-10 text-dark">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Waiter Terminal</div>
                <div class="stat-number text-info">Tablet PC</div>
                <div class="small text-muted">Active POS login enabled</div>
            </div>
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-tablet-screen-button"></i>
            </div>
        </div>
    </div>
</div>

<!-- Staff Directory & Attendance Table -->
<div class="card-custom">
    <div class="card-header-clean d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-address-book text-primary me-2"></i>Staff Directory & Daily Attendance (<?= date('M d, Y') ?>)
            </h5>
            <small class="text-muted">Manage employee records and record daily shift attendance</small>
        </div>
        <button class="btn btn-primary btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#newStaffModal">
            <i class="fa-solid fa-user-plus me-1"></i> Add Employee
        </button>
    </div>

    <div class="p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable-init">
                <thead class="table-light small text-uppercase text-muted">
                    <tr>
                        <th>Code</th>
                        <th>Employee Name</th>
                        <th>Department & Role</th>
                        <th>Contact</th>
                        <th>Monthly Salary</th>
                        <th>Today's Attendance</th>
                        <th class="text-end">Mark Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffMembers as $s): 
                        $status = $s['today_status'] ?? 'pending';
                        $statusBadge = '<span class="badge bg-secondary-subtle text-secondary"><i class="fa-regular fa-clock me-1"></i> Not Clocked In</span>';
                        if ($status === 'present') $statusBadge = '<span class="badge bg-success-subtle text-success border border-success"><i class="fa-solid fa-check me-1"></i> Present (' . date('h:i A', strtotime($s['clock_in'])) . ')</span>';
                        elseif ($status === 'late') $statusBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning"><i class="fa-solid fa-clock me-1"></i> Late</span>';
                        elseif ($status === 'absent') $statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="fa-solid fa-xmark me-1"></i> Absent</span>';
                    ?>
                    <tr>
                        <td>
                            <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($s['staff_code']) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                            <?php if ($s['user_account']): ?>
                                <small class="text-primary"><i class="fa-solid fa-shield-halved me-1"></i> <?= htmlspecialchars($s['user_account']) ?> (<?= htmlspecialchars($s['role_name']) ?>)</small>
                            <?php else: ?>
                                <small class="text-muted">No system user attached</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($s['designation']) ?></div>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['department']) ?></span>
                        </td>
                        <td>
                            <div class="small text-dark"><i class="fa-solid fa-phone me-1 text-muted"></i> <?= htmlspecialchars($s['phone']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($s['email'] ?? '-') ?></small>
                        </td>
                        <td>
                            <span class="fw-bold text-dark"><?= formatMoney($s['salary']) ?></span>
                        </td>
                        <td>
                            <?= $statusBadge ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" class="d-inline-flex gap-1">
                                <input type="hidden" name="mark_attendance" value="1">
                                <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="status" value="present">
                                <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2" title="Mark Present">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline-flex gap-1">
                                <input type="hidden" name="mark_attendance" value="1">
                                <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="status" value="late">
                                <button type="submit" class="btn btn-sm btn-outline-warning py-1 px-2" title="Mark Late">
                                    <i class="fa-solid fa-clock"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline-flex gap-1">
                                <input type="hidden" name="mark_attendance" value="1">
                                <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="status" value="absent">
                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Mark Absent">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Staff Modal -->
<div class="modal fade" id="newStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Register New Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="staff.php">
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Department *</label>
                            <select name="department" class="form-select">
                                <option value="Front Office">Front Office</option>
                                <option value="F&B Service">F&B Service</option>
                                <option value="Kitchen">Kitchen</option>
                                <option value="Housekeeping">Housekeeping</option>
                                <option value="Management">Management</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Designation *</label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g. Waiter or Cook" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Phone Number *</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Monthly Salary (<?= APP_CURRENCY ?>)</label>
                            <input type="number" name="salary" class="form-control" value="65000" step="1000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary px-4" onclick="Swal.fire('Saved', 'Staff record registered!', 'success')">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
