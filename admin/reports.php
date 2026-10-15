```php
<?php
session_start();

require_once "../config/database.php";

// Allow admin users only
if (
    !isset($_SESSION["user"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

// Helper function for safe output
function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Get total properties
$propertiesResult = $conn->query("SELECT COUNT(*) AS total FROM properties");
$totalProperties = $propertiesResult
    ? (int)$propertiesResult->fetch_assoc()["total"]
    : 0;

// Get total bookings
$bookingsResult = $conn->query("SELECT COUNT(*) AS total FROM bookings");
$totalBookings = $bookingsResult
    ? (int)$bookingsResult->fetch_assoc()["total"]
    : 0;

// Get approved bookings
$approvedBookingsResult = $conn->query(
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'Approved'"
);
$approvedBookings = $approvedBookingsResult
    ? (int)$approvedBookingsResult->fetch_assoc()["total"]
    : 0;

// Get total tenants
$tenantsResult = $conn->query(
    "SELECT COUNT(*) AS total FROM userss WHERE role = 'tenant'"
);
$totalTenants = $tenantsResult
    ? (int)$tenantsResult->fetch_assoc()["total"]
    : 0;

// Get payment statistics
$paymentStatsResult = $conn->query("
    SELECT
        COUNT(*) AS total_payments,
        COALESCE(SUM(CASE WHEN status = 'Approved' THEN amount ELSE 0 END), 0) AS confirmed_income,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_payments,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_payments,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_payments
    FROM payments
");

$paymentStats = $paymentStatsResult
    ? $paymentStatsResult->fetch_assoc()
    : [
        "total_payments" => 0,
        "confirmed_income" => 0,
        "pending_payments" => 0,
        "approved_payments" => 0,
        "rejected_payments" => 0
    ];

// Get recent payments
$recentPayments = $conn->query("
    SELECT
        p.id,
        p.amount,
        p.payment_method,
        p.status,
        p.created_at,
        b.property_name,
        u.name AS tenant_name
    FROM payments p
    LEFT JOIN bookings b ON p.booking_id = b.id
    LEFT JOIN userss u ON p.user_id = u.id
    ORDER BY p.id DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartRent | Reports</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        header {
            background: #0f172a;
            color: white;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h2 {
            margin: 0;
            color: #38bdf8;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .container {
            width: 94%;
            max-width: 1400px;
            margin: 30px auto;
        }

        .intro {
            margin-bottom: 25px;
        }

        .intro p {
            color: #64748b;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 14px;
            box-shadow: 0 5px 20px #0000000d;
        }

        .card h3 {
            margin: 0 0 12px;
            font-size: 15px;
            color: #64748b;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .income {
            color: #15803d;
        }

        .section-title {
            margin-top: 35px;
            margin-bottom: 18px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #e2e8f0;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .Pending {
            background: #fef3c7;
            color: #92400e;
        }

        .Approved {
            background: #dcfce7;
            color: #166534;
        }

        .Rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #64748b;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #0369a1;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            header {
                padding: 18px 5%;
            }

            nav a {
                display: inline-block;
                margin: 8px 10px 0 0;
            }

            .card {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>SmartRent Admin</h2>

    <nav>
        <a href="index.php">Dashboard</a>
        <a href="properties.php">Properties</a>
        <a href="payments.php">Payments</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <a class="back" href="index.php">← Back to Dashboard</a>

    <div class="intro">
        <h1>📊 Reports & Analytics</h1>
        <p>Overview of properties, bookings, tenants, and payments.</p>
    </div>

    <div class="cards">

        <div class="card">
            <h3>🏠 Total Properties</h3>
            <div class="number">
                <?= number_format($totalProperties) ?>
            </div>
        </div>

        <div class="card">
            <h3>🔑 Total Bookings</h3>
            <div class="number">
                <?= number_format($totalBookings) ?>
            </div>
        </div>

        <div class="card">
            <h3>✅ Approved Bookings</h3>
            <div class="number">
                <?= number_format($approvedBookings) ?>
            </div>
        </div>

        <div class="card">
            <h3>👥 Total Tenants</h3>
            <div class="number">
                <?= number_format($totalTenants) ?>
            </div>
        </div>

        <div class="card">
            <h3>💰 Confirmed Payment Income</h3>
            <div class="number income">
                <?= number_format((float)$paymentStats["confirmed_income"], 2) ?> ETB
            </div>
        </div>

        <div class="card">
            <h3>⏳ Pending Payments</h3>
            <div class="number">
                <?= number_format((int)$paymentStats["pending_payments"]) ?>
            </div>
        </div>

        <div class="card">
            <h3>✔ Approved Payments</h3>
            <div class="number">
                <?= number_format((int)$paymentStats["approved_payments"]) ?>
            </div>
        </div>

        <div class="card">
            <h3>❌ Rejected Payments</h3>
            <div class="number">
                <?= number_format((int)$paymentStats["rejected_payments"]) ?>
            </div>
        </div>

    </div>

    <h2 class="section-title">🧾 Recent Payments</h2>

    <div class="card table-wrap">

        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Tenant</th>
                    <th>Property</th>
                    <th>Amount (ETB)</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($recentPayments && $recentPayments->num_rows > 0): ?>

                <?php while ($payment = $recentPayments->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$payment["id"] ?></td>
                        <td><?= e($payment["tenant_name"] ?? "Unknown") ?></td>
                        <td><?= e($payment["property_name"] ?? "Unknown") ?></td>
                        <td><?= number_format((float)$payment["amount"], 2) ?></td>
                        <td><?= e($payment["payment_method"]) ?></td>
                        <td><?= e($payment["created_at"]) ?></td>
                        <td>
                            <span class="status <?= e($payment["status"]) ?>">
                                <?= e($payment["status"]) ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="7" class="empty">
                        No payment records found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

    </div>

</div>

</body>
</html>
```
