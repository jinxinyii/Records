<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$query = "SELECT first_name FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($first_name);
$stmt->fetch();
$stmt->close();

$query = "SELECT id, time_in, time_out FROM time_logs WHERE user_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($log_id, $last_time_in, $last_time_out);
$stmt->fetch();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["time_in"])) {
    $time_in = $_POST["time_in"];
    $log_date = date('Y-m-d');

    if (!empty($last_time_in) && empty($last_time_out)) {
        echo "<script>alert('Please enter Time Out before adding a new Time In.');</script>";
    } else {
        $query = "INSERT INTO time_logs (user_id, time_in, log_date, first_name) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", $user_id, $time_in, $log_date, $first_name);
        if ($stmt->execute()) {
            echo "<script>alert('Time In recorded successfully!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error recording Time In.');</script>";
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["time_out"])) {
    $time_out = $_POST["time_out"];

    if (empty($last_time_in)) {
        echo "<script>alert('Please enter Time In first.');</script>";
    } elseif (!empty($last_time_out)) {
        echo "<script>alert('Time Out already recorded for this entry. Please add a new Time In.');</script>";
    } elseif (strtotime($time_out) <= strtotime($last_time_in)) {
        echo "<script>alert('Time Out must be later than Time In.');</script>";
    } else {
        $time_in_sec = strtotime($last_time_in);
        $time_out_sec = strtotime($time_out);
        $total_seconds = $time_out_sec - $time_in_sec;

        $total_hours = floor($total_seconds / 3600);
        $total_minutes = floor(($total_seconds % 3600) / 60);
        $total_time = ($total_hours > 0 ? "{$total_hours} hr " : "") . ($total_minutes > 0 ? "{$total_minutes} mins" : "");

        $query = "UPDATE time_logs SET time_out = ?, total_time = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $time_out, $total_time, $log_id);
        if ($stmt->execute()) {
            echo "<script>alert('Time Out recorded successfully! You can now insert a new Time In.'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error recording Time Out.');</script>";
        }
        $stmt->close();
    }
}

$query = "SELECT time_in, time_out, total_time, log_date FROM time_logs WHERE user_id = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($time_in, $time_out, $total_time, $log_date);

$time_logs = [];
$total_hours = 0; // Initialize total hours
while ($stmt->fetch()) {
    $time_logs[] = ["time_in" => $time_in, "time_out" => $time_out, "total_time" => $total_time, "log_date" => $log_date];
    
    // Calculate total hours
    if (!empty($total_time)) {
        preg_match('/(\d+) hr/', $total_time, $hours);
        preg_match('/(\d+) mins/', $total_time, $minutes);
        $total_hours += (!empty($hours) ? (int)$hours[1] : 0) + (!empty($minutes) ? (int)$minutes[1] / 60 : 0);
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-white min-h-screen flex flex-col">
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
                    <a href="profile.php" class="block px-4 py-2 text-black hover:bg-gray-200">Profile</a>
                    <a href="logout.php" class="block px-4 py-2 text-black hover:bg-gray-200">Logout</a>
                </div>
            </div>
            <div class="hidden md:flex items-center">
                <a href="profile.php" class="text-black font-bold hover:underline mr-4">Profile</a>
                <a href="logout.php" class="text-black font-bold hover:underline">Logout</a>
            </div>
        </div>
    </nav>
    <script>
        // Toggle dropdown menu for mobile view
        document.getElementById('menu-toggle').addEventListener('click', function() {
            var dropdown = document.getElementById('dropdown');
            dropdown.classList.toggle('hidden');
        });
    </script>

    <main class="flex-grow container mx-auto mt-10 text-center">
        <h2 class="text-2xl">Welcome, <?php echo htmlspecialchars($first_name); ?>!</h2>

        <!-- Time In Form -->
        <div class="flex justify-center mt-4">
            <form method="POST" action="" class="space-y-4 w-full max-w-xs">
                <label for="time_in" class="block text-sm font-medium text-gray-700">Time In</label>
                <input type="time" name="time_in" id="time_in" required class="w-full px-3 py-2 border rounded" <?php echo (!empty($last_time_in) && empty($last_time_out)) ? 'disabled' : ''; ?> />
                <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600" <?php echo (!empty($last_time_in) && empty($last_time_out)) ? 'disabled' : ''; ?>>Insert Time In</button>
            </form>
        </div>

        <!-- Time Out Form -->
        <div class="flex justify-center mt-4">
            <form method="POST" action="" class="space-y-4 w-full max-w-xs">
                <label for="time_out" class="block text-sm font-medium text-gray-700">Time Out</label>
                <input type="time" name="time_out" id="time_out" required class="w-full px-3 py-2 border rounded" <?php echo (empty($last_time_in) || !empty($last_time_out)) ? 'disabled' : ''; ?> />
                <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600" <?php echo (empty($last_time_in) || !empty($last_time_out)) ? 'disabled' : ''; ?>>Insert Time Out</button>
            </form>
        </div>

        <!-- Time Log Table -->
        <div class="overflow-x-auto mt-6">
            <table class="min-w-full bg-white border border-gray-300 mx-auto">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="py-2 px-4 border">Date</th>
                        <th class="py-2 px-4 border">Time In</th>
                        <th class="py-2 px-4 border">Time Out</th>
                        <th class="py-2 px-4 border">Total Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_logs as $log): ?>
                        <tr>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($log["log_date"]); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($log["time_in"]); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($log["time_out"] ?: '---'); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($log["total_time"] ?: '---'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Total Overall Hours -->
        <div class="mt-4 text-lg font-semibold">
            Total Overall Hours: <?php echo floor($total_hours); ?> hr <?php echo ($total_hours - floor($total_hours)) * 60; ?> mins
        </div>
    </main>
</body>
</html>