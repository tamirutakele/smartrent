<?php
session_start();

if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: admin-login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$totalProperties = 0;
$totalBookings = 0;
$totalTenants = 0;
$totalIncome = 0;
$pendingPayments = 0;

try {
    // Total properties
    $result = $conn->query(
        "SELECT COUNT(*) AS total FROM properties"
    );

    if ($result) {
        $totalProperties = (int) $result->fetch_assoc()['total'];
    }

    // Total bookings
    $result = $conn->query(
        "SELECT COUNT(*) AS total FROM bookings"
    );

    if ($result) {
        $totalBookings = (int) $result->fetch_assoc()['total'];
    }

    // Total tenants
    $result = $conn->query(
        "SELECT COUNT(*) AS total FROM userss WHERE role = 'tenant'"
    );

    if ($result) {
        $totalTenants = (int) $result->fetch_assoc()['total'];
    }

    // Total approved payment income
    $result = $conn->query(
        "SELECT COALESCE(SUM(amount), 0) AS total
         FROM payments
         WHERE status = 'Approved'"
    );

    if ($result) {
        $totalIncome = (float) $result->fetch_assoc()['total'];
    }

    // Pending payments
    $result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM payments
         WHERE status = 'Pending'"
    );

    if ($result) {
        $pendingPayments = (int) $result->fetch_assoc()['total'];
    }

} catch (Throwable $e) {
    error_log("SmartRent dashboard error: " . $e->getMessage());
}

$adminName = htmlspecialchars(
    $_SESSION['user'],
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SmartRent Admin Dashboard</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .header {
            background: #1e293b;
            color: white;
            padding: 22px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
        }

        .header h1 {
            font-size: 25px;
        }

        .logout {
            background: #ef4444;
            color: white;
            text-decoration: none;
            padding: 11px 20px;
            border-radius: 7px;
            font-weight: bold;
        }

        .logout:hover {
            background: #dc2626;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 35px 25px;
        }

        .welcome {
            margin-bottom: 28px;
        }

        .welcome h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .welcome p {
            color: #64748b;
            line-height: 1.6;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .stat-card .icon {
            font-size: 30px;
            margin-bottom: 12px;
        }

        .stat-card h3 {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 27px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .income {
            border-top: 4px solid #16a34a;
        }

        .properties {
            border-top: 4px solid #2563eb;
        }

        .bookings {
            border-top: 4px solid #ea580c;
        }

        .tenants {
            border-top: 4px solid #9333ea;
        }

        .pending {
            border-top: 4px solid #eab308;
        }

        .section-title {
            font-size: 22px;
            margin-bottom: 18px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.07);
            text-decoration: none;
            display: block;
            transition: 0.25s;
            border: 1px solid #e2e8f0;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .card h2 {
            margin-bottom: 12px;
            color: #1e293b;
            font-size: 21px;
        }

        .card p {
            color: #64748b;
            line-height: 1.7;
        }

        .card-icon {
            font-size: 32px;
            margin-bottom: 14px;
        }

        .add-property {
            border-left: 6px solid #16a34a;
        }

        .manage-properties {
            border-left: 6px solid #2563eb;
        }

        .users {
            border-left: 6px solid #9333ea;
        }

        .manage-bookings {
            border-left: 6px solid #ea580c;
        }

        .payments {
            border-left: 6px solid #0891b2;
        }

        .reports {
            border-left: 6px solid #db2777;
        }

        /* New Property Image Upload card */
        .property-images {
            border-left: 6px solid #7c3aed;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
            padding: 20px;
        }

        @media (max-width: 850px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .stats,
            .cards {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 21px;
            }

            .container {
                padding: 25px 16px;
            }

            .welcome h2 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<header class="header">
    <h1>🏠 SmartRent Admin Dashboard</h1>

    <a href="../logout.php" class="logout">Logout</a>
</header>

<main class="container">

    <section class="welcome">
        <h2>Welcome, <?= $adminName; ?> 👋</h2>

        <p>
            Monitor your rental properties, tenants, bookings,
            and payments from one place.
        </p>
    </section>

    <!-- Dashboard Statistics -->
    <section class="stats">

        <div class="stat-card properties">
            <div class="icon">🏠</div>
            <h3>Total Properties</h3>
            <div class="number">
                <?= number_format($totalProperties); ?>
            </div>
        </div>

        <div class="stat-card bookings">
            <div class="icon">📋</div>
            <h3>Total Bookings</h3>
            <div class="number">
                <?= number_format($totalBookings); ?>
            </div>
        </div>

        <div class="stat-card tenants">
            <div class="icon">👥</div>
            <h3>Total Tenants</h3>
            <div class="number">
                <?= number_format($totalTenants); ?>
            </div>
        </div>

        <div class="stat-card income">
            <div class="icon">💰</div>
            <h3>Approved Payment Income</h3>
            <div class="number">
                <?= number_format($totalIncome, 2); ?>
            </div>
        </div>

        <div class="stat-card pending">
            <div class="icon">⏳</div>
            <h3>Pending Payments</h3>
            <div class="number">
                <?= number_format($pendingPayments); ?>
            </div>
        </div>

    </section>

    <h2 class="section-title">Management Menu</h2>

    <section class="cards">

        <!-- Add Property -->
        <a href="add-property.php" class="card add-property">
            <div class="card-icon">➕</div>
            <h2>Add Property</h2>
            <p>
                Add new rental houses and apartments to SmartRent.
            </p>
        </a>

        <!-- Manage Properties -->
        <a href="properties.php" class="card manage-properties">
            <div class="card-icon">🏠</div>
            <h2>Properties</h2>
            <p>
                View and manage available rental properties.
            </p>
        </a>

        <!-- Manage Users -->
        <a href="users.php" class="card users">
            <div class="card-icon">👥</div>
            <h2>Users</h2>
            <p>
                View and manage registered tenants and users.
            </p>
        </a>

        <!-- Manage Bookings -->
        <a href="bookings.php" class="card manage-bookings">
            <div class="card-icon">📋</div>
            <h2>Bookings</h2>
            <p>
                Review booking requests and manage their status.
            </p>
        </a>

        <!-- Manage Payments -->
        <a href="payments.php" class="card payments">
            <div class="card-icon">💳</div>
            <h2>Payments</h2>
            <p>
                Review tenant payments and approve or reject pending payments.
            </p>
        </a>

        <!-- Reports -->
        <a href="reports.php" class="card reports">
            <div class="card-icon">📊</div>
            <h2>Reports</h2>
            <p>
                View rental management statistics and payment reports.
            </p>
        </a>

        <!-- NEW: Property Image Upload -->
        <a href="property-images.php" class="card property-images">
            <div class="card-icon">🖼️</div>
            <h2>Property Image Upload</h2>
            <p>
                Upload and manage photos for rental houses and apartments.
            </p>
        </a>

    </section>

</main>

<footer class="footer">
    SmartRent Property Management System
</footer>

</body>
</html>