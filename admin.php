<?php
require_once 'includes/db.php';
require_once 'backend/holidays.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function formatTime12h($timeStr)
{
    if (!$timeStr)
        return '';
    $time = explode(':', $timeStr);
    $h = (int) $time[0];
    $m = $time[1];
    $ampm = $h >= 12 ? 'PM' : 'AM';
    $h = $h % 12;
    $h = $h ? $h : 12;
    return "$h:$m $ampm";
}

$is_logged_in = isset($_SESSION['user_id']);
$current_view = $_GET['view'] ?? 'dashboard';

// Login handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role='admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php?view=dashboard");
        exit;
    } else {
        $login_error = "Invalid restricted credentials";
    }
}

// Data fetching based on view
if ($is_logged_in) {

    // Shared actions like refund
    if (isset($_GET['action']) && $_GET['action'] == 'refund' && isset($_GET['booking_id'])) {
        $bid = $_GET['booking_id'];
        $pdo->prepare("UPDATE bookings SET status = 'refunded' WHERE id = ? AND status = 'paid'")->execute([$bid]);
        $_SESSION['swal_msg'] = ['title' => 'Refunded!', 'text' => 'Booking was successfully refunded.', 'icon' => 'success'];
        header("Location: admin.php?view=bookings");
        exit;
    }

    // Delete Attraction
    if (isset($_GET['action']) && $_GET['action'] == 'delete_attraction' && isset($_GET['id'])) {
        $aid = $_GET['id'];
        $pdo->prepare("DELETE FROM attractions WHERE id = ?")->execute([$aid]);
        $_SESSION['swal_msg'] = ['title' => 'Deleted!', 'text' => 'Attraction has been removed.', 'icon' => 'success'];
        header("Location: admin.php?view=attractions");
        exit;
    }

    // Approve Booking
    if (isset($_GET['action']) && $_GET['action'] == 'approve' && isset($_GET['booking_id'])) {
        $bid = $_GET['booking_id'];
        $pdo->prepare("UPDATE bookings SET status = 'paid' WHERE id = ? AND status = 'pending'")->execute([$bid]);
        $_SESSION['swal_msg'] = ['title' => 'Approved!', 'text' => 'Booking status updated to Paid.', 'icon' => 'success'];
        header("Location: admin.php?view=bookings");
        exit;
    }

    // Reject Booking
    if (isset($_GET['action']) && $_GET['action'] == 'reject' && isset($_GET['booking_id'])) {
        $bid = $_GET['booking_id'];
        $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ? AND status = 'pending'")->execute([$bid]);
        $_SESSION['swal_msg'] = ['title' => 'Rejected!', 'text' => 'Booking has been rejected.', 'icon' => 'info'];
        header("Location: admin.php?view=bookings");
        exit;
    }

    // Update Attraction — MUST check for valid numeric ID to prevent accidental firing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attraction'])) {
        $aid = $_POST['id'] ?? '';

        // Guard: only proceed if we have a valid numeric ID
        if (empty($aid) || !is_numeric($aid)) {
            $_SESSION['swal_msg'] = ['title' => 'Error!', 'text' => 'Invalid attraction ID.', 'icon' => 'error'];
            header("Location: admin.php?view=attractions");
            exit;
        }

        $aid = intval($aid);
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $loc = $_POST['location'];
        $video = $_POST['video_url'] ?? '';
        $price_senior = floatval($_POST['price_senior']);
        $price_adult = floatval($_POST['price_adult']);
        $price_child = floatval($_POST['price_child']);
        $capacity = intval($_POST['max_capacity']);
        $morning_start = $_POST['morning_start'];
        $morning_end = $_POST['morning_end'];
        $afternoon_start = $_POST['afternoon_start'];
        $afternoon_end = $_POST['afternoon_end'];

        try {
            $pdo->beginTransaction();

            // Handle Images (If any new ones ARE provided, we REPLACE the current set)
            $new_images = [];
            if (isset($_FILES['attraction_images']) && is_array($_FILES['attraction_images']['name'])) {
                foreach ($_FILES['attraction_images']['name'] as $i => $img_name) {
                    if ($_FILES['attraction_images']['error'][$i] == 0) {
                        $target_dir = "uploads/";
                        if (!is_dir($target_dir))
                            mkdir($target_dir, 0755, true);
                        $file_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
                        $file_name = bin2hex(random_bytes(8)) . "." . $file_ext;
                        $target_file = $target_dir . $file_name;
                        if (move_uploaded_file($_FILES['attraction_images']['tmp_name'][$i], $target_file)) {
                            $new_images[] = $target_file;
                        }
                    }
                }
            }

            if (!empty($new_images)) {
                // Remove old images from DB
                $pdo->prepare("DELETE FROM attraction_images WHERE attraction_id = ?")->execute([$aid]);
                // Insert new ones
                foreach ($new_images as $img_path) {
                    $pdo->prepare("INSERT INTO attraction_images (attraction_id, image_url) VALUES (?, ?)")->execute([$aid, $img_path]);
                }
                // Update primary image in attractions table
                $pdo->prepare("UPDATE attractions SET image_url = ? WHERE id = ?")->execute([$new_images[0], $aid]);
            }

            // Update core attraction fields — lat/lng removed
            $stmt = $pdo->prepare("UPDATE attractions SET name = ?, description = ?, location = ?, video_url = ? WHERE id = ?");
            $stmt->execute([$name, $desc, $loc, $video, $aid]);

            // Update Time Slots
            $pdo->prepare("UPDATE time_slots SET start_time = ?, end_time = ?, max_capacity = ? WHERE attraction_id = ? AND slot_name LIKE '%Morning%'")->execute([$morning_start, $morning_end, $capacity, $aid]);
            $pdo->prepare("UPDATE time_slots SET start_time = ?, end_time = ?, max_capacity = ? WHERE attraction_id = ? AND slot_name LIKE '%Afternoon%'")->execute([$afternoon_start, $afternoon_end, $capacity, $aid]);

            // Update Base Prices
            $pdo->prepare("UPDATE ticket_types SET base_price = ? WHERE attraction_id = ? AND name = 'Adult'")->execute([$price_adult, $aid]);
            $pdo->prepare("UPDATE ticket_types SET base_price = ? WHERE attraction_id = ? AND name = 'Child'")->execute([$price_child, $aid]);
            $pdo->prepare("UPDATE ticket_types SET base_price = ? WHERE attraction_id = ? AND name = 'Senior'")->execute([$price_senior, $aid]);

            $pdo->commit();
            $_SESSION['swal_msg'] = ['title' => 'Updated!', 'text' => 'Attraction details saved.', 'icon' => 'success'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['swal_msg'] = ['title' => 'Error!', 'text' => 'Update failed: ' . $e->getMessage(), 'icon' => 'error'];
        }
        header("Location: admin.php?view=attractions");
        exit;
    }

    if ($current_view === 'attractions') {

        // Creating new attraction
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_attraction'])) {
            $name = $_POST['name'];
            $desc = $_POST['description'];
            $loc = $_POST['location'];
            $morning_start = $_POST['morning_start'] ?: '07:00:00';
            $morning_end = $_POST['morning_end'] ?: '12:00:00';
            $afternoon_start = $_POST['afternoon_start'] ?: '13:00:00';
            $afternoon_end = $_POST['afternoon_end'] ?: '17:00:00';
            $price_adult = floatval($_POST['price_adult']);
            $price_child = floatval($_POST['price_child']);
            $price_senior = floatval($_POST['price_senior']);
            $capacity = intval($_POST['max_capacity']);
            $video = $_POST['video_url'] ?? '';

            $images = [];
            if (isset($_FILES['attraction_images']) && is_array($_FILES['attraction_images']['name'])) {
                foreach ($_FILES['attraction_images']['name'] as $i => $img_name) {
                    if ($_FILES['attraction_images']['error'][$i] == 0) {
                        $target_dir = "uploads/";
                        if (!is_dir($target_dir))
                            mkdir($target_dir, 0755, true);
                        $file_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
                        $file_name = bin2hex(random_bytes(8)) . "." . $file_ext;
                        $target_file = $target_dir . $file_name;
                        if (move_uploaded_file($_FILES['attraction_images']['tmp_name'][$i], $target_file)) {
                            $images[] = $target_file;
                        }
                    }
                }
            }

            if (empty($images)) {
                $_SESSION['swal_msg'] = ['title' => 'Error!', 'text' => 'Please upload at least one image.', 'icon' => 'error'];
                header("Location: admin.php?view=attractions");
                exit;
            }

            try {
                $pdo->beginTransaction();

                // 1. Insert Attraction — lat/lng removed
                $stmt = $pdo->prepare("INSERT INTO attractions (name, description, location, image_url, video_url, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$name, $desc, $loc, $images[0], $video]);
                $new_id = $pdo->lastInsertId();

                // 2. Insert all images into gallery
                foreach ($images as $img_path) {
                    $pdo->prepare("INSERT INTO attraction_images (attraction_id, image_url) VALUES (?, ?)")->execute([$new_id, $img_path]);
                }

                // 3. Insert Base Tickets
                $stmt = $pdo->prepare("INSERT INTO ticket_types (attraction_id, name, base_price, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$new_id, 'Adult', $price_adult, 'Standard admission ticket']);
                $stmt->execute([$new_id, 'Child', $price_child, 'Children under 12']);
                $stmt->execute([$new_id, 'Senior', $price_senior, 'Seniors 60+']);

                // 4. Insert Base Slots
                $stmt = $pdo->prepare("INSERT INTO time_slots (attraction_id, slot_name, start_time, end_time, max_capacity) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$new_id, 'Morning', $morning_start, $morning_end, $capacity]);
                $stmt->execute([$new_id, 'Afternoon', $afternoon_start, $afternoon_end, $capacity]);

                $pdo->commit();
                $_SESSION['swal_msg'] = ['title' => 'Created!', 'text' => 'The attraction was added successfully.', 'icon' => 'success'];
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['swal_msg'] = ['title' => 'Error!', 'text' => 'Failed to add attraction: ' . $e->getMessage(), 'icon' => 'error'];
            }
            header("Location: admin.php?view=attractions");
            exit;
        }

        // Fetch attractions with their base prices
        $stmt = $pdo->prepare("
            SELECT a.*,
                MAX(CASE WHEN tt.name = 'Adult' THEN tt.base_price END) as price_adult,
                MAX(CASE WHEN tt.name = 'Child' THEN tt.base_price END) as price_child,
                MAX(CASE WHEN tt.name = 'Senior' THEN tt.base_price END) as price_senior
            FROM attractions a
            LEFT JOIN ticket_types tt ON a.id = tt.attraction_id
            GROUP BY a.id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute();
        $attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all images and slots for each attraction
        foreach ($attractions as &$a) {
            $img_stmt = $pdo->prepare("SELECT image_url FROM attraction_images WHERE attraction_id = ?");
            $img_stmt->execute([$a['id']]);
            $a['images'] = $img_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [$a['image_url']];

            $slot_stmt = $pdo->prepare("SELECT slot_name, start_time, end_time, max_capacity FROM time_slots WHERE attraction_id = ?");
            $slot_stmt->execute([$a['id']]);
            $slots = $slot_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($slots as $s) {
                if (stripos($s['slot_name'], 'Morning') !== false) {
                    $a['morning_start'] = $s['start_time'];
                    $a['morning_end'] = $s['end_time'];
                    $a['max_capacity'] = $s['max_capacity'];
                } else {
                    $a['afternoon_start'] = $s['start_time'];
                    $a['afternoon_end'] = $s['end_time'];
                }
            }
        }
        unset($a); // Break reference after foreach
    }

    if ($current_view === 'bookings') {
        $stmt = $pdo->prepare("SELECT b.*, s.slot_name, a.name as attraction_name FROM bookings b JOIN time_slots s ON b.time_slot_id = s.id JOIN attractions a ON s.attraction_id = a.id ORDER BY b.created_at DESC");
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($current_view === 'dashboard') {
        $stmt = $pdo->prepare("SELECT b.*, s.slot_name FROM bookings b JOIN time_slots s ON b.time_slot_id = s.id WHERE b.status = 'paid'");
        $stmt->execute();
        $paid_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today = date('Y-m-d');
        $morning = 0;
        $afternoon = 0;
        foreach ($paid_bookings as $b) {
            if ($b['visit_date'] === $today) {
                if (stripos($b['slot_name'], 'Morning') !== false)
                    $morning++;
                else
                    $afternoon++;
            }
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets t JOIN bookings b ON t.booking_id = b.id WHERE b.visit_date = ? AND b.status = 'paid'");
        $stmt->execute([$today]);
        $total_expected = $stmt->fetchColumn() ?: 0;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets t JOIN bookings b ON t.booking_id = b.id WHERE b.visit_date = ? AND b.status = 'paid' AND t.is_scanned = 1");
        $stmt->execute([$today]);
        $total_checked_in = $stmt->fetchColumn() ?: 0;

        $month = isset($_GET['cal_month']) ? intval($_GET['cal_month']) : intval(date('n'));
        $year = isset($_GET['cal_year']) ? intval($_GET['cal_year']) : intval(date('Y'));
        // Clamp month
        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1; $year++; }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $firstDay = date('w', mktime(0, 0, 0, $month, 1, $year));

        $stmt = $pdo->prepare("
            SELECT b.visit_date, COUNT(*) as ticket_count
            FROM tickets t
            JOIN bookings b ON t.booking_id = b.id
            WHERE MONTH(b.visit_date) = ? AND YEAR(b.visit_date) = ? AND b.status = 'paid'
            GROUP BY b.visit_date
        ");
        $stmt->execute([$month, $year]);
        $daily_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    }
}

require 'includes/header.php';
?>

<?php if (!$is_logged_in): ?>
    <div class="container main-content-wrapper">
        <div style="max-width: 400px; margin: 4rem auto;">
            <div class="card animate-fade-in">
                <h2 class="card-title text-center" style="text-align:center;">Admin Login</h2>
                <?php if (isset($login_error)): ?>
                    <div
                        style="background: #fef2f2; border: 1px solid #ef4444; color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="admin.php">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">Access Secure
                        Portal</button>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="container-fluid main-content-wrapper" style="padding: 2.5rem 3rem; width: 100%; box-sizing: border-box;">

        <div
            style="margin-bottom: 2.5rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h1 style="margin:0; text-transform: capitalize; font-size: 2.2rem;">
                <?php echo htmlspecialchars($current_view); ?> Portal</h1>
            <?php if ($current_view === 'attractions'): ?>
                <button onclick="document.getElementById('addAttractionModal').style.display='flex'" class="btn btn-primary"
                    style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 700;">
                    <i class="fa-solid fa-plus"></i> Add New Attraction
                </button>
            <?php endif; ?>
        </div>

        <?php if ($current_view === 'dashboard'): ?>
            <div class="booking-container" style="grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 2rem;">
                <div class="card" style="margin-bottom:0px;">
                    <h2 class="card-title"><i class="fa-solid fa-qrcode"></i> Entry Validator</h2>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="qrInput" placeholder="Scan or enter QR...">
                        <button class="btn btn-primary" onclick="validateQR()">Verify</button>
                    </div>
                    <div id="validationResult"
                        style="margin-top: 1.5rem; display: none; padding: 1.5rem; border-radius: 0.5rem; text-align: center;">
                    </div>
                </div>

                <div class="card" style="margin-bottom:0px;">
                    <h2 class="card-title"><i class="fa-solid fa-chart-pie"></i> Today's Check-ins</h2>
                    <div style="margin-bottom: 2rem; text-align: center;">
                        <div style="font-size: 3rem; font-weight: 800; color: var(--primary);"><?php echo $total_checked_in; ?>
                            <span style="font-size: 1.5rem; color: var(--text-muted);">/ <?php echo $total_expected; ?></span>
                        </div>
                        <div
                            style="text-transform: uppercase; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); letter-spacing: 1px;">
                            Expected Visitors Arrived</div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <div>Morning Slots Booking: <?php echo $morning; ?></div>
                        <div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow:hidden;">
                            <div
                                style="width: <?php echo min(100, ($morning / 500) * 100); ?>%; height: 100%; background: var(--primary);">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div>Afternoon Slots Booking: <?php echo $afternoon; ?></div>
                        <div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow:hidden;">
                            <div
                                style="width: <?php echo min(100, ($afternoon / 500) * 100); ?>%; height: 100%; background: var(--accent);">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <?php
                $prevMonth = $month - 1;
                $prevYear = $year;
                if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
                $nextMonth = $month + 1;
                $nextYear = $year;
                if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
                $prevUrl = "admin.php?view=dashboard&cal_month={$prevMonth}&cal_year={$prevYear}";
                $nextUrl = "admin.php?view=dashboard&cal_month={$nextMonth}&cal_year={$nextYear}";
                $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
                ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <a href="<?php echo $prevUrl; ?>" class="btn btn-ghost btn-sm" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--border);">
                        <i class="fa-solid fa-chevron-left"></i> Prev
                    </a>
                    <h2 style="margin: 0; font-size: 1.25rem;">
                        <i class="fa-solid fa-calendar-days"></i> Monthly Capacity Overview
                        <span style="color: var(--primary);">(<?php echo $monthName; ?>)</span>
                    </h2>
                    <a href="<?php echo $nextUrl; ?>" class="btn btn-ghost btn-sm" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--border);">
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; text-align: center;">
                    <?php
                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    foreach ($days as $d)
                        echo "<div style='font-weight: bold; font-size: 0.8rem; opacity: 0.6;'>$d</div>";
                    for ($i = 0; $i < $firstDay; $i++)
                        echo "<div></div>";
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $count = $daily_counts[$dateStr] ?? 0;
                        $holiday_name = isPhilippineHoliday($dateStr);

                        if ($holiday_name) {
                            $bg = '#fff1f2';
                            $color = '#e11d48';
                            $border = '2px solid #e11d48';
                            $label = $holiday_name;
                            $shortName = htmlspecialchars($holiday_name);
                            $badge = "<small style='font-size:0.5rem; font-weight:700; color:#e11d48; margin-top:1px; line-height:1.1; padding: 0 2px; word-break:break-word; max-width:100%;'>{$shortName}</small>";
                        } elseif ($count === 0) {
                            $bg = '#f3f4f6';
                            $color = 'inherit';
                            $border = '1px solid var(--border)';
                            $label = 'No bookings';
                            $badge = '';
                        } elseif ($count >= 1000) {
                            $bg = '#fee2e2';
                            $color = '#991b1b';
                            $border = '1px solid var(--border)';
                            $label = 'Sold Out';
                            $badge = '';
                        } elseif ($count >= 700) {
                            $bg = '#ffedd5';
                            $color = '#9a3412';
                            $border = '1px solid var(--border)';
                            $label = 'Busy';
                            $badge = '';
                        } else {
                            $bg = '#dcfce7';
                            $color = '#166534';
                            $border = '1px solid var(--border)';
                            $label = 'Available';
                            $badge = '';
                        }

                        echo "<div title='$label ($dateStr)' style='height: 60px; background: $bg; border-radius: 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 600; color: $color; border: $border; cursor: default;'>
                            <span>$day</span>
                            $badge
                          </div>";
                    }
                    ?>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1rem; font-size: 0.75rem; justify-content: center; flex-wrap: wrap;">
                    <span style="display: flex; align-items: center; gap: 4px;">
                        <div style="width: 12px; height: 12px; background: #dcfce7; border-radius: 2px;"></div> Available
                    </span>
                    <span style="display: flex; align-items: center; gap: 4px;">
                        <div style="width: 12px; height: 12px; background: #ffedd5; border-radius: 2px;"></div> Busy (70%+)
                    </span>
                    <span style="display: flex; align-items: center; gap: 4px;">
                        <div style="width: 12px; height: 12px; background: #fee2e2; border-radius: 2px;"></div> Sold Out
                    </span>
                    <span style="display: flex; align-items: center; gap: 4px;">
                        <div style="width: 12px; height: 12px; background: #fff1f2; border-radius: 2px; border: 2px solid #e11d48;"></div> Holiday
                    </span>
                </div>
            </div>

            <script>
                async function validateQR() {
                    const qr = document.getElementById('qrInput').value;
                    if (!qr) return;
                    try {
                        const res = await fetch(`backend/api_bookings.php?action=validate&qr=${encodeURIComponent(qr)}`);
                        const data = await res.json();
                        const vr = document.getElementById('validationResult');
                        vr.style.display = 'block';
                        if (data.success) {
                            vr.style.background = '#ecfdf5';
                            vr.style.border = '2px solid #10b981';
                            vr.style.color = '#065f46';
                            vr.innerHTML = `<i class="fa-solid fa-circle-check" style="font-size: 2rem;"></i>
                                    <h3 style="margin: 0.5rem 0;">GRANTED</h3>
                                    <p>${data.message} - ${data.visitor} (${data.type})</p>`;
                            document.getElementById('qrInput').value = '';
                        } else {
                            vr.style.background = '#fef2f2';
                            vr.style.border = '2px solid #ef4444';
                            vr.style.color = '#991b1b';
                            vr.innerHTML = `<i class="fa-solid fa-circle-xmark" style="font-size: 2rem;"></i>
                                    <h3 style="margin: 0.5rem 0;">DENIED</h3>
                                    <p>${data.message}</p>`;
                        }
                    } catch (e) { Swal.fire('Error', 'Connection Error', 'error'); }
                }
            </script>
        <?php endif; ?>

        <?php if ($current_view === 'attractions'): ?>
            <!-- Add Attraction Modal -->
            <div id="addAttractionModal" class="modal-overlay">
                <div class="modal-box animate-fade-in">
                    <div class="modal-header">
                        <h2 class="card-title" style="margin:0;"><i class="fa-solid fa-map-location-dot"></i> Establish New
                            Attraction</h2>
                        <button class="modal-close-btn"
                            onclick="document.getElementById('addAttractionModal').style.display='none'"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="admin.php?view=attractions" enctype="multipart/form-data">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label>Attraction Name</label>
                                    <input type="text" name="name" required placeholder="e.g. Kyoto Gardens">
                                </div>
                                <div class="form-group">
                                    <label>Location</label>
                                    <input type="text" name="location" required placeholder="e.g. Japan">
                                </div>
                                <div class="form-group" style="grid-column: 1/-1;">
                                    <label>Description</label>
                                    <textarea name="description" rows="3" required
                                        placeholder="Highlight the experience..."></textarea>
                                </div>
                                <div class="form-group" style="grid-column: 1/-1;">
                                    <label>Attraction Images (Select multiple)</label>
                                    <input type="file" name="attraction_images[]" multiple required
                                        accept=".webp,.jpg,.jpeg,.png,image/*" class="form-control-file">
                                    <small style="color:var(--text-muted); display: block; margin-top: 5px;">Supported: WebP,
                                        JPEG, PNG. You can select multiple images at once.</small>
                                </div>
                                <div class="form-group">
                                    <label>Morning Start</label>
                                    <input type="time" name="morning_start" required value="07:00">
                                </div>
                                <div class="form-group">
                                    <label>Morning End</label>
                                    <input type="time" name="morning_end" required value="12:00">
                                </div>
                                <div class="form-group">
                                    <label>Afternoon Start</label>
                                    <input type="time" name="afternoon_start" required value="13:00">
                                </div>
                                <div class="form-group">
                                    <label>Afternoon End</label>
                                    <input type="time" name="afternoon_end" required value="17:00">
                                </div>
                                <div class="form-group">
                                    <label>Video URL (Optional)</label>
                                    <input type="url" name="video_url" placeholder="https://youtube.com/watch?v=...">
                                </div>
                                <div class="form-group">
                                    <label>Adult Price (₱)</label>
                                    <input type="number" step="0.01" name="price_adult" required placeholder="100.00">
                                </div>
                                <div class="form-group">
                                    <label>Child Price (₱)</label>
                                    <input type="number" step="0.01" name="price_child" required placeholder="50.00">
                                </div>
                                <div class="form-group">
                                    <label>Senior Price (₱)</label>
                                    <input type="number" step="0.01" name="price_senior" required placeholder="75.00">
                                </div>
                                <div class="form-group">
                                    <label>Slot Capacity</label>
                                    <input type="number" name="max_capacity" required value="500">
                                </div>
                                <div class="form-group" style="grid-column: 1/-1;">
                                    <small style="color:var(--text-muted); display:block;">Manually set 'Adult', 'Child', and
                                        'Senior' ticket prices with standard Morning/Afternoon slots.</small>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                                <button type="button" class="btn btn-secondary"
                                    onclick="document.getElementById('addAttractionModal').style.display='none'">Cancel</button>
                                <button type="submit" name="create_attraction" class="btn btn-primary"><i
                                        class="fa-solid fa-plus"></i> Open for Bookings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 0; background: transparent; border: none; box-shadow: none; padding: 0;">
                <h2 class="card-title" style="border:none; margin-bottom: 1.5rem;"><i class="fa-solid fa-map-location-dot"></i>
                    Manage Tourist Spots</h2>
                <div class="attraction-grid">
                    <?php foreach ($attractions as $a): ?>
                        <div class="attraction-card" id="card-<?php echo $a['id']; ?>">
                            <span class="attraction-status-badge">Active</span>

                            <!-- Carousel -->
                            <div class="carousel-container"
                                style="position: relative; overflow: hidden; aspect-ratio: 16/9; background: #0f172a;">
                                <div class="carousel-slides"
                                    style="display: flex; transition: transform 0.4s ease-out; height: 100%;">
                                    <?php foreach ($a['images'] as $img): ?>
                                        <img src="<?php echo htmlspecialchars($img); ?>" class="attraction-card-image"
                                            style="flex: 0 0 100%; border-bottom: none;">
                                    <?php endforeach; ?>
                                </div>

                                <?php if (count($a['images']) > 1): ?>
                                    <button class="carousel-btn carousel-prev" onclick="moveCarousel('<?php echo $a['id']; ?>', -1)"><i
                                            class="fa-solid fa-chevron-left"></i></button>
                                    <button class="carousel-btn carousel-next" onclick="moveCarousel('<?php echo $a['id']; ?>', 1)"><i
                                            class="fa-solid fa-chevron-right"></i></button>
                                    <div class="carousel-dots">
                                        <?php foreach ($a['images'] as $i => $img): ?>
                                            <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>"
                                                onclick="setCarousel('<?php echo $a['id']; ?>', <?php echo $i; ?>)"></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="attraction-card-content">
                                <div class="attraction-card-title">
                                    <span><?php echo htmlspecialchars($a['name']); ?></span>
                                </div>
                                <div class="attraction-card-location">
                                    <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($a['location']); ?>
                                </div>
                                <div class="attraction-card-stats">
                                    <span><i class="fa-solid fa-ticket"></i>
                                        ₱<?php echo number_format($a['price_adult'], 2); ?></span>
                                    <span style="text-align: right;"><i class="fa-solid fa-clock"></i>
                                        <?php echo formatTime12h($a['morning_start'] ?? '08:00'); ?> -
                                        <?php echo formatTime12h($a['afternoon_end'] ?? '17:00'); ?></span>
                                </div>
                            </div>
                            <div class="attraction-card-actions">
                                <button onclick='openEditModal(<?php echo json_encode($a); ?>)' class="btn btn-sm btn-secondary"
                                    style="flex: 1; background: #e0e7ff; color: #4338ca;"><i class="fa-solid fa-pen-to-square"></i>
                                    Edit</button>
                                <button onclick="confirmDeleteAttraction('<?php echo $a['id']; ?>')"
                                    class="btn btn-sm btn-secondary" style="flex: 1; background: #fee2e2; color: #b91c1c;"><i
                                        class="fa-solid fa-trash"></i> Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($attractions)): ?>
                    <div
                        style="text-align: center; padding: 4rem; background: white; border-radius: 1rem; border: 1px dashed var(--border);">
                        <i class="fa-solid fa-map-pin" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-muted);">No attractions published yet. Start by adding one!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Edit Attraction Modal -->
            <div id="editAttractionModal" class="modal-overlay">
                <div class="modal-box" style="max-width: 700px;">
                    <div class="modal-header">
                        <h2 style="margin:0;">Edit Attraction</h2>
                        <button class="modal-close-btn"
                            onclick="document.getElementById('editAttractionModal').style.display='none'">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="admin.php?view=attractions" enctype="multipart/form-data">
                            <input type="hidden" name="id" id="edit_id">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label>Attraction Name</label>
                                    <input type="text" name="name" id="edit_name" required>
                                </div>
                                <div class="form-group">
                                    <label>Location (City/Area)</label>
                                    <input type="text" name="location" id="edit_location" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" id="edit_description" rows="3" required></textarea>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label>Morning Start</label>
                                    <input type="time" name="morning_start" id="edit_morning_start" required>
                                </div>
                                <div class="form-group">
                                    <label>Morning End</label>
                                    <input type="time" name="morning_end" id="edit_morning_end" required>
                                </div>
                                <div class="form-group">
                                    <label>Afternoon Start</label>
                                    <input type="time" name="afternoon_start" id="edit_afternoon_start" required>
                                </div>
                                <div class="form-group">
                                    <label>Afternoon End</label>
                                    <input type="time" name="afternoon_end" id="edit_afternoon_end" required>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label>Video URL (Optional)</label>
                                    <input type="url" name="video_url" id="edit_video_url"
                                        placeholder="https://youtube.com/watch?v=...">
                                </div>
                                <div class="form-group">
                                    <label>Slot Capacity</label>
                                    <input type="number" name="max_capacity" id="edit_max_capacity" required>
                                </div>
                                <div class="form-group">
                                    <label>Adult Price (₱)</label>
                                    <input type="number" step="0.01" name="price_adult" id="edit_price_adult" required>
                                </div>
                                <div class="form-group">
                                    <label>Child Price (₱)</label>
                                    <input type="number" step="0.01" name="price_child" id="edit_price_child" required>
                                </div>
                                <div class="form-group">
                                    <label>Senior Price (₱)</label>
                                    <input type="number" step="0.01" name="price_senior" id="edit_price_senior" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Update Images (Select multiple to replace current gallery)</label>
                                <input type="file" name="attraction_images[]" multiple accept=".webp,.jpg,.jpeg,.png,image/*"
                                    class="form-control-file">
                                <small style="color:var(--text-muted); display: block; margin-top: 5px;">Supported: WebP, JPEG,
                                    PNG. Leave empty to keep existing images.</small>
                            </div>
                            <button type="submit" name="update_attraction" class="btn btn-primary" style="width: 100%;">Save
                                Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                const carouselStates = {};

                function moveCarousel(id, direction) {
                    if (!carouselStates[id]) carouselStates[id] = 0;
                    const card = document.getElementById('card-' + id);
                    const slides = card.querySelector('.carousel-slides');
                    const total = slides.children.length;
                    carouselStates[id] = (carouselStates[id] + direction + total) % total;
                    updateCarousel(id);
                }

                function setCarousel(id, index) {
                    carouselStates[id] = index;
                    updateCarousel(id);
                }

                function updateCarousel(id) {
                    const card = document.getElementById('card-' + id);
                    const slides = card.querySelector('.carousel-slides');
                    const dots = card.querySelectorAll('.dot');
                    const index = carouselStates[id];
                    slides.style.transform = `translateX(-${index * 100}%)`;
                    dots.forEach((d, i) => d.classList.toggle('active', i === index));
                }

                function openEditModal(data) {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_location').value = data.location;
                    document.getElementById('edit_description').value = data.description;
                    document.getElementById('edit_morning_start').value = data.morning_start ? data.morning_start.substring(0, 5) : '07:00';
                    document.getElementById('edit_morning_end').value = data.morning_end ? data.morning_end.substring(0, 5) : '12:00';
                    document.getElementById('edit_afternoon_start').value = data.afternoon_start ? data.afternoon_start.substring(0, 5) : '13:00';
                    document.getElementById('edit_afternoon_end').value = data.afternoon_end ? data.afternoon_end.substring(0, 5) : '17:00';
                    document.getElementById('edit_video_url').value = data.video_url || '';
                    document.getElementById('edit_price_adult').value = data.price_adult;
                    document.getElementById('edit_price_child').value = data.price_child;
                    document.getElementById('edit_price_senior').value = data.price_senior;
                    document.getElementById('edit_max_capacity').value = data.max_capacity;
                    document.getElementById('editAttractionModal').style.display = 'flex';
                }

                function confirmDeleteAttraction(id) {
                    Swal.fire({
                        title: 'Delete Attraction?',
                        text: "All associated tickets and slots will be removed!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#b91c1c',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "admin.php?view=attractions&action=delete_attraction&id=" + id;
                        }
                    });
                }
            </script>
        <?php endif; ?>

        <?php if ($current_view === 'bookings'): ?>
            <div class="card" style="margin-top: 2rem;">
                <h2 class="card-title">Order Management</h2>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border);">
                                <th style="padding: 1rem;">Date</th>
                                <th style="padding: 1rem;">Attraction</th>
                                <th style="padding: 1rem;">Name</th>
                                <th style="padding: 1rem;">Slot</th>
                                <th style="padding: 1rem;">Status</th>
                                <th style="padding: 1rem;">Total</th>
                                <th style="padding: 1rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 1rem; font-weight: 500;"><?php echo date('F j, Y', strtotime($b['visit_date'])); ?></td>
                                    <td style="padding: 1rem;"><?php echo htmlspecialchars($b['attraction_name']); ?></td>
                                    <td style="padding: 1rem;"><?php echo htmlspecialchars($b['visitor_name']); ?></td>
                                    <td style="padding: 1rem;"><?php echo htmlspecialchars($b['slot_name']); ?></td>
                                    <td style="padding: 1rem;">
                                        <?php
                                        $statusStyle = 'background:#fef3c7; color:#92400e;'; // Pending
                                        if ($b['status'] === 'paid') $statusStyle = 'background:#dcfce7; color:#166534;';
                                        if ($b['status'] === 'refunded') $statusStyle = 'background:#fee2e2; color:#991b1b;';
                                        if ($b['status'] === 'rejected') $statusStyle = 'background:#f1f5f9; color:#475569;';
                                        ?>
                                        <span style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600; text-transform: capitalize; <?php echo $statusStyle; ?>">
                                            <?php echo htmlspecialchars($b['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; font-weight: 700;">₱<?php echo number_format($b['total_amount'], 2); ?></td>
                                    <td style="padding: 1rem; display: flex; gap: 0.5rem; align-items: center;">
                                        <button onclick='openViewModal(<?php echo json_encode($b); ?>)' class="btn btn-sm btn-secondary" style="background:#e0e7ff; color:#4338ca; border:none; padding: 0.4rem 0.8rem;">
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>
                                        <?php if ($b['status'] === 'pending' || $b['status'] === 'paylater'): ?>
                                            <button onclick="updateStatus('approve', '<?php echo $b['id']; ?>')" class="btn btn-sm" style="background:#10b981; color:white; border:none; padding: 0.4rem 0.8rem; font-size: 0.75rem;">Approve</button>
                                            <button onclick="updateStatus('reject', '<?php echo $b['id']; ?>')" class="btn btn-sm" style="background:#ef4444; color:white; border:none; padding: 0.4rem 0.8rem; font-size: 0.75rem;">Reject</button>
                                        <?php endif; ?>
                                        <?php if ($b['status'] === 'paid'): ?>
                                            <button onclick="confirmRefund('<?php echo urlencode($b['id']); ?>')" class="btn btn-sm" style="background:transparent; color: #ef4444; border: 1px solid #ef4444; font-size: 0.75rem; padding: 0.3rem 0.6rem;">Refund</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="6" style="padding: 2rem; text-align:center; color: var(--text-muted);">No bookings
                                        found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
                function updateStatus(action, bookingId) {
                    const title = action === 'approve' ? 'Approve Booking?' : 'Reject Booking?';
                    const text = action === 'approve' ? 'This will mark the booking as Paid.' : 'This will mark the booking as Rejected.';
                    const btnColor = action === 'approve' ? '#10b981' : '#ef4444';

                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: btnColor,
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, ' + action + ' it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `admin.php?view=bookings&action=${action}&booking_id=${bookingId}`;
                        }
                    });
                }

                function confirmRefund(bookingId) {
                    Swal.fire({
                        title: 'Refund Booking?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, refund it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "admin.php?view=bookings&action=refund&booking_id=" + bookingId;
                        }
                    });
                }
            </script>
    <?php endif; ?>

    <!-- Booking Details Modal (Inspired by User Screenshot) -->
    <div id="bookingDetailsModal" class="modal-overlay" onclick="if(event.target==this) this.style.display='none'">
        <div class="modal-box" style="max-width: 500px; padding: 0; overflow: hidden; border-radius: 1.5rem;">
            <div style="padding: 2rem; background: white;">
                <h1 style="text-align: center; margin-bottom: 2rem; font-size: 1.75rem; color: #334155; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
                    Booking Details <span style="font-size: 1.5rem;">📄</span>
                </h1>

                <div id="detailsContent" style="display: grid; gap: 1.5rem;">
                    <!-- Content via JS -->
                </div>

                <div style="margin-top: 2.5rem; text-align: center;">
                    <button onclick="document.getElementById('bookingDetailsModal').style.display='none'" class="btn" style="background: #1e293b; color: white; padding: 0.75rem 2.5rem; border-radius: 0.75rem; font-weight: 700; width: 100%;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openViewModal(b) {
        const content = document.getElementById('detailsContent');
        content.innerHTML = `
            <div>
                <div style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
                    🗺️ Location
                </div>
                <div style="font-weight: 600; color: #1e293b; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9;">${b.attraction_name}</div>
            </div>

            <div>
                <div style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
                    👤 Customer
                </div>
                <div style="font-size: 0.9rem;">
                    <div><strong>Name:</strong> ${b.visitor_name}</div>
                    <div><strong>Email:</strong> ${b.visitor_email}</div>
                    <div><strong>Phone:</strong> ${b.visitor_phone}</div>
                </div>
                <div style="margin-top: 0.75rem; border-bottom: 1px solid #f1f5f9;"></div>
            </div>

            <div>
                <div style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
                    📅 Booking Info
                </div>
                <div style="font-size: 0.9rem;">
                    <div><strong>Date:</strong> ${new Date(b.visit_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</div>
                    <div><strong>Time Slot:</strong> ${b.slot_name}</div>
                </div>
                <div style="margin-top: 0.75rem; border-bottom: 1px solid #f1f5f9;"></div>
            </div>

            <div>
                <div style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
                    💰 Payment
                </div>
                <div style="font-size: 0.9rem;">
                    <div style="display: flex; justify-content: space-between;"><span>Total Paid:</span> <strong>₱${parseFloat(b.total_amount).toFixed(2)}</strong></div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #64748b;"><span>Tax:</span> <span>12.00%</span></div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #64748b;"><span>Service Charge:</span> <span>10.00%</span></div>
                    <div style="display: flex; justify-content: space-between; margin-top: 0.25rem;"><span>Status:</span> <span style="background: ${b.status === 'paid' ? '#dcfce7' : '#fef3c7'}; color: ${b.status === 'paid' ? '#166534' : '#92400e'}; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">${b.status}</span></div>
                </div>
                <div style="margin-top: 0.75rem; border-bottom: 1px solid #f1f5f9;"></div>
            </div>

            ${b.payment_method === 'gcash' ? `
            <div>
                <div style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
                    🔷 GCash Info
                </div>
                <div style="font-size: 0.9rem;">
                    <div><strong>Reference ID:</strong> ${b.gcash_reference || 'N/A'}</div>
                </div>
            </div>
            ` : `
            <div>
                <div style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
                    💳 PayPal Info
                </div>
                <div style="font-size: 0.9rem;">
                    <div><strong>Order ID:</strong> ${b.paypal_order_id || 'N/A'}</div>
                    <div style="font-size: 0.8rem; color: #64748b;">Created: ${b.created_at}</div>
                </div>
            </div>
            `}
        `;
        document.getElementById('bookingDetailsModal').style.display = 'flex';
    }
    </script>
<?php endif; ?>

<?php if (isset($_SESSION['swal_msg'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            Swal.fire({
                title: '<?php echo addslashes($_SESSION['swal_msg']['title']); ?>',
                text: '<?php echo addslashes($_SESSION['swal_msg']['text']); ?>',
                icon: '<?php echo addslashes($_SESSION['swal_msg']['icon']); ?>'
            });
        });
    </script>
    <?php unset($_SESSION['swal_msg']); endif; ?>

    </div> <!-- End .main-content-wrapper -->
<?php require 'includes/footer.php'; ?>