<?php
session_start();
include "config.php";

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$is_admin = $_SESSION["is_admin"] == 1;

$query = "SELECT first_name FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($first_name);
$stmt->fetch();
$stmt->close();

$query = "SELECT id, time_in, time_out, lunch_in, lunch_out FROM time_logs WHERE user_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($log_id, $last_time_in, $last_time_out, $last_lunch_in, $last_lunch_out);
$stmt->fetch();
$stmt->close();

$log_date = date('Y-m-d');
$query = "SELECT COUNT(*) FROM time_logs WHERE user_id = ? AND log_date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $log_date);
$stmt->execute();
$stmt->bind_result($log_count);
$stmt->fetch();
$stmt->close();

$can_log_today = true;

if ($log_count > 0) {
    $query = "SELECT time_in, time_out, lunch_in, lunch_out FROM time_logs WHERE user_id = ? AND log_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $log_date);
    $stmt->execute();
    $stmt->bind_result($time_in, $time_out, $lunch_in, $lunch_out);
    $stmt->fetch();
    $stmt->close();

    if (!empty($time_in) && !empty($time_out) && !empty($lunch_in) && !empty($lunch_out)) {
        $can_log_today = false;
    }
}

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
    } elseif (empty($last_lunch_in) || empty($last_lunch_out)) {
        echo "<script>alert('Please complete Lunch In and Lunch Out before entering Time Out.');</script>";
    } elseif (strtotime($time_out) <= strtotime($last_time_in)) {
        echo "<script>alert('Time Out must be later than Time In.');</script>";
    } else {
        $time_in_sec = strtotime($last_time_in);
        $lunch_out_sec = strtotime($last_lunch_out);
        $lunch_in_sec = strtotime($last_lunch_in);
        $time_out_sec = strtotime($time_out);

        $morning_seconds = $lunch_out_sec - $time_in_sec;
        $afternoon_seconds = $time_out_sec - $lunch_in_sec;
        $total_seconds = $morning_seconds + $afternoon_seconds;

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["lunch_in"])) {
    $lunch_in = $_POST["lunch_in"];

    if (empty($last_time_in)) {
        echo "<script>alert('Please enter Time In first.');</script>";
    } elseif (empty($last_lunch_out)) {
        echo "<script>alert('Please enter Lunch Out before adding Lunch In.');</script>";
    } elseif (!empty($last_lunch_in)) {
        echo "<script>alert('Lunch In already recorded for this entry.');</script>";
    } else {
        $query = "UPDATE time_logs SET lunch_in = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $lunch_in, $log_id);
        if ($stmt->execute()) {
            echo "<script>alert('Lunch In recorded successfully!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error recording Lunch In.');</script>";
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["lunch_out"])) {
    $lunch_out = $_POST["lunch_out"];

    if (empty($last_time_in)) {
        echo "<script>alert('Please enter Time In first.');</script>";
    } elseif (!empty($last_lunch_out)) {
        echo "<script>alert('Lunch Out already recorded for this entry.');</script>";
    } elseif (strtotime($lunch_out) <= strtotime($last_time_in)) {
        echo "<script>alert('Lunch Out must be later than Time In.');</script>";
    } else {
        $query = "UPDATE time_logs SET lunch_out = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $lunch_out, $log_id);
        if ($stmt->execute()) {
            echo "<script>alert('Lunch Out recorded successfully!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error recording Lunch Out.');</script>";
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["time_in_now"])) {
    $time_in = date('H:i:s');
    $log_date = date('Y-m-d');

    if (!empty($last_time_in) && empty($last_time_out)) {
        echo "<script>alert('Please enter Time Out before adding a new Time In.');</script>";
    } else {
        $query = "INSERT INTO time_logs (user_id, time_in, log_date, first_name) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", $user_id, $time_in, $log_date, $first_name);
        if ($stmt->execute()) {
            $_SESSION['last_time_in'] = $time_in;
            echo "<script>alert('Time In recorded successfully!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error recording Time In.');</script>";
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["time_out_now"])) {
    $time_out = date('H:i:s');

    if (empty($last_time_in)) {
        echo "<script>alert('Please enter Time In first.');</script>";
    } elseif (!empty($last_time_out)) {
        echo "<script>alert('Time Out already recorded for this entry. Please add a new Time In.');</script>";
    } elseif (empty($last_lunch_in) || empty($last_lunch_out)) {
        echo "<script>alert('Please complete Lunch In and Lunch Out before entering Time Out.');</script>";
    } elseif (strtotime($time_out) <= strtotime($last_time_in)) {
        echo "<script>alert('Time Out must be later than Time In.');</script>";
    } else {
        $time_in_sec = strtotime($last_time_in);
        $lunch_out_sec = strtotime($last_lunch_out);
        $lunch_in_sec = strtotime($last_lunch_in);
        $time_out_sec = strtotime($time_out);

        $morning_seconds = $lunch_out_sec - $time_in_sec;
        $afternoon_seconds = $time_out_sec - $lunch_in_sec;
        $total_seconds = $morning_seconds + $afternoon_seconds;

        $total_hours = floor($total_seconds / 3600);
        $total_minutes = floor(($total_seconds % 3600) / 60);
        $total_time = ($total_hours > 0 ? "{$total_hours} hr " : "") . ($total_minutes > 0 ? "{$total_minutes} mins" : "");

        $query = "UPDATE time_logs SET time_out = ?, total_time = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $time_out, $total_time, $log_id);
        if ($stmt->execute()) {
            $_SESSION['last_time_out'] = $time_out;
            echo "<script>alert('Time Out recorded successfully! You can now insert a new Time In.'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error recording Time Out.');</script>";
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["lunch_in_now"])) {
    $lunch_in = date('H:i:s');

    if (empty($last_time_in)) {
        echo "<script>alert('Please enter Time In first.');</script>";
    } elseif (empty($last_lunch_out)) {
        echo "<script>alert('Please enter Lunch Out before adding Lunch In.');</script>";
    } elseif (!empty($last_lunch_in)) {
        echo "<script>alert('Lunch In already recorded for this entry.');</script>";
    } else {
        $query = "UPDATE time_logs SET lunch_in = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $lunch_in, $log_id);
        if ($stmt->execute()) {
            $_SESSION['last_lunch_in'] = $lunch_in;
            echo "<script>alert('Lunch In recorded successfully!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error recording Lunch In.');</script>";
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["lunch_out_now"])) {
    $lunch_out = date('H:i:s');

    if (empty($last_time_in)) {
        echo "<script>alert('Please enter Time In first.');</script>";
    } elseif (!empty($last_lunch_out)) {
        echo "<script>alert('Lunch Out already recorded for this entry.');</script>";
    } elseif (strtotime($lunch_out) <= strtotime($last_time_in)) {
        echo "<script>alert('Lunch Out must be later than Time In.');</script>";
    } else {
        $query = "UPDATE time_logs SET lunch_out = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $lunch_out, $log_id);
        if ($stmt->execute()) {
            $_SESSION['last_lunch_out'] = $lunch_out;
            echo "<script>alert('Lunch Out recorded successfully!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error recording Lunch Out.');</script>";
        }
        $stmt->close();
    }
}

$query = "SELECT time_in, time_out, lunch_in, lunch_out, total_time, log_date FROM time_logs WHERE user_id = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($time_in, $time_out, $lunch_in, $lunch_out, $total_time, $log_date);

$time_logs = [];
$total_hours = 0;
while ($stmt->fetch()) {
    $time_logs[] = ["time_in" => $time_in, "time_out" => $time_out, "lunch_in" => $lunch_in, "lunch_out" => $lunch_out, "total_time" => $total_time, "log_date" => $log_date];

    if (!empty($total_time)) {
        preg_match('/(\d+) hr/', $total_time, $hours);
        preg_match('/(\d+) mins/', $total_time, $minutes);
        $total_hours += (!empty($hours) ? (int)$hours[1] : 0) + (!empty($minutes) ? (int)$minutes[1] / 60 : 0);
    }
}
$stmt->close();

$total_required_hours = 486;
$remaining_hours = $total_required_hours - $total_hours;
$remaining_hours_int = floor($remaining_hours);
$remaining_minutes = floor(($remaining_hours - $remaining_hours_int) * 60);
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
                    <?php if ($is_admin): ?>
                        <a href="admin.php" class="block px-4 py-2 text-black hover:bg-gray-200">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="block px-4 py-2 text-black hover:bg-gray-200">Logout</a>
                </div>
            </div>
            <div class="hidden md:flex items-center">
                <a href="profile.php" class="text-black font-bold hover:underline mr-4">Profile</a>
                <?php if ($is_admin): ?>
                    <a href="admin.php" class="text-black font-bold hover:underline mr-4">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="text-black font-bold hover:underline">Logout</a>
            </div>
        </div>
    </nav>
    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            var dropdown = document.getElementById('dropdown');
            dropdown.classList.toggle('hidden');
        });
    </script>

    <main class="flex-grow container mx-auto mt-10 text-center">
        <h2 class="text-2xl">Welcome, <?php echo htmlspecialchars($first_name); ?>!</h2>

        <?php if ($can_log_today): ?>
            <!-- Time In Button -->
            <div class="flex justify-center mt-4">
                <form method="POST" action="" class="space-y-4 w-full max-w-xs">
                    <button type="submit" name="time_in_now" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600" <?php echo (!empty($last_time_in) && empty($last_time_out)) ? 'disabled' : ''; ?>>Insert Time In</button>
                </form>
            </div>

            <!-- Lunch Out Button -->
            <div class="flex justify-center mt-4">
                <form method="POST" action="" class="space-y-4 w-full max-w-xs">
                    <button type="submit" name="lunch_out_now" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600" <?php echo (empty($last_time_in) || !empty($last_lunch_out)) ? 'disabled' : ''; ?>>Insert Lunch Out</button>
                </form>
            </div>

            <!-- Lunch In Button -->
            <div class="flex justify-center mt-4">
                <form method="POST" action="" class="space-y-4 w-full max-w-xs">
                    <button type="submit" name="lunch_in_now" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600" <?php echo (!empty($last_lunch_in) && empty($last_lunch_out)) ? 'disabled' : ''; ?>>Insert Lunch In</button>
                </form>
            </div>

            <!-- Time Out Button -->
            <div class="flex justify-center mt-4">
                <form method="POST" action="" class="space-y-4 w-full max-w-xs">
                    <button type="submit" name="time_out_now" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600" <?php echo (empty($last_time_in) || !empty($last_time_out) || empty($last_lunch_in) || empty($last_lunch_out)) ? 'disabled' : ''; ?>>Insert Time Out</button>
                </form>
            </div>
        <?php else: ?>
            <div class="mt-4 text-lg font-semibold text-red-500">
                You have already logged your time for today.
            </div>
        <?php endif; ?>

        <!-- Time Log Table -->
        <div class="overflow-x-auto mt-6">
            <table class="min-w-full bg-white border border-gray-300 mx-auto">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="py-2 px-4 border">Date</th>
                        <th class="py-2 px-4 border">Time In</th>
                        <th class="py-2 px-4 border">Lunch Out</th>
                        <th class="py-2 px-4 border">Lunch In</th>
                        <th class="py-2 px-4 border">Time Out</th>
                        <th class="py-2 px-4 border">Total Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_logs as $log): ?>
                        <tr>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($log["log_date"]); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($log["time_in"]); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($log["lunch_out"] ?: '---'); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($log["lunch_in"] ?: '---'); ?></td>
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

        <!-- Remaining Time to Render -->
        <div class="mt-2 text-lg font-semibold">
            Remaining Time to Render: <?php echo $remaining_hours_int; ?> hr <?php echo $remaining_minutes; ?> mins
        </div>
    </main>
</body>
</html>