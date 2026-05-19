<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user       = $_SESSION['user'];
$userId     = $user['user_id'];
$userType   = $user['user_type_id'];
$brCode     = $user['br_code'];
$orgCode    = $user['org_code'];
$canInsert  = $user['can_insert']  ?? 1;
$canEdit    = $user['can_edit']    ?? 1;
$canDelete  = $user['can_delete']  ?? 1;
$canApprove = $user['can_approve'] ?? 0;

$editEmployee = null;
$formData     = [];

/* =====================
   POST: INSERT / UPDATE
   ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empId = trim($_POST['emp_id'] ?? ''); // Primary Key auto_increment ID
    
    $formData = [
        'first_name'        => trim($_POST['first_name'] ?? ''),
        'last_name'         => trim($_POST['last_name'] ?? ''),
        'father_name'       => trim($_POST['father_name'] ?? ''),
        'mother_name'       => trim($_POST['mother_name'] ?? ''),
        'NID'               => trim($_POST['NID'] ?? ''),
        'dob'               => !empty($_POST['dob']) ? $_POST['dob'] : null,
        'gender'            => $_POST['gender'] ?? null,
        'present_address'   => trim($_POST['present_address'] ?? ''),
        'permanent_address' => trim($_POST['permanent_address'] ?? ''),
        'email'             => trim($_POST['email'] ?? ''),
        'phone_number'      => trim($_POST['phone_number'] ?? ''),
        'hire_date'         => $_POST['hire_date'] ?? date('Y-m-d'),
        'job_title'         => trim($_POST['job_title'] ?? ''),
        'department_id'     => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
    ];

    $phone = $formData['phone_number'];
    $bdPhoneRegex = '/^(?:\+8801|01)[3-9]\d{8}$/';

    if (!empty($phone) && !preg_match($bdPhoneRegex, $phone)) {
        $_SESSION['flash'] = [
            'type' => 'danger', 
            'msg'  => 'Invalid phone format! Use 01XXXXXXXXX or +8801XXXXXXXXX.'
        ];
    } else {
        if (str_starts_with($phone, '+880')) {
            $phone = '0' . substr($phone, 4);
        }
        $formData['phone_number'] = $phone;

        // Check unique phone number context across non-deleted profiles in this branch
        $stmtCheck = $pdo->prepare("
            SELECT emp_id FROM employees 
            WHERE phone_number = ? AND BR_CODE = ? AND IFNULL(IS_DELETED, 'N') != 'Y'
        ");
        $stmtCheck->execute([$phone, $brCode]);
        $existing = $stmtCheck->fetch();

        if ($existing && (!$empId || $existing['emp_id'] != $empId)) {
            $_SESSION['flash'] = [
                'type' => 'danger', 
                'msg'  => 'Phone number already registered to another active profile in this branch.'
            ];
        } else {
            if ($empId) {
                if ($canEdit) {
                    $stmt = $pdo->prepare("
                        UPDATE employees
                        SET first_name        = :first_name,
                            last_name         = :last_name,
                            father_name       = :father_name,
                            mother_name       = :mother_name,
                            NID               = :NID,
                            dob               = :dob,
                            gender            = :gender,
                            present_address   = :present_address,
                            permanent_address = :permanent_address,
                            email             = :email,
                            phone_number      = :phone_number,
                            hire_date         = :hire_date,
                            job_title         = :job_title,
                            department_id     = :department_id,
                            EDIT_USER         = :EDIT_USER,
                            EDIT_DATE         = NOW()
                        WHERE emp_id = :emp_id
                          AND BR_CODE = :BR_CODE
                          AND IFNULL(AUTHORIZED_STATUS, 'N') != 'Y'
                    ");
                    
                    $params = $formData;
                    $params['EDIT_USER'] = $userId;
                    $params['emp_id']    = $empId;
                    $params['BR_CODE']   = $brCode;
                    
                    $stmt->execute($params);
                    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Employee profile updated successfully.'];
                    $formData = [];
                }
            } else {
                if ($canInsert) {
                    // Generate unique human-readable employee uniform registration sequence (emp_no)
                    $empNo = "EMP-" . $brCode . "-" . date('ymdHis');
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO employees
                            (emp_no, first_name, last_name, father_name, mother_name, NID, dob, gender, 
                             present_address, permanent_address, email, phone_number, hire_date, job_title, 
                             department_id, BR_CODE, ORG_CODE, AUTHORIZED_STATUS, ENTRY_USER, ENTRY_DATE, IS_DELETED)
                        VALUES 
                            (:emp_no, :first_name, :last_name, :father_name, :mother_name, :NID, :dob, :gender, 
                             :present_address, :permanent_address, :email, :phone_number, :hire_date, :job_title, 
                             :department_id, :BR_CODE, :ORG_CODE, 'N', :ENTRY_USER, NOW(), 'N')
                    ");
                    
                    $params = $formData;
                    $params['emp_no']     = $empNo;
                    $params['BR_CODE']    = $brCode;
                    $params['ORG_CODE']   = $orgCode;
                    $params['ENTRY_USER'] = $userId;
                    
                    $stmt->execute($params);
                    $_SESSION['flash'] = ['type' => 'success', 'msg' => "Employee profile saved successfully. System Key ID: {$empNo}"];
                    $formData = [];
                }
            }
            header("Location: add_employee.php");
            exit();
        }
    }
}

/* =====================
   GET: EDIT LOAD
   ===================== */
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("
        SELECT * FROM employees
        WHERE emp_id = :id AND BR_CODE = :br
          AND IFNULL(IS_DELETED, 'N') != 'Y'
          AND IFNULL(AUTHORIZED_STATUS, 'N') != 'Y'
    ");
    $stmt->execute(['id' => $_GET['edit'], 'br' => $brCode]);
    $editEmployee = $stmt->fetch();
    if ($editEmployee) {
        $formData = $editEmployee;
    }
}

