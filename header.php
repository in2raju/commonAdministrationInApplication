<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Include DB connection
if (!isset($pdo)) {
    require 'db.php';
}

// Logged-in user info
$loginUserTypeId = $_SESSION['user']['user_type_id'] ?? '';
$loginUserId     = $_SESSION['user']['user_id'] ?? '';
$brCode          = $_SESSION['user']['br_code'] ?? '';

// Fetch menus that the user_type_id has permission to view
$sqlMenus = "SELECT m.MENU_ID, m.MENU_NAME, m.MENU_LINK, m.PARENT_ID
             FROM menu_info m
             INNER JOIN user_menu_view_permission ump 
                 ON m.MENU_ID = ump.MENU_ID
             WHERE ump.USER_TYPE_ID = :user_type_id
               AND ump.CAN_VIEW = true
             ORDER BY m.MENU_ID";

$stmtMenus = $pdo->prepare($sqlMenus);
$stmtMenus->execute([
    'user_type_id' => $loginUserTypeId
]);

$menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC);

// Build menu tree: parent_id => children
$menuTree = [];
foreach ($menus as $menu) {
    $parentId = $menu['PARENT_ID'] ?: 0; // top-level menus
    $menuTree[$parentId][] = $menu;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock3600</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { display: flex; flex-direction: column; }

        footer { background-color: #212529; color: white; }

        /* Show dropdown on hover */
        .navbar-nav .dropdown:hover > .dropdown-menu {
            display: block;
        }

        .dropdown-menu {
            margin-top: 0;
            transition: all 0.3s ease;
        }

        /* Optional: caret rotation on hover */
        .navbar-nav .dropdown:hover > .nav-link.dropdown-toggle::after {
            transform: rotate(-180deg);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="home.php">Stock3600</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Dynamic menu items -->
            <ul class="navbar-nav me-auto">
                <?php
                if (isset($menuTree[0])) {
                    foreach ($menuTree[0] as $menu) {
                        $menuId = $menu['MENU_ID'];
                        if (isset($menuTree[$menuId])) {
                            // Dropdown menu
                            echo '<li class="nav-item dropdown">';
                            echo '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">'
                                . htmlspecialchars($menu['MENU_NAME']) . '</a>';
                            echo '<ul class="dropdown-menu">';
                            foreach ($menuTree[$menuId] as $sub) {
                                echo '<li><a class="dropdown-item" href="' . htmlspecialchars($sub['MENU_LINK']) . '">'
                                    . htmlspecialchars($sub['MENU_NAME']) . '</a></li>';
                            }
                            echo '</ul></li>';
                        } else {
                            // Single menu
                            echo '<li class="nav-item">';
                            echo '<a class="nav-link" href="' . htmlspecialchars($menu['MENU_LINK']) . '">'
                                . htmlspecialchars($menu['MENU_NAME']) . '</a></li>';
                        }
                    }
                }
                ?>
            </ul>

            <!-- User dropdown -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($loginUserId) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
