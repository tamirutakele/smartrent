<?php
session_start();

require_once "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";

    // Validate required fields
    if (
        $name === "" ||
        $email === "" ||
        $phone === "" ||
        $password === ""
    ) {
        $message = "Please fill in all fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";
        $message_type = "error";

    } else {

        // Check if email already exists
        $check_sql = "
            SELECT id
            FROM userss
            WHERE email = ?
            LIMIT 1
        ";

        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {

            $message = "Database error. Please try again.";
            $message_type = "error";

        } else {

            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();

            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {

                $message = "This email is already registered.";
                $message_type = "error";

            } else {

                // Hash password securely
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Every account created through this page is a tenant.
                // The role is assigned on the server, not by user input.
                $role = "tenant";

                $sql = "
                    INSERT INTO userss
                        (name, email, password, phone, role)
                    VALUES
                        (?, ?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);

                if (!$stmt) {

                    $message = "Database error. Please try again.";
                    $message_type = "error";

                } else {

                    $stmt->bind_param(
                        "sssss",
                        $name,
                        $email,
                        $hashed_password,
                        $phone,
                        $role
                    );

                    if ($stmt->execute()) {

                        $message = "Registration successful! You can login now.";
                        $message_type = "success";

                    } else {

                        $message = "Registration failed. Please try again.";
                        $message_type = "error";

                    }

                    $stmt->close();
                }
            }

            $check_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SmartRent - Create Account</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eff6ff, #e0e7ff);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .register-box {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 10px 35px rgba(15, 23, 42, 0.12);
        }

        .logo {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 8px;
        }

        h1 {
            text-align: center;
            color: #1e293b;
            font-size: 25px;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        label {
            display: block;
            color: #334155;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input {
            display: block;
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            color: #1e293b;
            background: #ffffff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        input::placeholder {
            color: #94a3b8;
        }

        button {
            display: block;
            width: 100%;
            padding: 14px;
            margin-top: 8px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: #1d4ed8;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            line-height: 1.5;
        }

        .success {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
        }

        .error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fecaca;
        }

        .login-link {
            text-align: center;
            margin-top: 22px;
            color: #64748b;
            font-size: 14px;
        }

        .login-link a {
            color: #2563eb;
            font-weight: bold;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .register-box {
                padding: 25px 20px;
            }
        }
    </style>

</head>

<body>

    <div class="register-box">

        <div class="logo">SmartRent</div>

        <h1>Create Account</h1>

        <p class="subtitle">
            Create your account to find your next home.
        </p>

        <?php if ($message !== ""): ?>

            <div class="message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="register.php">

            <div class="form-group">

                <label for="name">Full Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    autocomplete="name"
                    required
                >

            </div>

            <div class="form-group">

                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    required
                >

            </div>

            <div class="form-group">

                <label for="phone">Phone Number</label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    autocomplete="tel"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    minlength="6"
                    required
                >

            </div>

            <button type="submit">
                Create Account
            </button>

        </form>

        <div class="login-link">

            Already have an account?

            <a href="login.php">Login</a>

        </div>

    </div>

</body>

</html>