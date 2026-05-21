<?php
require_once 'config.php';

// --- ACTION LOGIC START ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];

    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE user_id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE user_id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'remove') {
            $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            $conn->beginTransaction();

            $conn->prepare("DELETE FROM mentor_profiles WHERE mentor_id = ?")->execute([$id]);
            $conn->prepare("DELETE FROM mentee_profiles WHERE mentee_id = ?")->execute([$id]);
            $conn->prepare("DELETE FROM sessions WHERE mentor_id = ? OR mentee_id = ?")->execute([$id, $id]);
            $conn->prepare("DELETE FROM goals WHERE mentee_id = ?")->execute([$id]);
            $conn->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$id, $id]);
            $conn->prepare("DELETE FROM reviews WHERE mentor_id = ? OR mentee_id = ?")->execute([$id, $id]);
            $conn->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);

            $conn->commit();
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
        
        $current_file = basename($_SERVER['PHP_SELF']);
        header("Location: " . $current_file . "?search=" . urlencode($_GET['search'] ?? ''));
        exit();
    } catch(Exception $e) {
        if ($conn->inTransaction()) { $conn->rollBack(); }
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        die("Critical Error: " . $e->getMessage());
    }
}
// --- ACTION LOGIC END ---

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

try {
    $mentor_query = "
        SELECT u.*, mp.hourly_rate
        FROM users u
        LEFT JOIN mentor_profiles mp ON u.user_id = mp.mentor_id
        WHERE u.role = 'mentor'
    ";
    
    if (!empty($search)) {
        $mentor_query .= " AND (CONCAT_WS(' ', u.first_name, u.last_name) LIKE :search OR u.email LIKE :search OR u.qualification LIKE :search)";
    }
    
    $mentor_query .= " GROUP BY u.user_id";
    $stmt_m = $conn->prepare($mentor_query);
    if (!empty($search)) { $stmt_m->bindValue(':search', '%' . $search . '%'); }
    $stmt_m->execute();
    $mentors = $stmt_m->fetchAll();

    $mentee_query = "SELECT u.* FROM users u WHERE u.role = 'mentee'";
    if (!empty($search)) {
        $mentee_query .= " AND (CONCAT_WS(' ', u.first_name, u.last_name) LIKE :search OR u.email LIKE :search OR u.qualification LIKE :search)";
    }
    $stmt_me = $conn->prepare($mentee_query);
    if (!empty($search)) { $stmt_me->bindValue(':search', '%' . $search . '%'); }
    $stmt_me->execute();
    $mentees = $stmt_me->fetchAll();

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Panel</title>
    <link rel="icon" type="image/png" href="assets/images/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#362C7A', secondary: '#735CC6', tertiary: '#C6B6F7', background: '#F9F5ED' }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background font-sans leading-relaxed" x-data="{ openProfile: null }">

    <template x-if="openProfile">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="openProfile = null">
            <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl animate-in fade-in zoom-in duration-200">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-center gap-5">
                            <img :src="openProfile.pic" class="w-24 h-24 rounded-full object-cover border-4 border-secondary shadow-md">
                            <div>
                                <h3 class="text-3xl font-bold text-primary" x-text="openProfile.name"></h3>
                                <p class="text-secondary font-bold uppercase text-xs tracking-widest mt-1" x-text="openProfile.role"></p>
                            </div>
                        </div>
                        <button @click="openProfile = null" class="text-gray-300 hover:text-red-500 transition-colors text-3xl font-light">&times;</button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-y border-gray-100 py-6">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Email Address</p>
                            <p class="text-gray-800 font-medium" x-text="openProfile.email"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Qualification</p>
                            <p class="text-primary font-bold break-all" x-text="openProfile.qualification || 'Not specified'"></p>
                        </div>
                        <template x-if="openProfile.role === 'mentor'">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Price Rate</p>
                                <p class="text-gray-800 font-medium" x-text="openProfile.subDetail"></p>
                            </div>
                        </template>
                    </div>

                    <div class="mt-8">
                        <p class="text-[10px] font-black text-gray-400 uppercase mb-3">Skills & Expertise</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-if="openProfile.skills">
                                <template x-for="skill in openProfile.skills.split(',')">
                                    <span class="bg-tertiary/30 text-primary px-4 py-1.5 rounded-full text-xs font-bold" x-text="skill.trim()"></span>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 p-5 flex justify-end">
                    <button @click="openProfile = null" class="bg-primary text-white px-8 py-2.5 rounded-xl font-black hover:bg-secondary transition-all">CLOSE PROFILE</button>
                </div>
            </div>
        </div>
    </template>

    <nav class="bg-white shadow-md fixed w-full z-50 h-16 flex items-center px-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="flex items-center no-underline">
                <img src="assets/images/Logo.jpg" alt="Logo" class="h-10 w-auto"> 
                <span class="ml-2 text-primary text-xl font-black tracking-tighter">SUPER ADMIN</span> 
            </a>
        </div>
    </nav>

    <div class="pt-24 pb-12 container mx-auto px-4">
        <div class="max-w-xl mx-auto mb-10">
            <form method="GET" class="relative group">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by full name, email or qualification..." 
                       class="w-full pl-12 pr-4 py-3 rounded-full border border-tertiary focus:outline-none focus:ring-2 focus:ring-secondary shadow-sm">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-primary text-white px-6 py-1.5 rounded-full hover:bg-secondary">Search</button>
            </form>
        </div>

        <div class="space-y-12">
            <section class="bg-white rounded-xl shadow-lg overflow-hidden border border-tertiary/30">
                <div class="bg-primary p-4 flex justify-between items-center">
                    <h2 class="text-white text-xl font-semibold"><i class="fas fa-user-shield mr-2"></i> Mentors</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <tr>
                                <th class="p-4">Mentor Name & Status</th>
                                <th class="p-4 text-center">Approve</th>
                                <th class="p-4 text-center">Reject</th>
                                <th class="p-4 text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-tertiary/10">
                            <?php foreach($mentors as $m): 
                                $pic = ($m['profile_picture'] && file_exists('uploads/'.$m['profile_picture'])) ? 'uploads/'.$m['profile_picture'] : 'assets/images/default-avatar.jpg';
                                
                                $statusBadge = '<span class="ml-2 text-[9px] font-bold bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">PENDING</span>';
                                if($m['status'] == 'approved') {
                                    $statusBadge = '<span class="ml-2 text-[9px] font-bold bg-green-100 text-green-600 px-2 py-0.5 rounded-full">APPROVED</span>';
                                } elseif($m['status'] == 'rejected') {
                                    $statusBadge = '<span class="ml-2 text-[9px] font-bold bg-red-100 text-red-600 px-2 py-0.5 rounded-full">REJECTED</span>';
                                }
                            ?>
                            <tr class="hover:bg-tertiary/5 transition-colors">
                                <td class="p-4 flex items-center cursor-pointer group" 
                                    @click="openProfile = { 
                                        name: '<?php echo addslashes($m['first_name'] . ' ' . $m['last_name']); ?>', 
                                        role: 'mentor', 
                                        email: '<?php echo $m['email']; ?>',
                                        pic: '<?php echo $pic; ?>',
                                        qualification: '<?php echo addslashes($m['qualification'] ?? ''); ?>',
                                        subDetail: '₹<?php echo $m['hourly_rate'] ?? 0; ?>/hr',
                                        skills: '<?php echo addslashes($m['skills'] ?? ''); ?>'
                                    }">
                                    <img src="<?php echo $pic; ?>" class="w-10 h-10 rounded-full mr-3 object-cover border border-secondary">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-800 group-hover:text-secondary"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></span>
                                        <div><?php echo $statusBadge; ?></div>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <a href="?action=approve&id=<?php echo $m['user_id']; ?>&search=<?php echo urlencode($search); ?>" class="text-green-500 hover:text-green-700 transition-colors"><i class="fas fa-check-circle text-2xl"></i></a>
                                </td>
                                <td class="p-4 text-center">
                                    <a href="?action=reject&id=<?php echo $m['user_id']; ?>&search=<?php echo urlencode($search); ?>" class="text-yellow-500 hover:text-yellow-700 transition-colors"><i class="fas fa-times-circle text-2xl"></i></a>
                                </td>
                                <td class="p-4 text-center">
                                    <a href="?action=remove&id=<?php echo $m['user_id']; ?>&search=<?php echo urlencode($search); ?>" onclick="return confirm('Delete user?')" class="text-red-600 hover:text-red-800 transition-colors"><i class="fas fa-trash text-2xl"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white rounded-xl shadow-lg overflow-hidden border border-tertiary/30">
                <div class="bg-secondary p-4 flex justify-between items-center">
                    <h2 class="text-white text-xl font-semibold"><i class="fas fa-users mr-2"></i> Mentees</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <tr>
                                <th class="p-4">Name</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-tertiary/10">
                            <?php foreach($mentees as $me): 
                                 $mpic = ($me['profile_picture'] && file_exists('uploads/'.$me['profile_picture'])) ? 'uploads/'.$me['profile_picture'] : 'assets/images/default-avatar.jpg';
                            ?>
                            <tr class="hover:bg-tertiary/5 transition-colors">
                                <td class="p-4 font-bold text-gray-800 cursor-pointer group hover:text-secondary"
                                    @click="openProfile = { 
                                        name: '<?php echo addslashes($me['first_name'] . ' ' . $me['last_name']); ?>', 
                                        role: 'mentee', 
                                        email: '<?php echo $me['email']; ?>',
                                        pic: '<?php echo $mpic; ?>',
                                        qualification: '<?php echo addslashes($me['qualification'] ?? ''); ?>',
                                        skills: '<?php echo addslashes($me['skills'] ?? ''); ?>'
                                    }">
                                    <?php echo htmlspecialchars($me['first_name'] . ' ' . $me['last_name']); ?>
                                </td>
                                <td class="p-4 text-right">
                                    <a href="?action=remove&id=<?php echo $me['user_id']; ?>&search=<?php echo urlencode($search); ?>" class="bg-red-600 text-white px-4 py-1 rounded-lg text-xs font-black hover:bg-red-700 transition-colors">REMOVE</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</body>
</html>