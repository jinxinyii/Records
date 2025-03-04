<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["is_admin"] != 1) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["edit_log"])) {
        $log_id = $_POST["log_id"];
        $time_in = $_POST["time_in"];
        $lunch_out = $_POST["lunch_out"];
        $lunch_in = $_POST["lunch_in"];
        $time_out = $_POST["time_out"];
        $log_date = $_POST["log_date"];

        $time_in_sec = strtotime($time_in);
        $lunch_out_sec = strtotime($lunch_out);
        $lunch_in_sec = strtotime($lunch_in);
        $time_out_sec = strtotime($time_out);

        $morning_seconds = $lunch_out_sec - $time_in_sec;
        $afternoon_seconds = $time_out_sec - $lunch_in_sec;
        $total_seconds = $morning_seconds + $afternoon_seconds;

        $total_hours = floor($total_seconds / 3600);
        $total_minutes = floor(($total_seconds % 3600) / 60);
        $total_time = ($total_hours > 0 ? "{$total_hours} hr " : "") . ($total_minutes > 0 ? "{$total_minutes} mins" : "");

        $query = "UPDATE time_logs SET time_in = ?, lunch_out = ?, lunch_in = ?, time_out = ?, log_date = ?, total_time = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $time_in, $lunch_out, $lunch_in, $time_out, $log_date, $total_time, $log_id);
        if ($stmt->execute()) {
            echo "<script>alert('Log updated successfully!'); window.location.href='admin.php';</script>";
        } else {
            echo "<script>alert('Error updating log.');</script>";
        }
        $stmt->close();
    } elseif (isset($_POST["add_log"])) {
        $user_id = $_POST["user_id"];
        $time_in = $_POST["time_in"];
        $lunch_out = $_POST["lunch_out"];
        $lunch_in = $_POST["lunch_in"];
        $time_out = $_POST["time_out"];
        $log_date = $_POST["log_date"];

        $time_in_sec = strtotime($time_in);
        $lunch_out_sec = strtotime($lunch_out);
        $lunch_in_sec = strtotime($lunch_in);
        $time_out_sec = strtotime($time_out);

        $morning_seconds = $lunch_out_sec - $time_in_sec;
        $afternoon_seconds = $time_out_sec - $lunch_in_sec;
        $total_seconds = $morning_seconds + $afternoon_seconds;

        $total_hours = floor($total_seconds / 3600);
        $total_minutes = floor(($total_seconds % 3600) / 60);
        $total_time = ($total_hours > 0 ? "{$total_hours} hr " : "") . ($total_minutes > 0 ? "{$total_minutes} mins" : "");

        $query = "INSERT INTO time_logs (user_id, time_in, lunch_out, lunch_in, time_out, log_date, total_time) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssss", $user_id, $time_in, $lunch_out, $lunch_in, $time_out, $log_date, $total_time);
        if ($stmt->execute()) {
            echo "<script>alert('Log added successfully!'); window.location.href='admin.php';</script>";
        } else {
            echo "<script>alert('Error adding log.');</script>";
        }
        $stmt->close();
    }
}

$query = "SELECT time_logs.id, time_logs.user_id, time_logs.time_in, time_logs.lunch_out, time_logs.lunch_in, time_logs.time_out, time_logs.log_date, users.first_name, users.last_name FROM time_logs JOIN users ON time_logs.user_id = users.id ORDER BY time_logs.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
                    <a href="logout.php" class="block px-4 py-2 text-black hover:bg-gray-200">Logout</a>
                </div>
            </div>
            <div class="hidden md:flex items-center">
                <a href="logout.php" class="text-black font-bold hover:underline">Logout</a>
            </div>
        </div>
    </nav>
    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            var dropdown = document.getElementById('dropdown');
            dropdown.classList.toggle('hidden');
        });

        function openModal(log) {
            document.getElementById('log_id').value = log.id;
            document.getElementById('time_in').value = log.time_in;
            document.getElementById('lunch_out').value = log.lunch_out;
            document.getElementById('lunch_in').value = log.lunch_in;
            document.getElementById('time_out').value = log.time_out;
            document.getElementById('log_date').value = log.log_date;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>

    <main class="flex-grow container mx-auto mt-10 text-center">
        <h2 class="text-2xl">Time Logs</h2>

        <!-- Add Log Form -->
        <div class="flex justify-center mt-4">
            <form method="POST" action="" class="space-y-4 w-full max-w-xs">
                <input type="hidden" name="add_log" value="1">
                <input type="text" name="user_id" placeholder="User ID" class="w-full border border-gray-300 p-2 rounded">
                <input type="text" name="time_in" placeholder="Time In (HH:MM:SS)" class="w-full border border-gray-300 p-2 rounded">
                <input type="text" name="lunch_out" placeholder="Lunch Out (HH:MM:SS)" class="w-full border border-gray-300 p-2 rounded">
                <input type="text" name="lunch_in" placeholder="Lunch In (HH:MM:SS)" class="w-full border border-gray-300 p-2 rounded">
                <input type="text" name="time_out" placeholder="Time Out (HH:MM:SS)" class="w-full border border-gray-300 p-2 rounded">
                <input type="date" name="log_date" class="w-full border border-gray-300 p-2 rounded">
                <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Add Log</button>
            </form>
        </div>

        <!-- Time Log Table -->
        <div class="overflow-x-auto mt-6">
            <table class="min-w-full bg-white border border-gray-300 mx-auto">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="py-2 px-4 border">ID</th>
                        <th class="py-2 px-4 border">User ID</th>
                        <th class="py-2 px-4 border">Full Name</th>
                        <th class="py-2 px-4 border">Date</th>
                        <th class="py-2 px-4 border">Time In</th>
                        <th class="py-2 px-4 border">Lunch Out</th>
                        <th class="py-2 px-4 border">Lunch In</th>
                        <th class="py-2 px-4 border">Time Out</th>
                        <th class="py-2 px-4 border">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row["id"]); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row["user_id"]); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row["first_name"] . ' ' . $row["last_name"]); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row["log_date"]); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row["time_in"]); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row["lunch_out"] ?: '---'); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row["lunch_in"] ?: '---'); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row["time_out"] ?: '---'); ?></td>
                            <td class="py-2 px-4 border">
                                <button onclick="openModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Edit</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
            <h2 class="text-xl mb-4">Edit Log</h2>
            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="edit_log" value="1">
                <input type="hidden" id="log_id" name="log_id">
                <input type="text" id="time_in" name="time_in" placeholder="Time In (HH:MM:SS)" class="w-full border border-gray-300 p-2 rounded">
                <input type="text" id="lunch_out" name="lunch_out" placeholder="Lunch Out (HH:MM:SS)" class="w-full border border-gray-300 p-2 rounded">
                <input type="text" id="lunch_in" name="lunch_in" placeholder="Lunch In (HH:MM:SS)" class="w-full border border-gray-300 p-2 rounded">
                <input type="text" id="time_out" name="time_out" placeholder="Time Out (HH:MM:SS)" class="w-full border border-gray-300 p-2 rounded">
                <input type="date" id="log_date" name="log_date" class="w-full border border-gray-300 p-2 rounded">
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeModal()" class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Update</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
