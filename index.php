<?php
include "config.php";
session_start();

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $query = "SELECT id, password, is_admin FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $is_admin);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $id;
            $_SESSION["is_admin"] = $is_admin;
            if ($is_admin) {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Invalid username or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-6 sm:p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
        <?php if (!empty($error_message)): ?>
            <div class="mb-4 text-red-500 text-center">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="" class="space-y-4">
            <input type="text" name="username" placeholder="Username" required class="w-full px-3 py-2 border rounded" />
            <input type="password" name="password" placeholder="Password" required class="w-full px-3 py-2 border rounded" />
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Login</button>
        </form>
        <p class="mt-4 text-center">Don't have an account? <a href="register.php" class="text-blue-500 hover:underline">Register here</a></p>
    </div>
</body>
</html>