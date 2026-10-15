<?php
session_start();

require_once "../config/database.php";

// Check admin login
if (
    !isset($_SESSION["user"]) ||
    !isset($_SESSION["role"]) ||
    strtolower($_SESSION["role"]) !== "admin"
) {
    header("Location: ../admin-login.php");
    exit;
}

$message = "";
$messageType = "";

// Delete property safely using POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_property"])) {

    $propertyId = filter_input(INPUT_POST, "property_id", FILTER_VALIDATE_INT);

    if (!$propertyId || $propertyId < 1) {
        $_SESSION["property_message"] = "Invalid property ID.";
        $_SESSION["property_message_type"] = "error";

    } else {

        // Check whether the property exists
        $check = $conn->prepare(
            "SELECT id FROM properties WHERE id = ?"
        );

        if (!$check) {
            $_SESSION["property_message"] = "Database error while checking property.";
            $_SESSION["property_message_type"] = "error";

        } else {

            $check->bind_param("i", $propertyId);
            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {

                $_SESSION["property_message"] = "Property not found. It may already be deleted.";
                $_SESSION["property_message_type"] = "error";

            } else {

                $check->close();
                $check = null;

                // Delete property from database
                $delete = $conn->prepare(
                    "DELETE FROM properties WHERE id = ?"
                );

                if (!$delete) {

                    $_SESSION["property_message"] = "Unable to prepare property deletion.";
                    $_SESSION["property_message_type"] = "error";

                } else {

                    $delete->bind_param("i", $propertyId);

                    if ($delete->execute() && $delete->affected_rows === 1) {

                        $_SESSION["property_message"] = "Property deleted successfully!";
                        $_SESSION["property_message_type"] = "success";

                    } else {

                        $_SESSION["property_message"] = "Property was not deleted. Check whether a booking is linked to it.";
                        $_SESSION["property_message_type"] = "error";

                    }

                    $delete->close();
                }

            }

            if ($check !== null) {
                $check->close();
            }
        }
    }

    // Redirect to refresh the list and prevent repeated form submission
    header("Location: properties.php");
    exit;
}

// Display message after redirect
if (isset($_SESSION["property_message"])) {
    $message = $_SESSION["property_message"];
    $messageType = $_SESSION["property_message_type"] ?? "success";

    unset($_SESSION["property_message"]);
    unset($_SESSION["property_message_type"]);
}

// Load all properties
$result = $conn->query(
    "SELECT id, name, location, rooms, price, type
     FROM properties
     ORDER BY id DESC"
);

if (!$result) {
    die("Unable to load properties. Please check your database connection.");
}

$totalProperties = $result->num_rows;

// Escape output safely
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

    <title>SmartRent - Manage Properties</title>

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
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 25px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .header-actions a {
            text-decoration: none;
            color: white;
            padding: 11px 16px;
            border-radius: 7px;
            font-weight: bold;
        }

        .add-button {
            background: #16a34a;
        }

        .add-button:hover {
            background: #15803d;
        }

        .dashboard-button {
            background: #2563eb;
        }

        .dashboard-button:hover {
            background: #1d4ed8;
        }

        .container {
            max-width: 1250px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .07);
            overflow-x: auto;
        }

        .card h2 {
            margin-bottom: 10px;
            font-size: 24px;
        }

        .description {
            color: #64748b;
            margin-bottom: 22px;
            line-height: 1.6;
        }

        .message {
            padding: 14px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-weight: bold;
            line-height: 1.5;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: #f8fafc;
            color: #0f172a;
            text-align: left;
            padding: 15px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .property-name {
            font-weight: bold;
        }

        .price {
            color: #15803d;
            font-weight: bold;
            white-space: nowrap;
        }

        .badge {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
        }

        .delete-form {
            margin: 0;
        }

        .delete-button {
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px 13px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .delete-button:hover {
            background: #b91c1c;
        }

        .total {
            margin-top: 20px;
            color: #64748b;
        }

        .empty {
            text-align: center;
            padding: 35px 20px;
            color: #64748b;
            line-height: 1.8;
        }

        .empty h3 {
            color: #1e293b;
            margin-bottom: 8px;
        }

        @media (max-width: 600px) {
            .header {
                padding: 18px;
            }

            .header h1 {
                font-size: 21px;
            }

            .container {
                padding: 0 12px;
            }

            .card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<header class="header">

    <h1>🏠 SmartRent Properties</h1>

    <div class="header-actions">

        <a href="add-property.php" class="add-button">
            + Add Property
        </a>

        <a href="index.php" class="dashboard-button">
            ← Admin Dashboard
        </a>

    </div>

</header>

<main class="container">

    <section class="card">

        <h2>All Properties</h2>

        <p class="description">
            View and manage rental properties saved in your database.
        </p>

        <?php if ($message !== ""): ?>

            <div class="message <?php echo e($messageType); ?>">
                <?php echo e($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($totalProperties > 0): ?>

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Property Name</th>
                        <th>Location</th>
                        <th>Bedrooms</th>
                        <th>Monthly Price</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($property = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo (int)$property["id"]; ?>
                            </td>

                            <td class="property-name">
                                <?php echo e($property["name"]); ?>
                            </td>

                            <td>
                                <?php echo e($property["location"]); ?>
                            </td>

                            <td>
                                <?php echo (int)$property["rooms"]; ?>
                            </td>

                            <td class="price">
                                <?php echo number_format((float)$property["price"], 2); ?>
                                ETB / Month
                            </td>

                            <td>
                                <span class="badge">
                                    <?php echo e($property["type"] ?: "Other"); ?>
                                </span>
                            </td>

                            <td>

                                <form
                                    method="POST"
                                    action="properties.php"
                                    class="delete-form"
                                    onsubmit="return confirm('Are you sure you want to delete this property? This action cannot be undone.');"
                                >

                                    <input
                                        type="hidden"
                                        name="property_id"
                                        value="<?php echo (int)$property["id"]; ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_property"
                                        value="1"
                                        class="delete-button"
                                    >
                                        🗑 Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">
                <h3>No properties found.</h3>
                <p>You can add a new rental property using the button above.</p>
            </div>

        <?php endif; ?>

        <p class="total">
            Total Properties:
            <strong><?php echo $totalProperties; ?></strong>
        </p>

    </section>

</main>

</body>
</html>