/* =====================
   GET: LOGICAL DELETE
   ===================== */
if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("
        UPDATE employees 
        SET IS_DELETED  = 'Y', 
            DELETE_USER = :user, 
            DELETE_DATE = NOW() 
        WHERE emp_id = :id 
          AND BR_CODE = :br
          AND IFNULL(AUTHORIZED_STATUS, 'N') != 'Y'
    ");
    $stmt->execute(['user' => $userId, 'id' => $_GET['delete'], 'br' => $brCode]);
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Employee profile dropped structural tracking context.'];
    header("Location: add_employee.php");
    exit();
}

/* =====================
   GET: AUTHORIZE
   ===================== */
if (isset($_GET['authorize']) && $canApprove) {
    $stmt = $pdo->prepare("
        UPDATE employees
        SET AUTHORIZED_STATUS = 'Y',
            AUTHORIZED_USER   = :auth_user,
            AUTHORIZED_DATE   = NOW()
        WHERE emp_id = :id AND BR_CODE = :br AND IFNULL(IS_DELETED, 'N') != 'Y'
    ");
    $stmt->execute(['auth_user' => $userId, 'id' => $_GET['authorize'], 'br' => $brCode]);
    $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Employee database record state set to authorized structural status.'];
    header("Location: add_employee.php");
    exit();
}

/* =====================
   FLASH CONFIGS
   ===================== */
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

/* =====================
   FETCH DEPARTMENTS FOR DROPDOWN (Matches employee_departments table layout)
   ===================== */
$deptStmt = $pdo->prepare("
    SELECT dept_id, dept_name 
    FROM employee_departments 
    WHERE BR_CODE = :br_code AND IFNULL(IS_DELETED, 'N') != 'Y'
    ORDER BY dept_name ASC
");
$deptStmt->execute(['br_code' => $brCode]);
$departmentsList = $deptStmt->fetchAll();

/* =====================
   QUERIES & SEARCH PARSING
   ===================== */
$searchQuery  = trim($_GET['search_query']  ?? '');
$filterStatus = trim($_GET['filter_status'] ?? '');

$sql = "SELECT e.*, d.dept_name 
        FROM employees e
        LEFT JOIN employee_departments d ON e.department_id = d.dept_id
        WHERE IFNULL(e.IS_DELETED, 'N') != 'Y'";

if ($userType != 1) {
    $sql .= " AND e.BR_CODE = :BR_CODE AND e.ORG_CODE = :ORG_CODE";
    $params = ['BR_CODE' => $brCode, 'ORG_CODE' => $orgCode];
} else {
    $sql .= " AND e.BR_CODE = :BR_CODE";
    $params = ['BR_CODE' => $brCode];
}

if ($searchQuery !== '') {
    $sql .= " AND (e.emp_no LIKE :q1 OR e.first_name LIKE :q2 OR e.last_name LIKE :q3 OR e.phone_number LIKE :q4 OR e.NID LIKE :q5 OR e.job_title LIKE :q6 OR d.dept_name LIKE :q7)";
    $params['q1'] = '%' . $searchQuery . '%';
    $params['q2'] = '%' . $searchQuery . '%';
    $params['q3'] = '%' . $searchQuery . '%';
    $params['q4'] = '%' . $searchQuery . '%';
    $params['q5'] = '%' . $searchQuery . '%';
    $params['q6'] = '%' . $searchQuery . '%';
    $params['q7'] = '%' . $searchQuery . '%';
}

if ($filterStatus === 'authorized') {
    $sql .= " AND e.AUTHORIZED_STATUS = 'Y'";
} elseif ($filterStatus === 'pending') {
    $sql .= " AND IFNULL(e.AUTHORIZED_STATUS, 'N') = 'N'";
}

$sql .= " ORDER BY e.ENTRY_DATE DESC, e.emp_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

/* =====================
   METRIC COUNT CALCULATIONS
   ===================== */
$stmtAll = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE IFNULL(IS_DELETED, 'N') != 'Y' AND BR_CODE = ?");
$stmtAll->execute([$brCode]);
$totalCount = (int)$stmtAll->fetchColumn();

$stmtAuth = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE IFNULL(IS_DELETED, 'N') != 'Y' AND BR_CODE = ? AND AUTHORIZED_STATUS = 'Y'");
$stmtAuth->execute([$brCode]);
$authCount    = (int)$stmtAuth->fetchColumn();
$pendingCount = $totalCount - $authCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Core Configuration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="entryFormCss.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-wrapper">

    <!-- Header Block Component -->
    <div class="page-header">
        <div class="page-title">
            <div class="page-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <polyline points="17 11 19 13 23 9"/>
                </svg>
            </div>
            <div>
                <h1>Employee Configurations</h1>
                <p>Register, map structural organizational profiles, configure payroll entities and manage system human assets</p>
            </div>
        </div>
    </div>

    <!-- Alert Messaging Container Matrix -->
    <?php if ($flash): ?>
        <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>" id="flashMsg">
            <?php if ($flash['type'] === 'success'): ?>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?php elseif ($flash['type'] === 'danger'): ?>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            <?php else: ?>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?php endif; ?>
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Structural Counter Metrics row -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $totalCount ?></div>
                <div class="stat-label">Total Staff Assets</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $authCount ?></div>
                <div class="stat-label">Authorized Rows</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $pendingCount ?></div>
                <div class="stat-label">Pending Verification</div>
            </div>
        </div>
    </div>

    <!-- Layout Data Entry Matrix Card Component -->
    <?php if ($canInsert || $editEmployee): ?>
    <div class="card<?= $editEmployee ? ' is-editing' : '' ?>" id="entryCard">
        <div class="card-header">
            <div class="card-header-icon">
                <?php if ($editEmployee): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                <?php else: ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>
                <?php endif; ?>
            </div>
            <div>
                <div class="card-title"><?= $editEmployee ? 'Altering Row Index — ' . htmlspecialchars($formData['emp_no'] ?? '') : 'Register New Organization Employee' ?></div>
                <div class="card-subtitle"><?= $editEmployee ? 'Modifying fields within an unauthorized transactional buffer block.' : 'Complete field schemas below to insert record entry parameters.' ?></div>
            </div>
        </div>
        <div class="card-body">
            <form method="post" id="employeeForm">
                <input type="hidden" name="emp_id" value="<?= htmlspecialchars($formData['emp_id'] ?? '') ?>">
                
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">First Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="first_name" class="form-control" required placeholder="Given Name"
                               value="<?= htmlspecialchars($formData['first_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Last Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="last_name" class="form-control" required placeholder="Surname"
                               value="<?= htmlspecialchars($formData['last_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">National ID (NID) <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="NID" class="form-control" required placeholder="Unique ID Number"
                               value="<?= htmlspecialchars($formData['NID'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-3" style="margin-top: 15px;">
                    <div class="form-group">
                        <label class="form-label">Father's Name</label>
                        <input type="text" name="father_name" class="form-control" placeholder="Father's Legal Name"
                               value="<?= htmlspecialchars($formData['father_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" name="mother_name" class="form-control" placeholder="Mother's Legal Name"
                               value="<?= htmlspecialchars($formData['mother_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Primary Contact Phone</label>
                        <input type="text" name="phone_number" class="form-control" placeholder="e.g. 01XXXXXXXXX" maxlength="20"
                               value="<?= htmlspecialchars($formData['phone_number'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-3" style="margin-top: 15px;">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="corporate@domain.com"
                               value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control"
                               value="<?= htmlspecialchars($formData['dob'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender Selection Context</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="M" <?= ($formData['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Male</option>
                            <option value="F" <?= ($formData['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Female</option>
                            <option value="O" <?= ($formData['gender'] ?? '') === 'O' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid-3" style="margin-top: 15px;">
                    <div class="form-group">
                        <label class="form-label">Job Title / Designation</label>
                        <input type="text" name="job_title" class="form-control" placeholder="e.g. Lead Analyst"
                               value="<?= htmlspecialchars($formData['job_title'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department Assignment <span style="color:var(--danger)">*</span></label>
                        <select name="department_id" class="form-control" required>
                            <option value="">-- Select Assigned Department --</option>
                            <?php foreach ($departmentsList as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['dept_id']) ?>" 
                                    <?= (trim($formData['department_id'] ?? '') == $dept['dept_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['dept_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date of Hire <span style="color:var(--danger)">*</span></label>
                        <input type="date" name="hire_date" class="form-control" required
                               value="<?= htmlspecialchars($formData['hire_date'] ?? date('Y-m-d')) ?>">
                    </div>
                </div>

                <div class="grid-2" style="margin-top: 15px;">
                    <div class="form-group">
                        <label class="form-label">Present Residential Address</label>
                        <textarea name="present_address" class="form-control" rows="2" placeholder="Current Mailing Address"><?= htmlspecialchars($formData['present_address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Permanent Residential Address</label>
                        <textarea name="permanent_address" class="form-control" rows="2" placeholder="Permanent Document Address"><?= htmlspecialchars($formData['permanent_address'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <?= $editEmployee ? 'Update Profile' : 'Commit Record' ?>
                    </button>
                    <?php if ($editEmployee): ?>
                        <a href="add_employee.php" class="btn btn-ghost">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Cancel Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Control Toolbar Blocks -->
    <div class="toolbar">

        <!-- Client Micro Search Processing Wrapper -->
        <div class="gsearch-wrap">
            <svg class="s-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="globalSearch" class="form-control"
                   placeholder="Instant filtration: Employee numbers, names, phone keys..."
                   autocomplete="off">
            <span class="match-chip" id="matchChip"></span>
            <button type="button" class="clear-btn" id="clearGS" title="Clear Index Query">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="toolbar-sep"></div>

        <!-- Filter Status Scope Selection Tabs -->
        <div class="filter-tabs">
            <a href="?filter_status=&search_query=<?= urlencode($searchQuery) ?>"
               class="ftab <?= $filterStatus === '' ? 'active' : '' ?>">
                All Profiles <span class="pill"><?= $totalCount ?></span>
            </a>
            <a href="?filter_status=authorized&search_query=<?= urlencode($searchQuery) ?>"
               class="ftab <?= $filterStatus === 'authorized' ? 'active' : '' ?>">
                Authorized <span class="pill"><?= $authCount ?></span>
            </a>
            <a href="?filter_status=pending&search_query=<?= urlencode($searchQuery) ?>"
               class="ftab <?= $filterStatus === 'pending' ? 'active' : '' ?>">
                Pending <span class="pill"><?= $pendingCount ?></span>
            </a>
        </div>

        <div class="toolbar-sep"></div>

        <!-- Server Side Query Form Module -->
        <form method="get" class="server-search">
            <input type="hidden" name="filter_status" value="<?= htmlspecialchars($filterStatus) ?>">
            <input type="text" name="search_query" class="form-control"
                   placeholder="Query DB entries..."
                   value="<?= htmlspecialchars($searchQuery, ENT_QUOTES) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="add_employee.php" class="btn btn-ghost btn-sm">Reset</a>
        </form>
    </div>

    <!-- Structured Corporate Master Table Element -->
    <div class="tbl-wrap">
        <div class="tbl-responsive">
            <table id="employeeTable">
                <thead>
                    <tr>
                        <th style="width:160px;">Employee Code</th>
                        <th>Core Profile / Identity Matrix Data</th>
                        <th style="width:140px;">Primary Phone</th>
                        <th>Job Specifications Mapping</th>
                        <th style="width:130px; text-align:center;">State Flag</th>
                        <th style="width:230px; text-align:right; padding-right:20px;">Execution Controls</th>
                    </tr>
                </thead>
                <tbody id="tableBody">

                <?php foreach ($employees as $row): 
                    $isAuth = (($row['AUTHORIZED_STATUS'] ?? 'N') === 'Y');
                    $statusValue = $isAuth ? 'authorized' : 'pending';

                    // Consolidate full row string vectors for optimized matching
                    $searchBlob = strtolower(implode(' ', [
                        $row['emp_no'],
                        $row['first_name'],
                        $row['last_name'],
                        $row['phone_number'] ?? '',
                        $row['NID'] ?? '',
                        $row['email'] ?? '',
                        $row['job_title'] ?? '',
                        $row['dept_name'] ?? '',
                        $statusValue
                    ]));

                    $highlightText = function(string $text) use ($searchQuery): string {
                        $safeString = htmlspecialchars($text);
                        if ($searchQuery === '') return $safeString;
                        return preg_replace(
                            '/' . preg_quote(htmlspecialchars($searchQuery), '/') . '/i',
                            '<mark>$0</mark>',
                            $safeString
                        );
                    };
                ?>
                    <tr data-search="<?= htmlspecialchars($searchBlob) ?>">
                        <td>
                            <span class="mono" data-col><?= $highlightText($row['emp_no']) ?></span>
                        </td>
                        <td>
                            <div class="cell-primary">
                                <span data-col><?= $highlightText($row['first_name']) ?></span> 
                                <span data-col><?= $highlightText($row['last_name']) ?></span>
                            </div>
                            <div class="cell-meta" style="font-size: 11px; margin-top: 2px;">
                                NID: <span data-col><?= $highlightText($row['NID']) ?></span> &middot; 
                                <?php if($row['email']): ?> Em: <span><?= htmlspecialchars($row['email']) ?></span> &middot; <?php endif; ?>
                                Dob: <span><?= htmlspecialchars($row['dob'] ?? '—') ?></span> (<?= htmlspecialchars($row['gender'] ?? '—') ?>)
                            </div>
                            <div class="cell-meta" style="margin-top: 4px; opacity: 0.85;">
                                <?php if ($isAuth): ?>
                                    Validated by <strong><?= htmlspecialchars($row['AUTHORIZED_USER'] ?? '—') ?></strong> on <?= !empty($row['AUTHORIZED_DATE']) ? date('d-M-Y', strtotime($row['AUTHORIZED_DATE'])) : '—' ?>
                                <?php else: ?>
                                    System Origin: Entered by <?= htmlspecialchars($row['ENTRY_USER'] ?? '—') ?> on <?= !empty($row['ENTRY_DATE']) ? date('d-M-Y', strtotime($row['ENTRY_DATE'])) : '—' ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="mono" data-col><?= $highlightText($row['phone_number'] ?? '—') ?></span>
                        </td>
                        <td>
                            <div style="font-size: 12px;">
                                Dept: <strong data-col><?= htmlspecialchars($row['dept_name'] ?? 'Unassigned') ?></strong>
                            </div>
                            <div style="margin-top: 2px; font-size: 12px; color: var(--text-muted)">
                                Title: <span data-col><?= htmlspecialchars($row['job_title'] ?: '—') ?></span>
                            </div>
                            <div style="margin-top:4px; font-size:11px; color: var(--accent);">
                                Hired: <?= !empty($row['hire_date']) ? date('d-M-Y', strtotime($row['hire_date'])) : '—' ?>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <?php if ($isAuth): ?>
                                <span class="badge badge-success">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    Authorized
                                </span>
                            <?php else: ?>
                                <span class="badge badge-warning">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-group">
                                <?php if ($isAuth): ?>
                                    <span style="font-size:11px; color:var(--text-muted); font-style:italic; padding-right:10px;">Context Finalized</span>
                                <?php else: ?>
                                    <?php if ($canApprove): ?>
                                        <a href="?authorize=<?= urlencode($row['emp_id']) ?>&filter_status=<?= urlencode($filterStatus) ?>&search_query=<?= urlencode($searchQuery) ?>"
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Authorize asset setup metrics for this employee?')">
                                             Authorize
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canEdit): ?>
                                        <a href="?edit=<?= urlencode($row['emp_id']) ?>&filter_status=<?= urlencode($filterStatus) ?>&search_query=<?= urlencode($searchQuery) ?>"
                                           class="btn btn-sm btn-warning btn-ico" title="Edit row parameters">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                        <a href="?delete=<?= urlencode($row['emp_id']) ?>&filter_status=<?= urlencode($filterStatus) ?>&search_query=<?= urlencode($searchQuery) ?>"
                                           class="btn btn-sm btn-danger btn-ico" title="Drop structural tracking"
                                           onclick="return confirm('Flag employee profile row index status as deleted?')">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($employees)): ?>
                    <tr id="emptyRow">
                        <td colspan="6">
                            <div class="empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                <p>No employee structures identified for current criteria profile contexts.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr id="noResults" style="display:none;">
                    <td colspan="6">
                        <div class="empty-state">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <p>No active matching arrays found. <a href="add_employee.php" style="color:var(--accent); font-weight:600;">Flush Query Filter</a></p>
                        </div>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Realtime Client-Side Global Filtration Runtime -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const globalSearch = document.getElementById('globalSearch');
    const tableBody = document.getElementById('tableBody');
    const rows = tableBody.querySelectorAll('tr:not(#emptyRow):not(#noResults)');
    const noResultsRow = document.getElementById('noResults');
    const matchChip = document.getElementById('matchChip');
    const clearGS = document.getElementById('clearGS');

    const flashAlert = document.getElementById('flashMsg');
    if(flashAlert) {
        setTimeout(() => {
            flashAlert.style.opacity = '0';
            setTimeout(() => flashAlert.remove(), 400);
        }, 4000);
    }

    function liveFilter() {
        const query = globalSearch.value.trim().toLowerCase();
        let matches = 0;

        if (query === '') {
            rows.forEach(r => r.style.display = '');
            noResultsRow.style.display = 'none';
            matchChip.style.display = 'none';
            clearGS.style.display = 'none';
            return;
        }

        rows.forEach(row => {
            const content = row.getAttribute('data-search') || '';
            if (content.includes(query)) {
                row.style.display = '';
                matches++;
            } else {
                row.style.display = 'none';
            }
        });

        matchChip.textContent = `${matches} match${matches === 1 ? '' : 'es'}`;
        matchChip.style.display = 'inline-block';
        clearGS.style.display = 'flex';

        if (matches === 0) {
            noResultsRow.style.display = 'table-row';
        } else {
            noResultsRow.style.display = 'none';
        }
    }

    globalSearch.addEventListener('input', liveFilter);
    clearGS.addEventListener('click', () => {
        globalSearch.value = '';
        liveFilter();
        globalSearch.focus();
    });
});
</script>
</body>
</html>