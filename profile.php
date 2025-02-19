<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Fetch user details
$query = "SELECT first_name, last_name, username, email, password FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name, $username, $email, $hashed_password);
$stmt->fetch();
$stmt->close();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $new_first_name = $_POST["first_name"];
    $new_last_name = $_POST["last_name"];
    $new_username = $_POST["username"];
    $new_email = $_POST["email"];

    $query = "UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssi", $new_first_name, $new_last_name, $new_username, $new_email, $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
    } else {
        echo "<script>alert('Error updating profile.');</script>";
    }
    $stmt->close();
}

// Handle password update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_password"])) {
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    // Verify current password
    if (!password_verify($current_password, $hashed_password)) {
        echo "<script>alert('Current password is incorrect.');</script>";
    } elseif ($new_password !== $confirm_password) {
        echo "<script>alert('New passwords do not match.');</script>";
    } else {
        // Hash new password
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $new_hashed_password, $user_id);

        if ($stmt->execute()) {
            echo "<script>alert('Password changed successfully!'); window.location.href='profile.php';</script>";
        } else {
            echo "<script>alert('Error updating password.');</script>";
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
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function toggleForm(formType) {
            document.getElementById('personal-info-form').style.display = formType === 'personal' ? 'block' : 'none';
            document.getElementById('password-form').style.display = formType === 'password' ? 'block' : 'none';
        }
    </script>
</head>
<body>
<nav class="bg-blue-500 p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
        <span class="text-black text-xl font-semibold">OJT VIRTUAL CALCULATOR</span>
        <div class="relative">
            <button id="menu-toggle" class="text-black font-bold hover:underline md:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
            <div id="dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white shadow-lg rounded z-10">
                <a href="dashboard.php" class="block px-4 py-2 text-black hover:bg-gray-200">Home</a>
                <a href="logout.php" class="block px-4 py-2 text-black hover:bg-gray-200">Logout</a>
            </div>
        </div>
        <div class="hidden md:flex items-center">
            <a href="dashboard.php" class="text-black font-bold hover:underline mr-4">Home</a>
            <a href="logout.php" class="text-black font-bold hover:underline">Logout</a>
        </div>
    </div>
</nav>

<main class="container mx-auto mt-10 px-4">
    <h2 class="text-2xl text-center mb-10">Edit Profile</h2>
    <div class="flex flex-col md:flex-row justify-center space-x-0 md:space-x-4 mb-6">
        <button onclick="toggleForm('personal')" class="w-full md:w-1/6 bg-blue-500 text-white py-2 rounded hover:bg-blue-600 mb-2 md:mb-0">Change Personal Info</button>
        <button onclick="toggleForm('password')" class="w-full md:w-1/6 bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Change Password</button>
    </div>

    <!-- Update Profile Form -->
    <form id="personal-info-form" method="POST" action="" class="space-y-4 mt-6 max-w-md mx-auto" style="display: none;">
        <input type="hidden" name="update_profile" value="1">
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required class="w-full px-3 py-2 border rounded" />
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required class="w-full px-3 py-2 border rounded" />
        <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required class="w-full px-3 py-2 border rounded" />
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full px-3 py-2 border rounded" />
        <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Update Profile</button>
    </form>

    <!-- Change Password Form -->
    <form id="password-form" method="POST" action="" class="space-y-4 mt-6 max-w-md mx-auto" style="display: none;">
        <input type="hidden" name="update_password" value="1">
        <input type="password" name="current_password" placeholder="Current Password" required class="w-full px-3 py-2 border rounded" />
        <input type="password" name="new_password" placeholder="New Password" required class="w-full px-3 py-2 border rounded" />
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required class="w-full px-3 py-2 border rounded" />
        <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Change Password</button>
    </form>
</main>

<script>
    document.getElementById('menu-toggle').addEventListener('click', function() {
        var dropdown = document.getElementById('dropdown');
        dropdown.classList.toggle('hidden');
    });
</script>
</body>
</html>