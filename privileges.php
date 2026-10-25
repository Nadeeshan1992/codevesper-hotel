<?php
/**
 * Privileges Management Module
 * Left Navigation: 'Privileges Management'
 */
$pageTitle = 'Privileges Management';
$pageSubtitle = 'Configure Role-Based Access Control (RBAC) for Admin, Owner, Reception & Waiter';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Fetch all roles
$roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

// Determine active role tab
$selectedRoleId = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 3; // Default to Receptionist to easily see toggleable items
if ($selectedRoleId <= 0 || $selectedRoleId > 4) {
    $selectedRoleId = 1;
}

// Fetch all permissions grouped by module
$permissionsRaw = $db->query("SELECT * FROM permissions ORDER BY module ASC, id ASC")->fetchAll();
$permissionsByModule = [];
foreach ($permissionsRaw as $p) {
    $permissionsByModule[$p['module']][] = $p;
}

// Fetch granted permissions for selected role
$grantedStmt = $db->prepare("SELECT permission_id, is_granted FROM role_permissions WHERE role_id = ?");
$grantedStmt->execute([$selectedRoleId]);
$grantedRows = $grantedStmt->fetchAll(PDO::FETCH_KEY_PAIR); // [permission_id => is_granted]

// Get current selected role info
$selectedRoleInfo = null;
foreach ($roles as $r) {
    if ((int)$r['id'] === $selectedRoleId) {
        $selectedRoleInfo = $r;
        break;
    }
}
?>

