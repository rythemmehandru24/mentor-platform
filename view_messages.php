<?php
// view_messages.php
require_once 'config.php';

try {
    // Fetch all messages from the database using $conn
    $stmt = $conn->query("SELECT id, name, email, subject, message, created_at FROM contact_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching messages: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Messages</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8 font-sans">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Contact Form Messages</h1>
            <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition">Back to Site</a>
        </div>
        
        <div class="bg-white shadow-xl rounded-lg overflow-hidden border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Sender details</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Subject & Message</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500 italic">No messages found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 align-top">
                                    <?php echo date('M d, Y', strtotime($msg['created_at'])); ?><br>
                                    <span class="text-xs"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></span>
                               </td>
                                <td class="px-6 py-4 whitespace-nowrap align-top">
                                    <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($msg['name']); ?></div>
                                    <div class="text-sm text-blue-600 hover:underline">
                                        <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>">
                                            <?php echo htmlspecialchars($msg['email']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-bold text-gray-800 mb-2 pb-1 border-b border-gray-100">
                                        <?php echo htmlspecialchars($msg['subject']); ?>
                                    </div>
                                    <div class="text-gray-600 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($msg['message']); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>