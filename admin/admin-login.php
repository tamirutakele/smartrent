<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT id, name, email, password, role
            FROM userss
            WHERE email = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $admin = $result->fetch_assoc();

        if (
            $admin["role"] === "admin" &&
            password_verify($password, $admin["password"])
        ) {

            $_SESSION["user"] = $admin["name"];
            $_SESSION["email"] = $admin["email"];
            $_SESSION["role"] = "admin";
            $_SESSION["user_id"] = $admin["id"];

            header("Location: index.php");
            exit;

        } else {

            $message = "Invalid admin email or password.";

        }

    } else {

        $message = "Admin account not found.";

    }

    $stmt->close();
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

    <title>SmartRent - Admin Login</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;

            background: #f1f5f9;

            display: flex;

            justify-content: center;

            align-items: center;

            min-height: 100vh;
        }

        .login-box {

            width: 400px;

            background: white;

            padding: 35px;

            border-radius: 12px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.1);
        }

        h1 {

            text-align: center;

            color: #1e293b;

            margin-bottom: 10px;
        }

        .subtitle {

            text-align: center;

            color: #64748b;

            margin-bottom: 25px;
        }

        label {

            display: block;

            font-weight: bold;

            margin-bottom: 7px;
        }

        input {

            width: 100%;

            padding: 12px;

            border: 1px solid #cbd5e1;

            border-radius: 6px;

            margin-bottom: 18px;

            font-size: 16px;
        }

        input:focus {

            outline: none;

            border-color: #2563eb;
        }

        button {

            width: 100%;

            border: none;

            background: #1e293b;

            color: white;

            padding: 13px;

            border-radius: 6px;

            font-size: 17px;

            font-weight: bold;

            cursor: pointer;
        }

        button:hover {

            background: #0f172a;
        }

        .error {

            background: #fee2e2;

            color: #991b1b;

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 20px;

            text-align: center;
        }

        .back {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #2563eb;

            text-decoration: none;
        }

        .back:hover {

            text-decoration: underline;
        }

    </style>

</head>

<body>

    <div class="login-box">

        <h1>🔐 Admin Login</h1>

        <p class="subtitle">
            SmartRent Administration
        </p>

        <?php if ($message !== ""): ?>

            <div class="error">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST" autocomplete="off">

            <label>
                Admin Email
            </label>

            <input
                type="email"
                name="email"
                placeholder="Enter admin email"
                autocomplete="off"
                required
            >


            <label>
                Admin Password
            </label>

            <input
                type="password"
                name="password"
                placeholder="Enter admin password"
                autocomplete="new-password"
                required
            >


            <button type="submit">
                Login as Admin
            </button>

        </form>


        <a
            href="../login.php"
            class="back"
        >
            ← Back to User Login
        </a>

    </div>

</body>

</html>