<div class="row g-4">
    <!-- Top Role Selector Cards -->
    <div class="col-12">
        <div class="card-custom p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h5 class="fw-bold mb-1">
                        <i class="fa-solid fa-user-shield text-warning me-2"></i>Select Role to Configure
                    </h5>
                    <p class="text-muted small mb-0">Choose one of the 4 system roles to view or modify its operational privileges</p>
                </div>
                <div>
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="fa-solid fa-lock text-primary me-1"></i> Role: <strong><?= htmlspecialchars($selectedRoleInfo['name']) ?></strong>
                    </span>
                </div>
            </div>

            <!-- Role Selection Tabs -->
            <div class="row g-3">
                <?php foreach ($roles as $role): 
                    $isActive = ((int)$role['id'] === $selectedRoleId);
                    $icon = 'user';
                    $color = 'primary';
                    if ($role['slug'] === 'admin') { $icon = 'shield-halved'; $color = 'primary'; }
                    elseif ($role['slug'] === 'owner') { $icon = 'crown'; $color = 'warning'; }
                    elseif ($role['slug'] === 'reception') { $icon = 'bell-concierge'; $color = 'info'; }
                    elseif ($role['slug'] === 'waiter') { $icon = 'tablet-screen-button'; $color = 'success'; }
                ?>
                <div class="col-6 col-md-3">
                    <a href="privileges.php?role_id=<?= $role['id'] ?>" class="text-decoration-none">
                        <div class="card h-100 p-3 border-2 transition <?= $isActive ? 'border-' . $color . ' shadow-sm bg-' . $color . ' bg-opacity-10' : 'border-light-subtle bg-white' ?>">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="p-2 rounded bg-<?= $color ?> bg-opacity-25 text-<?= $color ?>">
                                    <i class="fa-solid fa-<?= $icon ?>"></i>
                                </div>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($role['name']) ?></h6>
                            </div>
                            <p class="text-muted small mb-0" style="font-size: 0.76rem;"><?= htmlspecialchars($role['description']) ?></p>
                            <?php if ($isActive): ?>
                                <div class="mt-2 text-<?= $color ?> small fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Active Configuration</div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Permissions Table & Matrix -->
    <div class="col-12">
        <div class="card-custom">
            <div class="card-header-clean d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-list-check me-2 text-primary"></i>Privileges for <?= htmlspecialchars($selectedRoleInfo['name']) ?>
                    </h5>
                    <span class="text-muted small">Toggle module access and operational capabilities</span>
                </div>
                <?php if ($selectedRoleInfo['slug'] === 'admin'): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-2">
                        <i class="fa-solid fa-lock me-1"></i> System Administrator has unrevocable master access
                    </span>
                <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-2">
                        <i class="fa-solid fa-bolt me-1"></i> Changes auto-save in real-time
                    </span>
                <?php endif; ?>
            </div>

            <div class="p-4">
                <div class="accordion" id="privilegesAccordion">
                    <?php 
                    $accIndex = 0;
                    foreach ($permissionsByModule as $moduleName => $modulePermissions): 
                        $accIndex++;
                        $collapseId = 'collapseModule' . $accIndex;
                        $moduleIcon = 'folder';
                        if ($moduleName === 'Dashboard') $moduleIcon = 'chart-pie';
                        elseif ($moduleName === 'Rooms') $moduleIcon = 'door-open';
                        elseif ($moduleName === 'Reservations') $moduleIcon = 'calendar-check';
                        elseif ($moduleName === 'Check-In/Out') $moduleIcon = 'key';
                        elseif ($moduleName === 'Housekeeping') $moduleIcon = 'broom';
                        elseif ($moduleName === 'Restaurant') $moduleIcon = 'utensils';
                        elseif ($moduleName === 'Billing') $moduleIcon = 'file-invoice-dollar';
                        elseif ($moduleName === 'Staff') $moduleIcon = 'users';
                        elseif ($moduleName === 'Stock') $moduleIcon = 'boxes-stacked';
                        elseif ($moduleName === 'Reports') $moduleIcon = 'chart-line';
                        elseif ($moduleName === 'Privileges') $moduleIcon = 'shield-halved';
                    ?>
                    <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-none">
                        <h2 class="accordion-header" id="heading<?= $accIndex ?>">
                            <button class="accordion-button bg-light fw-bold text-dark py-3" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="true">
                                <i class="fa-solid fa-<?= $moduleIcon ?> text-warning me-2"></i>
                                <?= htmlspecialchars($moduleName) ?> Module
                                <span class="badge bg-secondary-subtle text-dark ms-2 small" style="font-size: 0.7rem;">
                                    <?= count($modulePermissions) ?> Privileges
                                </span>
                            </button>
                        </h2>
                        <div id="<?= $collapseId ?>" class="accordion-collapse collapse show">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light small text-uppercase text-muted">
                                            <tr>
                                                <th style="width: 35%;">Privilege / Capability</th>
                                                <th style="width: 45%;">Description</th>
                                                <th style="width: 20%;" class="text-center">Status / Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($modulePermissions as $p): 
                                                $permId = (int)$p['id'];
                                                // Admin has all granted
                                                $isGranted = ($selectedRoleInfo['slug'] === 'admin') ? true : (!empty($grantedRows[$permId]));
                                                $switchId = 'perm_switch_' . $permId;
                                                $isDisabled = ($selectedRoleInfo['slug'] === 'admin');
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                                    <code class="text-muted small" style="font-size: 0.72rem;"><?= htmlspecialchars($p['slug']) ?></code>
                                                </td>
                                                <td>
                                                    <span class="text-muted small"><?= htmlspecialchars($p['description']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check form-switch d-inline-block">
                                                        <input 
                                                            class="form-check-input privilege-toggle" 
                                                            type="checkbox" 
                                                            role="switch" 
                                                            id="<?= $switchId ?>"
                                                            data-role-id="<?= $selectedRoleId ?>"
                                                            data-permission-id="<?= $permId ?>"
                                                            <?= $isGranted ? 'checked' : '' ?>
                                                            <?= $isDisabled ? 'disabled' : '' ?>
                                                            style="width: 44px; height: 22px; cursor: <?= $isDisabled ? 'not-allowed' : 'pointer' ?>;"
                                                        >
                                                    </div>
                                                    <div class="small fw-semibold mt-1 <?= $isGranted ? 'text-success' : 'text-danger' ?>" id="status_label_<?= $permId ?>">
                                                        <?= $isGranted ? 'Enabled' : 'Disabled' ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.privilege-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const roleId = this.getAttribute('data-role-id');
            const permId = this.getAttribute('data-permission-id');
            const isChecked = this.checked ? 1 : 0;
            const statusLabel = document.getElementById('status_label_' + permId);

            // Optimistic UI update
            if (isChecked) {
                statusLabel.innerText = 'Enabled';
                statusLabel.classList.remove('text-danger');
                statusLabel.classList.add('text-success');
            } else {
                statusLabel.innerText = 'Disabled';
                statusLabel.classList.remove('text-success');
                statusLabel.classList.add('text-danger');
            }

            // AJAX request to persist
            const formData = new FormData();
            formData.append('role_id', roleId);
            formData.append('permission_id', permId);
            formData.append('is_granted', isChecked);

            fetch('api/privileges.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.message,
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: data.message
                    });
                    // Revert toggle
                    toggle.checked = !isChecked;
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Unable to update privilege. Please check your connection.'
                });
                toggle.checked = !isChecked;
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
