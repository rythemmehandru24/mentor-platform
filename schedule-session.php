<?php
require_once 'config.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentee') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = isset($_GET['success']) ? "Session scheduled successfully!" : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

try {
    // Get user data for sidebar
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // FETCH MENTORS: Using LEFT JOIN to ensure visibility and COALESCE for default values
    $stmt = $conn->prepare("
        SELECT u.*, 
               COALESCE(mp.hourly_rate, 0) as hourly_rate, 
               COALESCE(mp.availability, 'Available') as availability
        FROM users u
        LEFT JOIN mentor_profiles mp ON u.user_id = mp.mentor_id
        WHERE u.role = 'mentor' 
        AND (u.status = 'approved' OR u.status = 'active')
    ");
    $stmt->execute();
    $mentors = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Session - Mentor Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#362C7A',
                        secondary: '#735CC6',
                        tertiary: '#C6B6F7',
                        background: '#F9F5ED'
                    }
                }
            }
        }
    </script>
    <style>
        .mentor-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .mentor-card:hover { transform: translateY(-5px); border-color: #735CC6; }
        .selected-card { 
            border: 2px solid #362C7A !important; 
            background-color: #f3f0ff; 
            box-shadow: 0 10px 15px -3px rgba(54, 44, 122, 0.1);
        }
        
        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            background: transparent;
            bottom: 0;
            color: transparent;
            cursor: pointer;
            height: auto;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            width: auto;
        }
    </style>
</head>
<body class="bg-background font-sans">
    <div class="flex">
        <div class="w-64 bg-white min-h-screen shadow-lg py-5 border-r border-tertiary flex-shrink-0">
            <div class="text-center mb-8">
                <img src="<?php echo ($user['profile_picture'] && file_exists('uploads/' . $user['profile_picture'])) ? 'uploads/' . $user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                     class="w-24 h-24 rounded-full mx-auto border-4 border-secondary shadow-md object-cover">
                <h5 class="mt-3 text-lg font-bold text-primary"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                <p class="text-secondary text-sm">Mentee</p>
            </div>
            <nav class="px-4 space-y-2">
                <a href="dashboard.php" class="flex items-center p-3 text-primary hover:bg-tertiary rounded-lg transition"><i class="fas fa-home mr-3"></i> Dashboard</a>
                <a href="sessions.php" class="flex items-center p-3 bg-tertiary text-primary rounded-lg font-bold"><i class="fas fa-calendar mr-3"></i> Sessions</a>
                <a href="logout.php" class="flex items-center p-3 text-red-500 hover:bg-red-50 rounded-lg transition"><i class="fas fa-sign-out-alt mr-3"></i> Logout</a>
            </nav>
        </div>
        
        <div class="flex-1 p-10">
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-xl border border-tertiary/20 overflow-hidden max-w-5xl mx-auto">
                <div class="bg-primary p-6">
                    <h1 class="text-2xl font-bold text-white">Schedule New Session</h1>
                    <p class="text-tertiary text-sm">Select your mentor and set your preferred time.</p>
                </div>

                <div class="p-8">
                    <form method="POST" action="process-payment.php" id="scheduleForm">
                        
                        <label class="block text-sm font-semibold text-primary mb-4">Select Your Mentor</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <?php if (empty($mentors)): ?>
                                <div class="col-span-full py-10 text-center border-2 border-dashed border-tertiary rounded-xl text-gray-400">
                                    <i class="fas fa-user-slash text-4xl mb-3"></i>
                                    <p>No approved mentors available at the moment.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($mentors as $mentor): ?>
                                    <div class="mentor-card bg-white rounded-xl p-5 border border-tertiary/40 text-center cursor-pointer shadow-sm relative" 
                                         onclick="selectMentor(<?php echo $mentor['user_id']; ?>, <?php echo $mentor['hourly_rate']; ?>, this)">
                                        <img src="<?php echo ($mentor['profile_picture'] && file_exists('uploads/' . $mentor['profile_picture'])) ? 'uploads/' . $mentor['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                                             class="w-20 h-20 rounded-full mx-auto mb-3 border-2 border-secondary object-cover">
                                        <h6 class="font-bold text-primary"><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h6>
                                        <p class="text-secondary font-semibold">₹<?php echo number_format($mentor['hourly_rate'], 0); ?>/hour</p>
                                        <small class="text-gray-400 block mt-1"><?php echo htmlspecialchars($mentor['availability']); ?></small>
                                        <input type="radio" name="mentor_id" value="<?php echo $mentor['user_id']; ?>" class="hidden" required>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div>
                                <label class="block text-sm font-semibold text-primary mb-2">Start Date & Time</label>
                                <div class="relative group">
                                    <input type="datetime-local" 
                                           name="start_time" 
                                           id="start_time"
                                           class="w-full p-3 pl-12 rounded-lg border border-tertiary focus:ring-2 focus:ring-secondary outline-none transition-all bg-white cursor-pointer" 
                                           required>
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-primary pointer-events-none">
                                        <i class="fas fa-calendar-alt text-lg"></i>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-primary mb-2">Duration</label>
                                <select name="duration" id="duration" onchange="calculateTotal()" class="w-full p-3 rounded-lg border border-tertiary focus:ring-2 focus:ring-secondary outline-none" required>
                                    <option value="1">1 Hour</option>
                                    <option value="2">2 Hours</option>
                                    <option value="3">3 Hours</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-sm font-semibold text-primary mb-2">Session Notes (Optional)</label>
                            <textarea name="notes" rows="3" class="w-full p-3 rounded-lg border border-tertiary focus:ring-2 focus:ring-secondary outline-none" placeholder="What would you like to focus on?"></textarea>
                        </div>

                        <div class="flex flex-col md:flex-row items-center justify-between border-t border-tertiary/30 pt-6 gap-4">
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-500"><i class="fas fa-lock mr-2"></i> Secured by Razorpay</span>
                                <div id="price-summary" class="hidden mt-1 text-primary font-bold">
                                    Total Amount: <span id="total-price" class="text-xl">₹0</span>
                                </div>
                            </div>
                            <button type="submit" class="w-full md:w-auto bg-primary hover:bg-secondary text-white px-12 py-3 rounded-xl font-bold transition-all shadow-lg active:scale-95">
                                Pay & Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedHourlyRate = 0;

        function selectMentor(id, rate, el) {
            document.querySelectorAll('.mentor-card').forEach(c => c.classList.remove('selected-card'));
            el.classList.add('selected-card');
            
            el.querySelector('input').checked = true;
            selectedHourlyRate = rate;
            
            calculateTotal();
        }

        function calculateTotal() {
            const duration = document.getElementById('duration').value;
            const totalPrice = selectedHourlyRate * duration;
            
            const summaryDiv = document.getElementById('price-summary');
            const priceSpan = document.getElementById('total-price');
            
            if (selectedHourlyRate > 0) {
                summaryDiv.classList.remove('hidden');
                priceSpan.innerText = '₹' + totalPrice.toLocaleString();
            }
        }

        window.onload = function() {
            const dateTimeInput = document.getElementById('start_time');
            const now = new Date();
            
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            dateTimeInput.setAttribute('min', minDateTime);
        };

        document.getElementById('scheduleForm').onsubmit = function(e) {
            if (!document.querySelector('input[name="mentor_id"]:checked')) {
                alert("Please select a mentor first!");
                return false;
            }
        };
    </script>
</body>
</html>