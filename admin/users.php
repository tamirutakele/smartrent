<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin-login.php");
    exit;
}

$sql = "SELECT id, name, email, phone, role
        FROM userss
        ORDER BY id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Database error: " . $conn->error);
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

    <title>SmartRent - Users</title>

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
            color: white;

            text-decoration: none;

            background: #2563eb;

            padding: 10px 18px;

            border-radius: 6px;
        }

        .back:hover {
            background: #1d4ed8;
        }

        .container {
            padding: 30px;
        }

        .card {
            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.1);

            overflow-x: auto;
        }

        .card h2 {
            color: #1e293b;
        }

        table {
            width: 100%;

            border-collapse: collapse;

            margin-top: 20px;
        }

        th,
        td {
            padding: 14px;

            text-align: left;

            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;

            color: #1e293b;
        }

        td {
            color: #475569;
        }

        .role {
            background: #dbeafe;

            color: #1d4ed8;

            padding: 5px 10px;

            border-radius: 15px;

            font-size: 13px;

            font-weight: bold;
        }

        .admin-role {
            background: #dcfce7;

            color: #15803d;
        }

        /* Action Buttons */

        .edit-button,
        .delete-button {
            display: inline-block;

            color: white;

            text-decoration: none;

            padding: 7px 13px;

            border-radius: 5px;

            font-size: 13px;

            font-weight: bold;

            margin-right: 5px;
        }

        .edit-button {
            background: #2563eb;
        }

        .edit-button:hover {
            background: #1d4ed8;
        }

        .delete-button {
            background: #ef4444;
        }

        .delete-button:hover {
            background: #dc2626;
        }

        .empty {
            text-align: center;

            padding: 30px;

            color: #64748b;
        }

        @media (max-width: 700px) {

            .header {
                flex-direction: column;

                gap: 15px;
            }

            .container {
                padding: 15px;
            }

        }

    </style>

</head>

<body>


    <div class="header">

        <h1>
            SmartRent Users
        </h1>

        <a
            href="index.php"
            class="back"
        >
            ← Admin Dashboard
        </a>

    </div>


    <div class="container">

        <div class="card">

            <h2>
                All Users
            </h2>


            <?php if ($result->num_rows > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Name</th>

                            <th>Email</th>

                            <th>Phone</th>

                            <th>Role</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php while ($user = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars($user["id"]);
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($user["name"]);
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($user["email"]);
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($user["phone"]);
                                    ?>
                                </td>

                                <td>

                                    <?php if ($user["role"] === "admin"): ?>

                                        <span class="role admin-role">
                                            Admin
                                        </span>

                                    <?php else: ?>

                                        <span class="role">
                                            Tenant
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Actions -->

                                <td>

                                    <!-- Edit -->

                                    <a
                                        href="edit-user.php?id=<?php echo $user["id"]; ?>"
                                        class="edit-button"
                                    >
                                        Edit
                                    </a>


                                    <!-- Delete -->

                                    <?php if ($user["role"] !== "admin"): ?>

                                        <a
                                            href="delete-user.php?id=<?php echo $user["id"]; ?>"
                                            class="delete-button"
                                            onclick="return confirm('Are you sure you want to delete this user?');"
                                        >
                                            Delete
                                        </a>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">
                    No users found.
                </div>

            <?php endif; ?>


        </div>

    </div>


</body>

</html>