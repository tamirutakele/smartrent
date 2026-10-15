```php
<?php
session_start();

require_once "../config/database.php";

// Allow only logged-in tenants
if (
    !isset($_SESSION["user"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "tenant"
) {
    header("Location: ../login.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);

if ($userId <= 0) {
    exit("User ID is missing. Please log out and log in again.");
}

// Fetch only the current tenant's payment history
$stmt = $conn->prepare("
    SELECT
        p.id,
        p.amount,
        p.payment_method,
        p.transaction_reference,
        p.status,
        p.admin_note,
        p.created_at,
        b.property_name,
        b.location
    FROM payments p
    LEFT JOIN bookings b ON p.booking_id = b.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$payments = [];

while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}

$stmt->close();

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SmartRent | Payment History</title>

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
            padding: 20px 7%;
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
            width: 92%;
            max-width: 1200px;
            margin: 35px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #0369a1;
            text-decoration: none;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 5px 20px #0000000d;
        }

        h1 {
            margin-top: 0;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 25px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            color: #334155;
        }

        tr:hover {
            background: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .approved {
            background: #dcfce7;
            color: #166534;
        }

        .rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .unknown {
            background: #e2e8f0;
            color: #334155;
        }

        .empty {
            padding: 25px;
            background: #f8fafc;
            border-radius: 8px;
            text-align: center;
            color: #64748b;
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 18px;
            background: #0284c7;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .button:hover {
            background: #0369a1;
        }

        .note {
            max-width: 220px;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        @media (max-width: 600px) {
            header {
                padding: 18px 5%;
            }

            .container {
                width: 95%;
                margin: 20px auto;
            }

            .card {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>SmartRent</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="bookings.php">My Bookings</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <a class="back" href="dashboard.php">
        &larr; Back to Dashboard
    </a>

    <div class="card">

        <h1>Payment History</h1>

        <p class="subtitle">
            View your submitted payments and their approval status.
        </p>

        <?php if (count($payments) > 0): ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Property</th>
                            <th>Amount (ETB)</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Admin Note</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($payments as $payment): ?>

                            <?php
                            $status = strtolower(
                                trim($payment["status"] ?? "")
                            );

                            $statusClass = "unknown";

                            if ($status === "pending") {
                                $statusClass = "pending";
                            } elseif ($status === "approved") {
                                $statusClass = "approved";
                            } elseif ($status === "rejected") {
                                $statusClass = "rejected";
                            }
                            ?>

                            <tr>
                                <td>
                                    <?= (int)$payment["id"] ?>
                                </td>

                                <td>
                                    <?= e($payment["property_name"]) ?><br>
                                    <small>
                                        <?= e($payment["location"]) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)$payment["amount"],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?= e($payment["payment_method"]) ?>
                                </td>

                                <td>
                                    <?= e(
                                        $payment["transaction_reference"]
                                    ) ?>
                                </td>

                                <td>
                                    <?= e($payment["created_at"]) ?>
                                </td>

                                <td>
                                    <span class="status <?= e($statusClass) ?>">
                                        <?= e($payment["status"]) ?>
                                    </span>
                                </td>

                                <td class="note">
                                    <?= e($payment["admin_note"]) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <div class="empty">
                <h3>No Payment History Yet</h3>
                <p>You have not submitted any payments.</p>

                <a class="button" href="payment.php">
                    Make a Payment
                </a>
            </div>

        <?php endif; ?>

        <a class="button" href="payment.php">
            + Make a Payment
        </a>

    </div>
</div>

</body>
</html>
```
