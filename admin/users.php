<?php
require_once '../check_login.php';
require_once '../includes/db.php';

// ── Fetch all users ──────────────────────────────────────────────────────────
$result = mysqli_query($conn, "SELECT id, full_name, email, role, status, profile_photo, created_at FROM users ORDER BY created_at DESC");
$users  = [];
while ($r = mysqli_fetch_assoc($result)) {
    $users[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - H&M Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        .modal-content { border-radius: 0 !important; }

        .avatar-initial {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #E50010;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php' ?>

    <!-- Toast notification -->
    <div class="toast-msg" id="toastMsg"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1>User Management</h1>
            <?php include 'header.php' ?>
        </div>

        <!-- Users Section -->
        <div class="users-section">
            <div class="section-header">
                <h2>All Users <span style="font-size:13px;color:#999;font-weight:400;">(<?= count($users) ?> total)</span></h2>
                <a class="btn-add" href="add_user.php">
                    <i class="fas fa-plus"></i>Add New User
                </a>
            </div>



            <div class="table-responsive">
                <table class="custom-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th style="white-space:nowrap; width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <?php if (!empty($u['profile_pic'])): ?>
                                    <img src="../<?= htmlspecialchars($u['profile_pic']) ?>" alt="<?= htmlspecialchars($u['full_name']) ?>" class="user-avatar">
                                <?php else: ?>
                                    <span class="avatar-initial"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php $role = strtolower($u['role'] ?? 'customer'); ?>
                                <span class="badge-status <?= $role === 'admin' ? 'badge-admin' : 'badge-customer' ?>">
                                    <?= ucfirst($role) ?>
                                </span>
                            </td>
                            <td>
                                <?php $st = strtolower($u['status'] ?? 'active'); ?>
                                <span class="badge-status <?= $st === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ucfirst($st) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td style="white-space:nowrap;">
                                <div class="action-group">
                                    <button class="action-btn edit"
                                        onclick="openEditModal(<?= $u['id'] ?>, '<?= addslashes(htmlspecialchars($u['full_name'])) ?>', '<?= addslashes(htmlspecialchars($u['email'])) ?>', '<?= $u['role'] ?? 'customer' ?>', '<?= $u['status'] ?? 'active' ?>')"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete"
                                        onclick="openDeleteModal(<?= $u['id'] ?>, '<?= addslashes(htmlspecialchars($u['full_name'])) ?>')"
                                        title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#aaa;padding:30px;">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ══ EDIT USER MODAL ══ -->
    <div id="editModal" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);" onclick="closeEditModal()"></div>
        <div style="position:relative;background:#fff;max-width:500px;width:94%;z-index:1;">
            <div class="modal-header-custom">
                <h5><i class="fas fa-edit" style="margin-right:8px;color:#E50010;"></i>Edit User</h5>
                <button onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" style="padding:28px 28px 20px;" novalidate>
                <input type="hidden" id="editUserId" name="user_id">
                <div style="margin-bottom:18px;">
                    <div class="modal-label">Full Name <span style="color:#E50010;">*</span></div>
                    <input type="text" id="editName" name="full_name" class="modal-input" required
                           data-validation="required,alphabetic,min" data-min="3">
                    <small id="full_name_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div style="margin-bottom:18px;">
                    <div class="modal-label">Email <span style="color:#E50010;">*</span></div>
                    <input type="email" id="editEmail" name="email" class="modal-input" required
                           data-validation="required,email">
                    <small id="email_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">
                    <div>
                        <div class="modal-label">Role <span class="req">*</span></div>
                        <select id="editRole" name="role" class="modal-select" data-validation="required,select">
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                        <small id="role_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    <div>
                        <div class="modal-label">Status <span class="req">*</span></div>
                        <select id="editStatus" name="status" class="modal-select" data-validation="required,select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <small id="status_error" class="small text-danger" style="display:none;"></small>
                    </div>
                </div>
                <div style="margin-bottom:10px;">
                    <div class="modal-label">New Password <span style="color:#aaa;font-weight:400;text-transform:none;letter-spacing:0;">(leave blank to keep current)</span></div>
                    <input type="password" id="editPassword" name="new_password" class="modal-input" placeholder="Enter new password…"
                           data-validation="strongpassword">
                    <small id="new_password_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div id="editError" style="color:#E50010;font-size:12px;margin-top:10px;display:none;"></div>
                <div style="display:flex;gap:10px;margin-top:24px;justify-content:flex-end;">
                    <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-modal-save" id="editSaveBtn"><i class="fas fa-check"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ DELETE CONFIRM MODAL ══ -->
    <div id="deleteModal" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);" onclick="closeDeleteModal()"></div>
        <div style="position:relative;background:#fff;max-width:400px;width:94%;z-index:1;">
            <div class="modal-header-custom" style="background:#E50010;">
                <h5><i class="fas fa-trash" style="margin-right:8px;"></i>Delete User</h5>
                <button onclick="closeDeleteModal()">&times;</button>
            </div>
            <div style="padding:28px 28px 20px;text-align:center;">
                <i class="fas fa-exclamation-triangle" style="font-size:42px;color:#E50010;margin-bottom:14px;display:block;"></i>
                <p class="delete-confirm-text">Are you sure you want to delete<br><span class="delete-confirm-name" id="deleteUserName"></span>?</p>
                <p style="font-size:12px;color:#999;margin-bottom:20px;">This action cannot be undone.</p>
                <input type="hidden" id="deleteUserId">
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button class="btn-modal-save" id="deleteConfirmBtn" onclick="confirmDelete()">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>

    <script>
    // ── Toast ─────────────────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
        const t = document.getElementById('toastMsg');
        t.textContent = msg;
        t.className = 'toast-msg ' + type;
        t.style.display = 'block';
        setTimeout(() => { t.style.display = 'none'; }, 3500);
    }

    // ── Edit Modal ────────────────────────────────────────────────────────────
    function openEditModal(id, name, email, role, status) {
        document.getElementById('editUserId').value  = id;
        document.getElementById('editName').value    = name;
        document.getElementById('editEmail').value   = email;
        document.getElementById('editRole').value    = role.toLowerCase();
        document.getElementById('editStatus').value  = status.toLowerCase();
        document.getElementById('editPassword').value = '';
        document.getElementById('editError').style.display = 'none';
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Integrate with validation.js
        if (typeof validateForm === 'function') {
            if (!validateForm('#editForm')) {
                return false;
            }
        }

        const btn = document.getElementById('editSaveBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';
        btn.disabled = true;

        const fd = new FormData(this);
        fetch('user_action.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeEditModal();
                    showToast('User updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    document.getElementById('editError').textContent = data.message || 'Something went wrong.';
                    document.getElementById('editError').style.display = 'block';
                }
            })
            .catch(() => {
                document.getElementById('editError').textContent = 'Network error. Please try again.';
                document.getElementById('editError').style.display = 'block';
            })
            .finally(() => {
                btn.innerHTML = '<i class="fas fa-check"></i> Save Changes';
                btn.disabled = false;
            });
    });

    // ── Delete Modal ──────────────────────────────────────────────────────────
    function openDeleteModal(id, name) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserName').textContent = name;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    function confirmDelete() {
        const id  = document.getElementById('deleteUserId').value;
        const btn = document.getElementById('deleteConfirmBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';
        btn.disabled = true;

        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('user_id', id);

        fetch('user_action.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeDeleteModal();
                    showToast('User deleted.', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    alert(data.message || 'Failed to delete user.');
                }
            })
            .catch(() => alert('Network error. Please try again.'))
            .finally(() => {
                btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                btn.disabled = false;
            });
    }
    </script>
</body>

</html>