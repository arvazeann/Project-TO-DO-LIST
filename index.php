<?php
require 'function.php';

$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

$query = "SELECT * FROM todo_list WHERE 1=1";

if (!empty($status_filter)) {
    $query .= " AND status = '$status_filter'";
}

if (!empty($priority_filter)) {
    $query .= " AND priority = '$priority_filter'";
}

$query .= " ORDER BY $sort_by $sort_order";

$data = ambildata($koneksi, $query);

$total_tasks = count($data);
$completed_tasks = 0;
$pending_tasks = 0;
$in_progress_tasks = 0;
$overdue_tasks = 0;
$today_tasks = 0;
$priority_tasks = 0;

foreach ($data as $task) {
    if ($task['status'] === 'selesai' || $task['status'] === 'done') {
        $completed_tasks++;
    } elseif ($task['status'] === 'in progress' || $task['status'] === 'progress') {
        $in_progress_tasks++;
    } elseif ($task['status'] === 'overdue') {
        $overdue_tasks++;
    } else {
        $pending_tasks++;
    }

    $due_date = strtotime($task['due_date']);
    $today = strtotime(date('Y-m-d'));

    if ($due_date == $today && ($task['status'] !== 'selesai' && $task['status'] !== 'done')) {
        $today_tasks++;
    }
}

$progress_percentage = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

