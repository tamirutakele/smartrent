<?php
session_start();

require_once "../config/database.php";

// Admin authentication
if (
    !isset($_SESSION["user"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

$message = "";
$messageType = "success";

// Handle approve/reject actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $paymentId = (int)($_POST["payment_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($paymentId <= 0 || !in_array($action, ["approve", "reject"], true)) {
        $message = "Invalid payment request.";
        $messageType = "error";

    } else {

        $newStatus = ($action === "approve")
            ? "Approved"
            : "Rejected";

        // Update only pending payments
        $stmt = $conn->prepare("
            UPDATE payments
            SET status = ?
            WHERE id = ?
              AND status = 'Pending'
        ");

        $stmt->bind_param(
            "si",
            $newStatus,
            $paymentId
        );

        if ($stmt->execute() && $stmt->affected_rows === 1) {

            $message = "Payment status updated successfully.";

        } else {

            $message = "Payment not found or already reviewed.";
            $messageType = "error";
        }

        $stmt->close();
    }
}

// Get all payments with booking and tenant information
$sql = "
    SELECT
        p.id,
        p.user_id,
        p.booking_id,
        p.amount,
        p.payment_method,
        p.transaction_reference,
        p.status,
        p.admin_note,
        p.created_at,
        b.property_name,
        b.location,
        u.name AS tenant_name,
        u.email AS tenant_email
    FROM payments p
    LEFT JOIN bookings b
        ON p.booking_id = b.id
    LEFT JOIN userss u
        ON p.user_id = u.id
    ORDER BY p.id DESC
";

$result = $conn->query($sql);

if (!$result) {
    exit("Unable to load payments. Please check your database table columns.");
}

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

    <title>SmartRent | Admin Payments</title>

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
            gap: 12px;
        }

        header h2 {
            margin: 0;
            color: #38bdf8;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 14px;
        }

        .container {
            width: 94%;
            max-width: 1450px;
            margin: 30px auto;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 14px;
            box-shadow: 0 5px 20px #0000000d;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 13px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 14px;
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

        .btn {
            display: inline-block;
            padding: 8px 11px;
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            font-weight: bold;
            margin: 3px 0;
        }

        .approve {
            background: #16a34a;
        }

        .reject {
            background: #dc2626;
        }

        .message {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #64748b;
        }
    </style>
</head>

<body>

<header>
    <h2>SmartRent Admin</h2>

    <nav>
        <a href="index.php">Dashboard</a>
        <a href="properties.php">Properties</a>
        <a href="users.php">Users</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <div class="card">

        <h1>💳 Payment Management</h1>

        <p>Review tenant payments and confirm their status.</p>

        <?php if ($message !== ""): ?>
            <div class="message <?= e($messageType) ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tenant</th>
                        <th>Property</th>
                        <th>Amount (ETB)</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($payment = $result->fetch_assoc()): ?>

                        <tr>
                            <td><?= (int)$payment["id"] ?></td>

                            <td>
                                <?= e($payment["tenant_name"] ?? "Unknown") ?>
                                <br>
                                <small>
                                    <?= e($payment["tenant_email"] ?? "") ?>
                                </small>
                            </td>

                            <td>
                                <?= e($payment["property_name"] ?? "Unknown") ?>
                                <br>
                                <?= e($payment["location"] ?? "") ?>
                            </td>

                            <td>
                                <?= number_format((float)$payment["amount"], 2) ?>
                            </td>

                            <td><?= e($payment["payment_method"]) ?></td>

                            <td>
                                <?= e($payment["transaction_reference"] ?: "-") ?>
                            </td>

                            <td><?= e($payment["created_at"]) ?></td>

                            <td>
                                <span class="status <?= e($payment["status"]) ?>">
                                    <?= e($payment["status"]) ?>
                                </span>
                            </td>

                            <td>

                                <?php if ($payment["status"] === "Pending"): ?>

                                    <form method="POST">
                                        <input
                                            type="hidden"
                                            name="payment_id"
                                            value="<?= (int)$payment["id"] ?>"
                                        >

                                        <button
                                            class="btn approve"
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            onclick="return confirm('Approve this payment?')"
                                        >
                                            Approve
                                        </button>

                                        <button
                                            class="btn reject"
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            onclick="return confirm('Reject this payment?')"
                                        >
                                            Reject
                                        </button>
                                    </form>

                                <?php else: ?>

                                    Reviewed

                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9" class="empty">
                            No payments have been submitted yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>