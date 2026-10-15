<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin-login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: users.php");
    exit;
}

$id = (int) $_GET["id"];

$message = "";
$message_type = "";


// Get user information
$sql = "SELECT id, name, email, phone, role
        FROM userss
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();

    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();

$stmt->close();


// Update user
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $role = $_POST["role"];

    if ($name === "" || $email === "" || $phone === "") {

        $message = "Please fill in all fields.";
        $message_type = "error";

    } else {

        $sql = "UPDATE userss
                SET name = ?, email = ?, phone = ?, role = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $message = "Database error: " . $conn->error;
            $message_type = "error";

        } else {

            $stmt->bind_param(
                "ssssi",
                $name,
                $email,
                $phone,
                $role,
                $id
            );

            if ($stmt->execute()) {

                $message = "User updated successfully!";
                $message_type = "success";

                $user["name"] = $name;
                $user["email"] = $email;
                $user["phone"] = $phone;
                $user["role"] = $role;

            } else {

                $message = "Update failed: " . $stmt->error;
                $message_type = "error";
            }

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit User - SmartRent</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
        }

        .header {
            background: #1e293b;
            color: white;

            padding: 20px 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .back {
            background: #2563eb;

            color: white;

            text-decoration: none;

            padding: 10px 18px;

            border-radius: 6px;
        }

        .container {
            max-width: 600px;

            margin: 40px auto;

            padding: 0 20px;
        }

        .card {
            background: white;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 2px 10px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #1e293b;

            margin-bottom: 25px;
        }

        label {
            display: block;

            font-weight: bold;

            color: #334155;

            margin-bottom: 7px;
        }

        input,
        select {
            width: 100%;

            padding: 12px;

            margin-bottom: 18px;

            border: 1px solid #cbd5e1;

            border-radius: 6px;

            font-size: 15px;
        }

        input:focus,
        select:focus {
            outline: none;

            border-color: #2563eb;
        }

        .update-button {
            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 6px;

            background: #2563eb;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;
        }

        .update-button:hover {
            background: #1d4ed8;
        }

        .message {
            padding: 12px;

            border-radius: 6px;

            margin-bottom: 20px;

            text-align: center;
        }

        .success {
            background: #dcfce7;

            color: #166534;
        }

        .error {
            background: #fee2e2;

            color: #991b1b;
        }

    </style>

</head>

<body>


    <div class="header">

        <h1>
            SmartRent - Edit User
        </h1>

        <a
            href="users.php"
            class="back"
        >
            ← Back to Users
        </a>

    </div>


    <div class="container">

        <div class="card">

            <h2>
                Edit User
            </h2>


            <?php if ($message !== ""): ?>

                <div class="message <?php echo $message_type; ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <label>
                    Full Name
                </label>

                <input
                    type="text"
                    name="name"
                    value="<?php echo htmlspecialchars($user["name"]); ?>"
                    required
                >


                <label>
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?php echo htmlspecialchars($user["email"]); ?>"
                    required
                >


                <label>
                    Phone Number
                </label>

                <input
                    type="text"
                    name="phone"
                    value="<?php echo htmlspecialchars($user["phone"]); ?>"
                    required
                >


                <label>
                    Account Role
                </label>

                <select name="role" required>

                    <option
                        value="tenant"
                        <?php echo $user["role"] === "tenant" ? "selected" : ""; ?>
                    >
                        Tenant
                    </option>

                    <option
                        value="admin"
                        <?php echo $user["role"] === "admin" ? "selected" : ""; ?>
                    >
                        Admin
                    </option>

                </select>


                <button
                    type="submit"
                    class="update-button"
                >
                    Update User
                </button>


            </form>

        </div>

    </div>


</body>

</html>