if (isset($_POST["cari"])) {
    $data = cari($koneksi, $_POST["keyword"]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To Do List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        'primary-dark': '#1d4ed8',
                        success: '#10b981',
                        danger: '#ef4444',
                        warning: '#f59e0b',
                        'dark-blue': '#1e40af',
                        'overdue': '#dc2626'
                    }
                }
            }
        }
    </script>
    <style>
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        .slide-down {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .task-row:hover {
            transform: translateX(4px);
            transition: transform 0.2s ease;
        }

        .progress-bar {
            transition: width 1s ease-in-out;
        }

        .deadline-overdue {
            border-left: 4px solid #ef4444;
            background-color: #fef2f2;
        }

        .deadline-today {
            border-left: 4px solid #f59e0b;
            background-color: #fffbeb;
        }

        .deadline-normal {
            border-left: 4px solid #10b981;
        }

        .status-overdue {
            background: rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }


        .flatpickr-calendar {
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            animation: slideDown 0.3s ease-out;
        }

        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange,
        .flatpickr-day.selected.inRange,
        .flatpickr-day.startRange.inRange,
        .flatpickr-day.endRange.inRange,
        .flatpickr-day.selected:focus,
        .flatpickr-day.startRange:focus,
        .flatpickr-day.endRange:focus,
        .flatpickr-day.selected:hover,
        .flatpickr-day.startRange:hover,
        .flatpickr-day.endRange:hover,
        .flatpickr-day.selected.prevMonthDay,
        .flatpickr-day.startRange.prevMonthDay,
        .flatpickr-day.endRange.prevMonthDay,
        .flatpickr-day.selected.nextMonthDay,
        .flatpickr-day.startRange.nextMonthDay,
        .flatpickr-day.endRange.nextMonthDay {
            background: #3b82f6;
            border-color: #3b82f6;
        }

        .flatpickr-day.today {
            border-color: #3b82f6;
        }

        .flatpickr-day.today:hover {
            background: #3b82f6;
            color: white;
        }

        .flatpickr-day:hover {
            background: #e5e7eb;
        }

        .flatpickr-time input:hover,
        .flatpickr-time .flatpickr-am-pm:hover,
        .flatpickr-time input:focus,
        .flatpickr-time .flatpickr-am-pm:focus {
            background: #f3f4f6;
        }

        .flatpickr-calendar.arrowTop:before {
            border-bottom-color: #e5e7eb;
        }

        .flatpickr-calendar.arrowTop:after {
            border-bottom-color: white;
        }


        .custom-select {
            position: relative;
            width: 100%;
        }

        .select-selected {
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            justify-content: between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .select-selected:after {
            content: "▼";
            font-size: 12px;
            margin-left: auto;
            transition: transform 0.2s ease;
        }

        .select-selected.select-arrow-active:after {
            transform: rotate(180deg);
        }

        .select-selected:hover {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .select-items {
            position: absolute;
            background-color: white;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 99;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-top: 4px;
            overflow: hidden;
            animation: slideDown 0.2s ease-out;
        }

        .select-items div {
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease;
        }

        .select-items div:hover {
            background-color: #f3f4f6;
        }

        .select-hide {
            display: none;
        }

        .priority-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .priority-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .priority-rendah .priority-dot {
            background-color: #10b981;
        }

        .priority-sedang .priority-dot {
            background-color: #f59e0b;
        }

        .priority-tinggi .priority-dot {
            background-color: #ef4444;
        }


        @keyframes progressPulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .progress-pulse {
            animation: progressPulse 0.5s ease-in-out;
        }


        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .custom-checkbox.checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .custom-checkbox.checked:after {
            content: "✓";
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .custom-checkbox:hover {
            border-color: #10b981;
            transform: scale(1.1);
        }


        @keyframes taskComplete {
            0% {
                transform: scale(1);
                background-color: white;
            }

            50% {
                transform: scale(0.98);
                background-color: #f0fdf4;
            }

            100% {
                transform: scale(1);
                background-color: white;
            }
        }

        .task-complete-animation {
            animation: taskComplete 0.6s ease-in-out;
        }


        @keyframes confetti {
            0% {
                transform: translateY(0) rotate(0);
                opacity: 1;
            }

            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f59e0b;
            animation: confetti 2s ease-out forwards;
            z-index: 1000;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen p-4 md:p-8">
    <div class="max-w-7xl mx-auto">

        <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-800">To-Do List</h1>
                    <p class="text-gray-600 mt-2">Kelola tugas harian Anda dengan tampilan modern dan interaktif</p>
                </div>
                <button id="openCreateModal" class="bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-6 rounded-xl flex items-center gap-2 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Tugas</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Tugas</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?= $total_tasks ?></h3>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <i class="fas fa-tasks text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Selesai</p>
                        <h3 class="text-2xl font-bold text-green-600"><?= $completed_tasks ?></h3>
                    </div>
                    <div class="p-3 bg-green-100 rounded-xl">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Dalam Proses</p>
                        <h3 class="text-2xl font-bold text-blue-600"><?= $in_progress_tasks ?></h3>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <i class="fas fa-spinner text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Pending</p>
                        <h3 class="text-2xl font-bold text-yellow-600"><?= $pending_tasks ?></h3>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-xl">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold text-gray-800">Progress Keseluruhan</h3>
                <span id="progress-percentage" class="text-sm font-medium text-primary"><?= $progress_percentage ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div id="animated-progress-bar" class="progress-bar bg-gradient-to-r from-primary to-dark-blue h-4 rounded-full"
                    style="width: <?= $progress_percentage ?>%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-600 mt-2">
                <span><?= $completed_tasks ?> selesai</span>
                <span><?= $total_tasks - $completed_tasks ?> tersisa</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex flex-col lg:flex-row gap-4">

                <div class="flex-grow">
                    <form action="" method="post" class="flex gap-2">
                        <input type="text" name="keyword" autofocus placeholder="Cari tugas berdasarkan judul atau deskripsi..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200">
                        <button type="submit" name="cari" class="bg-primary hover:bg-primary-dark text-white font-medium py-3 px-6 rounded-xl flex items-center gap-2 transition-colors duration-200 whitespace-nowrap">
                            <i class="fas fa-search"></i>
                            <span class="hidden sm:inline">Cari</span>
                        </button>
                    </form>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <form action="" method="get" class="flex gap-2">

                        <select name="status" onchange="this.form.submit()" class="px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="progress" <?= $status_filter === 'progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="overdue" <?= $status_filter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                            <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>

                        <select name="priority" onchange="this.form.submit()" class="px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200">
                            <option value="">Semua Prioritas</option>
                            <option value="rendah" <?= $priority_filter === 'rendah' ? 'selected' : '' ?>>Rendah</option>
                            <option value="sedang" <?= $priority_filter === 'sedang' ? 'selected' : '' ?>>Sedang</option>
                            <option value="tinggi" <?= $priority_filter === 'tinggi' ? 'selected' : '' ?>>Tinggi</option>
                        </select>

                        <select name="sort" onchange="this.form.submit()" class="px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200">
                            <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Sort by: Tanggal Dibuat</option>
                            <option value="due_date" <?= $sort_by === 'due_date' ? 'selected' : '' ?>>Sort by: Deadline</option>
                            <option value="priority" <?= $sort_by === 'priority' ? 'selected' : '' ?>>Sort by: Prioritas</option>
                        </select>

                        <input type="hidden" name="order" value="<?= $sort_order === 'DESC' ? 'ASC' : 'DESC' ?>">
                        <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-3 px-4 rounded-xl flex items-center gap-2 transition-colors duration-200 whitespace-nowrap">
                            <i class="fas fa-sort-amount-<?= $sort_order === 'DESC' ? 'down' : 'up' ?>"></i>
                            <span class="hidden sm:inline"><?= $sort_order === 'DESC' ? 'DESC' : 'ASC' ?></span>
                        </button>
                    </form>

                    <a href="?" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-3 px-4 rounded-xl flex items-center gap-2 transition-colors duration-200 whitespace-nowrap">
                        <i class="fas fa-times"></i>
                        <span class="hidden sm:inline">Reset</span>
                    </a>
                </div>
            </div>
        </div>

        <?php if ($overdue_tasks > 0 || $today_tasks > 0): ?>
            <div class="mb-6 space-y-3">
                <?php if ($overdue_tasks > 0): ?>
                    <div class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-center gap-3">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-red-800">Deadline Terlewat!</h4>
                            <p class="text-red-600 text-sm">Anda memiliki <?= $overdue_tasks ?> tugas yang sudah melewati deadline</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($today_tasks > 0): ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4 flex items-center gap-3">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-yellow-800">Deadline Hari Ini!</h4>
                            <p class="text-yellow-600 text-sm">Anda memiliki <?= $today_tasks ?> tugas dengan deadline hari ini</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quick Action</th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Dibuat</th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioritas</th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($data as $baris) { ?>
                            <?php
                            $statusClass = 'bg-yellow-100 text-yellow-800';
                            $statusIcon = 'fas fa-clock';

                            if ($baris["status"] === 'in progress' || $baris["status"] === 'progress') {
                                $statusClass = 'bg-blue-100 text-blue-800';
                                $statusIcon = 'fas fa-spinner';
                            } elseif ($baris["status"] === 'selesai' || $baris["status"] === 'done') {
                                $statusClass = 'bg-green-100 text-green-800';
                                $statusIcon = 'fas fa-check-circle';
                            } elseif ($baris["status"] === 'overdue') {
                                $statusClass = 'status-overdue';
                                $statusIcon = 'fas fa-exclamation-triangle';
                            }

                            $priorityClass = 'bg-blue-100 text-blue-800';
                            if ($baris["priority"] === 'sedang') {
                                $priorityClass = 'bg-orange-100 text-orange-800';
                            } elseif ($baris["priority"] === 'tinggi') {
                                $priorityClass = 'bg-red-100 text-red-800';
                            }

                            $due_date = strtotime($baris["due_date"]);
                            $today = strtotime(date('Y-m-d'));
                            $deadlineClass = 'deadline-normal';
                            $deadlineIcon = 'far fa-calendar';
                            $deadlineText = 'text-gray-600';

                            if ($baris["status"] === 'overdue') {
                                $deadlineClass = 'deadline-overdue';
                                $deadlineIcon = 'fas fa-exclamation-triangle';
                                $deadlineText = 'text-red-600 font-semibold';
                            } elseif ($due_date == $today && ($baris["status"] !== 'selesai' && $baris["status"] !== 'done')) {
                                $deadlineClass = 'deadline-today';
                                $deadlineIcon = 'fas fa-clock';
                                $deadlineText = 'text-yellow-600 font-semibold';
                            } elseif ($baris["status"] === 'selesai' || $baris["status"] === 'done') {
                                $deadlineClass = 'deadline-normal';
                                $deadlineIcon = 'fas fa-check';
                                $deadlineText = 'text-green-600';
                            }
                            ?>
                            <tr class="task-row bg-white hover:bg-gray-50 transition-all duration-150 <?= $deadlineClass ?>" id="task-<?= $baris["id"] ?>">
                                <td class="py-4 px-6">
                                    <?php if ($baris["status"] !== 'selesai' && $baris["status"] !== 'done'): ?>
                                        <button class="complete-btn bg-success hover:bg-green-600 text-white text-sm font-medium py-2 px-3 rounded-lg flex items-center gap-1 transition-colors duration-200"
                                            data-id="<?= $baris["id"] ?>">
                                            <i class="fas fa-check text-xs"></i>
                                            <span class="hidden sm:inline">Selesai</span>
                                        </button>
                                    <?php else: ?>
                                        <div class="flex items-center gap-2">
                                            <div class="custom-checkbox checked"></div>
                                            <span class="text-xs text-gray-500">Selesai</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 font-medium text-gray-900"><?= htmlspecialchars($baris["title"]) ?></td>
                                <td class="py-4 px-6 text-gray-700 max-w-xs truncate"><?= htmlspecialchars($baris["description"]) ?></td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                                        <i class="<?= $statusIcon ?>"></i>
                                        <?= htmlspecialchars($baris["status"]) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-gray-700"><?= htmlspecialchars($baris["created_at"]) ?></td>
                                <td class="py-4 px-6 <?= $deadlineText ?>">
                                    <div class="flex items-center gap-1">
                                        <i class="<?= $deadlineIcon ?>"></i>
                                        <?= htmlspecialchars($baris["due_date"]) ?>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $priorityClass ?>">
                                        <i class="fas fa-flag mr-1"></i>
                                        <?= htmlspecialchars($baris["priority"]) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-wrap gap-2">
                                        <button class="edit-btn bg-primary hover:bg-primary-dark text-white text-sm font-medium py-2 px-4 rounded-lg flex items-center gap-1 transition-colors duration-200"
                                            data-id="<?= $baris["id"] ?>"
                                            data-judul="<?= htmlspecialchars($baris["title"], ENT_QUOTES) ?>"
                                            data-deskripsi="<?= htmlspecialchars($baris["description"], ENT_QUOTES) ?>"
                                            data-status="<?= htmlspecialchars($baris["status"], ENT_QUOTES) ?>"
                                            data-dibuat="<?= htmlspecialchars($baris["created_at"], ENT_QUOTES) ?>"
                                            data-deadline="<?= htmlspecialchars($baris["due_date"], ENT_QUOTES) ?>"
                                            data-prioritas="<?= htmlspecialchars($baris["priority"], ENT_QUOTES) ?>">
                                            <i class="fas fa-edit text-xs"></i>
                                            <span class="hidden sm:inline">Ubah</span>
                                        </button>
                                        <button class="delete-btn bg-danger hover:bg-red-600 text-white text-sm font-medium py-2 px-4 rounded-lg flex items-center gap-1 transition-colors duration-200"
                                            data-id="<?= $baris["id"] ?>"
                                            data-judul="<?= htmlspecialchars($baris["title"], ENT_QUOTES) ?>">
                                            <i class="fas fa-trash text-xs"></i>
                                            <span class="hidden sm:inline">Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-down">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Tambah Tugas Baru</h3>
                    <button id="closeCreateModal" class="text-gray-500 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-gray-600 mt-1">Masukkan detail tugas baru Anda</p>
            </div>

            <form id="createForm" action="tambah.php" method="post" class="p-6">
                <input type="hidden" name="status" value="pending">
                <input type="hidden" name="dibuat" value="<?= date('Y-m-d') ?>">

                <div class="space-y-4">
                    <div>
                        <label for="judul-tambah" class="block text-sm font-medium text-gray-700 mb-1">Judul Tugas</label>
                        <input type="text" name="judul" id="judul-tambah" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200">
                    </div>

                    <div>
                        <label for="deskripsi-tambah" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi-tambah" required rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"></textarea>
                    </div>

                    <div>
                        <label for="deadline-tambah" class="block text-sm font-medium text-gray-700 mb-1">Deadline</label>
                        <input type="text" name="deadline" id="deadline-tambah" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"
                            placeholder="Pilih deadline...">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                        <div class="custom-select">
                            <div class="select-selected" id="prioritas-tambah-selected">
                                <span class="priority-option priority-rendah">
                                    <span class="priority-dot"></span>
                                    Rendah
                                </span>
                            </div>
                            <div class="select-items select-hide" id="prioritas-tambah-options">
                                <div class="priority-option priority-rendah" data-value="rendah">
                                    <span class="priority-dot"></span>
                                    Rendah
                                </div>
                                <div class="priority-option priority-sedang" data-value="sedang">
                                    <span class="priority-dot"></span>
                                    Sedang
                                </div>
                                <div class="priority-option priority-tinggi" data-value="tinggi">
                                    <span class="priority-dot"></span>
                                    Tinggi
                                </div>
                            </div>
                            <input type="hidden" name="prioritas" id="prioritas-tambah" value="rendah" required>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" id="cancelCreate" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-xl transition-colors duration-200">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl flex items-center gap-2 transition-colors duration-200">
                        <i class="fas fa-save"></i>
                        <span>Simpan Tugas</span>
                    </button>
                </div>
            </form>
        </div>
    </div>


    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-down">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Edit Tugas</h3>
                    <button id="closeEditModal" class="text-gray-500 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-gray-600 mt-1">Perbarui detail tugas pilihan Anda</p>
            </div>

            <form id="editForm" action="ubah.php" method="post" class="p-6">
                <input type="hidden" name="id" id="edit-id">
                <input type="hidden" name="status" id="edit-status">
                <input type="hidden" name="dibuat" id="edit-dibuat">

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <input type="text" id="status-display" disabled
                                class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-xl text-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Dibuat</label>
                            <input type="text" id="dibuat-display" disabled
                                class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-xl text-gray-700">
                        </div>
                    </div>

                    <div>
                        <label for="judul-ubah" class="block text-sm font-medium text-gray-700 mb-1">Judul Tugas</label>
                        <input type="text" name="judul" id="judul-ubah" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200">
                    </div>

                    <div>
                        <label for="deskripsi-ubah" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi-ubah" required rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"></textarea>
                    </div>

                    <div>
                        <label for="deadline-ubah" class="block text-sm font-medium text-gray-700 mb-1">Deadline</label>
                        <input type="text" name="deadline" id="deadline-ubah" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"
                            placeholder="Pilih deadline...">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                        <div class="custom-select">
                            <div class="select-selected" id="prioritas-ubah-selected">
                                <span class="priority-option priority-rendah">
                                    <span class="priority-dot"></span>
                                    Rendah
                                </span>
                            </div>
                            <div class="select-items select-hide" id="prioritas-ubah-options">
                                <div class="priority-option priority-rendah" data-value="rendah">
                                    <span class="priority-dot"></span>
                                    Rendah
                                </div>
                                <div class="priority-option priority-sedang" data-value="sedang">
                                    <span class="priority-dot"></span>
                                    Sedang
                                </div>
                                <div class="priority-option priority-tinggi" data-value="tinggi">
                                    <span class="priority-dot"></span>
                                    Tinggi
                                </div>
                            </div>
                            <input type="hidden" name="prioritas" id="prioritas-ubah" value="rendah" required>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" id="cancelEdit" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-xl transition-colors duration-200">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl flex items-center gap-2 transition-colors duration-200">
                        <i class="fas fa-save"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>


    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-down">
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Tugas</h3>
                <p class="text-gray-600 mb-1">Apakah Anda yakin ingin menghapus tugas</p>
                <p class="text-gray-800 font-medium mb-6" id="delete-task-title">"Judul Tugas"?</p>
                <p class="text-red-500 text-sm mb-6">Tindakan ini tidak dapat dibatalkan!</p>

                <div class="flex justify-center gap-3">
                    <button id="cancelDelete" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-xl transition-colors duration-200">
                        Batal
                    </button>
                    <a id="confirmDelete" href="#" class="px-5 py-2.5 bg-danger hover:bg-red-600 text-white font-medium rounded-xl flex items-center gap-2 transition-colors duration-200">
                        <i class="fas fa-trash"></i>
                        <span>Ya, Hapus</span>
                    </a>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr.localize(flatpickr.l10ns.id);

            const deadlineTambah = flatpickr("#deadline-tambah", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "l, j F Y",
                minDate: "today",
                defaultDate: "today",
                animate: true,
                allowInput: true,
                clickOpens: true,
                locale: "id"
            });

            const deadlineUbah = flatpickr("#deadline-ubah", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "l, j F Y",
                minDate: "today",
                animate: true,
                allowInput: true,
                clickOpens: true,
                locale: "id"
            });

            function initCustomSelect(selectedId, optionsId, hiddenInputId) {
                const selected = document.getElementById(selectedId);
                const options = document.getElementById(optionsId);
                const hiddenInput = document.getElementById(hiddenInputId);

                selected.addEventListener('click', function(e) {
                    e.stopPropagation();
                    closeAllSelect(this);
                    options.classList.toggle('select-hide');
                    this.classList.toggle('select-arrow-active');
                });

                const optionItems = options.querySelectorAll('div');
                optionItems.forEach(function(item) {
                    item.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        const displayHtml = this.innerHTML;

                        selected.innerHTML = displayHtml;
                        hiddenInput.value = value;

                        options.classList.add('select-hide');
                        selected.classList.remove('select-arrow-active');
                    });
                });
            }

            function closeAllSelect(elm) {
                const selects = document.getElementsByClassName("select-items");
                const selected = document.getElementsByClassName("select-selected");

                for (let i = 0; i < selected.length; i++) {
                    if (elm !== selected[i]) {
                        selected[i].classList.remove("select-arrow-active");
                    }
                }

                for (let i = 0; i < selects.length; i++) {
                    if (elm !== selects[i]) {
                        selects[i].classList.add("select-hide");
                    }
                }
            }

            document.addEventListener('click', closeAllSelect);

            initCustomSelect('prioritas-tambah-selected', 'prioritas-tambah-options', 'prioritas-tambah');
            initCustomSelect('prioritas-ubah-selected', 'prioritas-ubah-options', 'prioritas-ubah');

            function createConfetti() {
                const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
                for (let i = 0; i < 50; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 2 + 's';
                    document.body.appendChild(confetti);

                    setTimeout(() => {
                        confetti.remove();
                    }, 2000);
                }
            }

            function updateProgressBar(newPercentage) {
                const progressBar = document.getElementById('animated-progress-bar');
                const progressText = document.getElementById('progress-percentage');

                progressBar.style.width = newPercentage + '%';
                progressBar.classList.add('progress-pulse');

                let currentPercentage = parseInt(progressText.textContent);
                const increment = newPercentage > currentPercentage ? 1 : -1;

                const counter = setInterval(() => {
                    currentPercentage += increment;
                    progressText.textContent = currentPercentage + '%';

                    if (currentPercentage === newPercentage) {
                        clearInterval(counter);
                        setTimeout(() => {
                            progressBar.classList.remove('progress-pulse');
                        }, 500);
                    }
                }, 20);
            }

            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');

            const openCreateModalBtn = document.getElementById('openCreateModal');
            const editButtons = document.querySelectorAll('.edit-btn');
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const completeButtons = document.querySelectorAll('.complete-btn');

            const closeCreateModalBtn = document.getElementById('closeCreateModal');
            const closeEditModalBtn = document.getElementById('closeEditModal');
            const cancelCreateBtn = document.getElementById('cancelCreate');
            const cancelEditBtn = document.getElementById('cancelEdit');
            const cancelDeleteBtn = document.getElementById('cancelDelete');

            const createForm = document.getElementById('createForm');
            const editForm = document.getElementById('editForm');

            const deleteTaskTitle = document.getElementById('delete-task-title');
            const confirmDeleteBtn = document.getElementById('confirmDelete');

            openCreateModalBtn.addEventListener('click', () => {
                createModal.classList.remove('hidden');
                setTimeout(() => {
                    createModal.classList.add('fade-in');
                }, 10);
            });

            editButtons.forEach(button => {
                button.addEventListener('click', () => {
                    document.getElementById('edit-id').value = button.dataset.id;
                    document.getElementById('edit-status').value = button.dataset.status;
                    document.getElementById('status-display').value = button.dataset.status;
                    document.getElementById('edit-dibuat').value = button.dataset.dibuat;
                    document.getElementById('dibuat-display').value = button.dataset.dibuat;
                    document.getElementById('judul-ubah').value = button.dataset.judul;
                    document.getElementById('deskripsi-ubah').value = button.dataset.deskripsi;

                    deadlineUbah.setDate(button.dataset.deadline);

                    const priority = button.dataset.prioritas;
                    document.getElementById('prioritas-ubah').value = priority;
                    const priorityClass = `priority-${priority}`;
                    const priorityText = priority === 'rendah' ? 'Rendah' : priority === 'sedang' ? 'Sedang' : 'Tinggi';
                    document.getElementById('prioritas-ubah-selected').innerHTML = `
                        <span class="priority-option ${priorityClass}">
                            <span class="priority-dot"></span>
                            ${priorityText}
                        </span>
                    `;

                    editModal.classList.remove('hidden');
                    setTimeout(() => {
                        editModal.classList.add('fade-in');
                    }, 10);
                });
            });

            deleteButtons.forEach(button => {
                button.addEventListener('click', () => {
                    deleteTaskTitle.textContent = `"${button.dataset.judul}"`;
                    confirmDeleteBtn.href = `hapus.php?id=${button.dataset.id}`;

                    deleteModal.classList.remove('hidden');
                    setTimeout(() => {
                        deleteModal.classList.add('fade-in');
                    }, 10);
                });
            });

            completeButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    const taskId = button.dataset.id;
                    const taskRow = document.getElementById(`task-${taskId}`);

                    const confirmComplete = await showCustomConfirm(
                        'Tandai Selesai',
                        'Apakah Anda yakin ingin menandai tugas ini sebagai selesai?',
                        'success'
                    );

                    if (confirmComplete) {
                        try {
                            taskRow.classList.add('task-complete-animation');

                            const formData = new FormData();
                            formData.append('id', taskId);

                            const response = await fetch('selesai.php', {
                                method: 'POST',
                                body: formData
                            });

                            if (response.ok) {
                                createConfetti();

                                const currentProgress = <?= $progress_percentage ?>;
                                const newProgress = Math.min(100, currentProgress + Math.round((1 / <?= $total_tasks ?>) * 100));
                                updateProgressBar(newProgress);

                                showNotification('Tugas berhasil ditandai selesai!', 'success');
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                showNotification('Terjadi kesalahan saat menandai tugas selesai', 'error');
                            }
                        } catch (error) {
                            showNotification('Terjadi kesalahan jaringan', 'error');
                            console.error(error);
                        }
                    }
                });
            });

            function showCustomConfirm(title, message, type = 'warning') {
                return new Promise((resolve) => {
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 fade-in';
                    modal.innerHTML = `
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-down">
                            <div class="p-6 text-center">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full ${type === 'success' ? 'bg-green-100' : 'bg-yellow-100'} mb-4">
                                    <i class="${type === 'success' ? 'fas fa-check text-green-600' : 'fas fa-exclamation-triangle text-yellow-600'} text-2xl"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">${title}</h3>
                                <p class="text-gray-600 mb-6">${message}</p>
                                <div class="flex justify-center gap-3">
                                    <button id="confirmCancel" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-xl transition-colors duration-200">
                                        Batal
                                    </button>
                                    <button id="confirmOk" class="px-5 py-2.5 ${type === 'success' ? 'bg-green-600 hover:bg-green-700' : 'bg-yellow-600 hover:bg-yellow-700'} text-white font-medium rounded-xl transition-colors duration-200">
                                        Ya, Lanjutkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(modal);

                    modal.querySelector('#confirmOk').addEventListener('click', () => {
                        document.body.removeChild(modal);
                        resolve(true);
                    });

                    modal.querySelector('#confirmCancel').addEventListener('click', () => {
                        document.body.removeChild(modal);
                        resolve(false);
                    });

                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            document.body.removeChild(modal);
                            resolve(false);
                        }
                    });
                });
            }

            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                const bgColor = type === 'success' ? 'bg-green-500' :
                    type === 'error' ? 'bg-red-500' :
                    type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';

                notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-xl shadow-lg z-50 slide-down`;
                notification.innerHTML = `
                    <div class="flex items-center gap-2">
                        <i class="fas ${type === 'success' ? 'fa-check' : 
                                     type === 'error' ? 'fa-times' : 
                                     type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info'}"></i>
                        <span>${message}</span>
                    </div>
                `;

                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }

            const closeCreateModal = () => {
                createModal.classList.add('hidden');
                createModal.classList.remove('fade-in');
            };

            closeCreateModalBtn.addEventListener('click', closeCreateModal);
            cancelCreateBtn.addEventListener('click', closeCreateModal);

            const closeEditModal = () => {
                editModal.classList.add('hidden');
                editModal.classList.remove('fade-in');
            };

            closeEditModalBtn.addEventListener('click', closeEditModal);
            cancelEditBtn.addEventListener('click', closeEditModal);

            const closeDeleteModal = () => {
                deleteModal.classList.add('hidden');
                deleteModal.classList.remove('fade-in');
            };

            cancelDeleteBtn.addEventListener('click', closeDeleteModal);

            [createModal, editModal, deleteModal].forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('fade-in');
                    }
                });
            });

            createForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(createForm);

                try {
                    const response = await fetch(createForm.action, {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        closeCreateModal();
                        showNotification('Tugas berhasil ditambahkan!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification('Terjadi kesalahan saat menambahkan tugas', 'error');
                    }
                } catch (error) {
                    showNotification('Terjadi kesalahan jaringan', 'error');
                    console.error(error);
                }
            });

            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(editForm);

                try {
                    const response = await fetch(editForm.action, {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        closeEditModal();
                        showNotification('Tugas berhasil diubah!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification('Terjadi kesalahan saat mengubah tugas', 'error');
                    }
                } catch (error) {
                    showNotification('Terjadi kesalahan jaringan', 'error');
                    console.error(error);
                }
            });
        });
    </script>
</body>

</html>