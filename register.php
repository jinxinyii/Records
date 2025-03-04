<?php
include "config.php";

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Validate inputs using regex
    if (!preg_match("/^[a-zA-Z-' ]*$/", $first_name)) {
        $error_message = "Only letters and white space allowed in First Name";
    } elseif (!preg_match("/^[a-zA-Z-' ]*$/", $last_name)) {
        $error_message = "Only letters and white space allowed in Last Name";
    } elseif (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
        $error_message = "Only letters and numbers allowed in Username";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $first_name, $last_name, $username, $email, $password_hashed);

        if ($stmt->execute()) {
            $success_message = "Registration successful. <a href='index.php' class='text-blue-500 hover:underline'>Login here</a>";
        } else {
            $error_message = "Error: " . $conn->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-6 sm:p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Register</h2>
        <?php if (!empty($error_message)): ?>
            <div class="mb-4 text-red-500 text-center">
                <?php echo $error_message; ?>
            </div>
        <?php elseif (!empty($success_message)): ?>
            <div class="mb-4 text-green-500 text-center">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="" class="space-y-4">
            <input type="text" name="first_name" placeholder="First Name" required class="w-full px-3 py-2 border rounded" />
            <input type="text" name="last_name" placeholder="Last Name" required class="w-full px-3 py-2 border rounded" />
            <input type="text" name="username" placeholder="Username" required class="w-full px-3 py-2 border rounded" />
            <input type="email" name="email" placeholder="Email" required class="w-full px-3 py-2 border rounded" />
            <input type="password" name="password" placeholder="Password" required class="w-full px-3 py-2 border rounded" />
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Register</button>
        </form>
        <p class="mt-4 text-center">Already have an account? <a href="index.php" class="text-blue-500 hover:underline">Login here</a></p>
    </div>
</body>
</html>