<?php
require_once 'config.php';

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

try {
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get all resources for the mentor
    $stmt = $conn->prepare("
        SELECT r.*
        FROM resources r
        WHERE r.mentor_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $resources = $stmt->fetchAll();
    error_log("Resources query result: " . print_r($resources, true));
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle resource upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload') {
    try {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $category = sanitize_input($_POST['category']);
        
        // Validate input
        if (empty($title) || empty($description) || empty($category)) {
            $error = "Please fill in all required fields";
        } else {
            // Handle file upload
            if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                $file = $_FILES['file'];
                $file_name = time() . '_' . basename($file['name']);
                $file_path = UPLOAD_PATH . 'resources/' . $file_name;
                
                // Create directory if it doesn't exist
                if (!file_exists(UPLOAD_PATH . 'resources/')) {
                    mkdir(UPLOAD_PATH . 'resources/', 0777, true);
                }
                
                // Get file type
                $type = pathinfo($file['name'], PATHINFO_EXTENSION);
                
                // Validate file type
                $allowed_types = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3'];
                if (!in_array(strtolower($type), $allowed_types)) {
                    $error = "Invalid file type. Allowed types: " . implode(', ', $allowed_types);
                } else {
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        error_log("File uploaded successfully to: " . $file_path);
                        
                        // Insert resource into database
                        $stmt = $conn->prepare("
                            INSERT INTO resources (mentor_id, title, description, category, type, file_name, file_path)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $relative_path = 'uploads/resources/' . $file_name;
                        $values = [
                            $user_id,
                            $title,
                            $description,
                            $category,
                            $type,
                            $file_name,
                            $relative_path
                        ];
                        error_log("Executing database insert with values: " . print_r($values, true));
                        
                        $stmt->execute($values);
                        error_log("Database insert successful. Last insert ID: " . $conn->lastInsertId());
                        
                        $success = "Resource uploaded successfully!";
                        
                        // Refresh resources list
                        $stmt = $conn->prepare("
                            SELECT r.*
                            FROM resources r
                            WHERE r.mentor_id = ?
                            ORDER BY r.created_at DESC
                        ");
                        $stmt->execute([$user_id]);
                        $resources = $stmt->fetchAll();
                        error_log("Resources refreshed. Count: " . count($resources));
                        
                    } else {
                        $error = "Failed to upload file";
                        error_log("Failed to move uploaded file to: " . $file_path);
                    }
                }
            } else {
                $error = "Please select a file to upload";
                error_log("No file uploaded or upload error: " . print_r($_FILES, true));
            }
        }
    } catch(Exception $e) {
        $error = "An unexpected error occurred: " . $e->getMessage();
        error_log("Unexpected error in resource upload: " . $e->getMessage());
    }
}

// Handle resource deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'delete') {
            $resource_id = (int)$_POST['resource_id'];
            
            // Get file path
            $stmt = $conn->prepare("SELECT file_path FROM resources WHERE resource_id = ? AND mentor_id = ?");
            $stmt->execute([$resource_id, $user_id]);
            $resource = $stmt->fetch();
            
            if ($resource) {
                // Delete file
                if (file_exists($resource['file_path'])) {
                    unlink($resource['file_path']);
                }
                
                // Delete from database
                $stmt = $conn->prepare("DELETE FROM resources WHERE resource_id = ? AND mentor_id = ?");
                $stmt->execute([$resource_id, $user_id]);
                
                $success = "Resource deleted successfully!";
                
                // Refresh resources list
                $stmt = $conn->prepare("
                    SELECT r.*
                    FROM resources r
                    WHERE r.mentor_id = ?
                    ORDER BY r.created_at DESC
                ");
                $stmt->execute([$user_id]);
                $resources = $stmt->fetchAll();
            }
        }
    } catch(Exception $e) {
        $error = "An unexpected error occurred: " . $e->getMessage();
        error_log("Unexpected error in resource deletion: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - Mentor Platform</title>
    <link rel="icon" type="image/png" href="assets/images/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#362C7A',
                        secondary: '#735CC6',
                        tertiary: '#C6B6F7',
                        background: '#F9F5ED',
                        white: '#FFFFFF'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.5s ease-out',
                        'scale': 'scale 0.3s ease-in-out',
                        'bounce-in': 'bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideIn: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        },
                        scale: {
                            '0%': { transform: 'scale(1)' },
                            '100%': { transform: 'scale(1.02)' }
                        },
                        bounceIn: {
                            '0%': { transform: 'scale(0.3)', opacity: '0' },
                            '50%': { transform: 'scale(1.05)', opacity: '0.8' },
                            '70%': { transform: 'scale(0.9)', opacity: '0.9' },
                            '100%': { transform: 'scale(1)', opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .resource-card {
            transition: all 0.3s ease;
        }
        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(54, 44, 122, 0.1);
        }
        .file-icon {
            font-size: 2rem;
            color: #362C7A;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .resource-card:hover .file-icon {
            transform: scale(1.1);
            color: #735CC6;
        }
        .category-tag {
            transition: all 0.3s ease;
        }
        .resource-card:hover .category-tag {
            transform: translateY(-2px);
        }
        .action-button {
            transition: all 0.3s ease;
        }
        .action-button:hover {
            transform: translateY(-2px);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <div class="flex animate-fade-in">
        <!-- Sidebar -->
        <div class="w-64 bg-white min-h-screen shadow-lg py-5 flex-shrink-0 border-r border-tertiary">
            <div class="text-center mb-4">
                <img src="<?php echo $user['profile_picture'] ? UPLOAD_URL . $user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                     alt="Profile Picture" 
                     class="w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary">
                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                <p class="text-secondary">Mentor</p>
            </div>
            
            <nav class="space-y-1 px-3">
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="dashboard.php">
                    <i class="fas fa-home w-5 mr-2.5"></i> Dashboard
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="profile.php">
                    <i class="fas fa-user w-5 mr-2.5"></i> Profile
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="messages.php">
                    <i class="fas fa-comments w-5 mr-2.5"></i> Messages
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="sessions.php">
                    <i class="fas fa-calendar w-5 mr-2.5"></i> Sessions
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="resources.php">
                    <i class="fas fa-book w-5 mr-2.5"></i> Resources
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="earnings.php">
                    <i class="fas fa-dollar-sign w-5 mr-2.5"></i> Earnings
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="analytics.php">
                    <i class="fas fa-chart-line w-5 mr-2.5"></i> Analytics
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-white bg-red-500 transition-all duration-300 hover:bg-red-600" href="logout.php">
                    <i class="fas fa-sign-out-alt w-5 mr-2.5"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-8 animate-slide-in">
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-primary">My Resources</h1>
                <button type="button" 
                        class="inline-block px-4 py-2 bg-primary hover:bg-secondary text-white rounded-lg transition-all duration-300"
                        data-bs-toggle="modal" 
                        data-bs-target="#uploadResourceModal">
                    <i class="fas fa-upload mr-2"></i> Upload Resource
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resources as $resource): ?>
                    <div class="resource-card bg-white rounded-xl shadow-lg border border-tertiary/30 overflow-hidden">
                        <div class="p-5 text-center">
                            <?php
                            $icon_class = 'fas fa-file';
                            switch ($resource['type']) {
                                case 'document':
                                    $icon_class = 'fas fa-file-alt';
                                    break;
                                case 'video':
                                    $icon_class = 'fas fa-video';
                                    break;
                                case 'audio':
                                    $icon_class = 'fas fa-headphones';
                                    break;
                                case 'image':
                                    $icon_class = 'fas fa-image';
                                    break;
                            }
                            ?>
                            <div class="file-icon">
                                <i class="<?php echo $icon_class; ?>"></i>
                            </div>
                            
                            <h5 class="text-lg font-semibold mb-2 text-primary"><?php echo htmlspecialchars($resource['title']); ?></h5>
                            <span class="category-tag inline-block px-3 py-1 rounded-full text-xs bg-tertiary/20 text-primary mb-3">
                                <?php echo htmlspecialchars($resource['category']); ?>
                            </span>
                            
                            <p class="text-secondary mb-4"><?php echo nl2br(htmlspecialchars($resource['description'])); ?></p>
                            
                            <div class="flex justify-center space-x-2">
                                <a href="<?php echo UPLOAD_URL . 'resources/' . $resource['file_name']; ?>" 
                                   class="action-button inline-block px-3 py-1 border border-primary text-primary hover:bg-primary hover:text-white rounded-lg transition-all duration-300" 
                                   target="_blank">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="resource_id" value="<?php echo $resource['resource_id']; ?>">
                                    <button type="submit" 
                                            class="action-button inline-block px-3 py-1 border border-red-500 text-red-500 hover:bg-red-500 hover:text-white rounded-lg transition-all duration-300">
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Upload Resource Modal -->
    <div id="uploadResourceModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden animate-fade-in overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-xl p-6 my-8 animate-slide-in max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-2xl font-semibold text-primary">Upload Resource</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="document.getElementById('uploadResourceModal').classList.add('hidden')">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="upload">
                    
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">Title</label>
                        <input type="text" 
                               class="w-full px-4 py-2.5 border border-tertiary rounded-xl focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition-all duration-300 hover:border-secondary" 
                               name="title" 
                               placeholder="Enter resource title"
                               required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">Description</label>
                        <textarea class="w-full px-4 py-2.5 border border-tertiary rounded-xl focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition-all duration-300 hover:border-secondary" 
                                  name="description" 
                                  rows="3" 
                                  placeholder="Describe your resource"
                                  required></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">Category</label>
                            <select class="w-full px-4 py-2.5 border border-tertiary rounded-xl focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition-all duration-300 hover:border-secondary appearance-none bg-white" 
                                    name="category" 
                                    required>
                                <option value="">Select a category</option>
                                <option value="Notes">Notes</option>
                                <option value="Assignments">Assignments</option>
                                <option value="Test Papers">Test Papers</option>
                                <option value="Professional Guidelines">Professional Guidelines</option>
                                <option value="Coding Material">Coding Material</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">Resource Type</label>
                            <select class="w-full px-4 py-2.5 border border-tertiary rounded-xl focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition-all duration-300 hover:border-secondary appearance-none bg-white" 
                                    name="type" 
                                    required>
                                <option value="">Select a type</option>
                                <option value="document">Document</option>
                                <option value="video">Video</option>
                                <option value="audio">Audio</option>
                                <option value="image">Image</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">File</label>
                        <div class="mt-1 flex justify-center px-6 pt-4 pb-5 border-2 border-tertiary border-dashed rounded-xl hover:border-secondary transition-colors duration-300">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-3xl text-secondary mb-2"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-secondary hover:text-primary focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-secondary">
                                        <span>Upload a file</span>
                                        <input id="file-upload" name="file" type="file" class="sr-only" required>
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    Maximum file size: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB<br>
                                    Allowed types: <?php echo implode(', ', ALLOWED_FILE_TYPES); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-4 pt-4 border-t border-gray-200">
                        <button type="button" 
                                class="px-5 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all duration-300"
                                onclick="document.getElementById('uploadResourceModal').classList.add('hidden')">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-5 py-2 bg-primary text-white rounded-xl hover:bg-secondary transition-all duration-300">
                            Upload Resource
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show modal
        document.querySelector('[data-bs-toggle="modal"]').addEventListener('click', function() {
            document.getElementById('uploadResourceModal').classList.remove('hidden');
        });

        // File upload preview
        const fileUpload = document.getElementById('file-upload');
        fileUpload.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const fileLabel = this.closest('div').querySelector('p');
                fileLabel.textContent = `Selected file: ${fileName}`;
            }
        });

        // Drag and drop functionality
        const dropZone = document.querySelector('.border-dashed');
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-secondary', 'bg-tertiary/10');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-secondary', 'bg-tertiary/10');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileUpload.files = files;
            
            if (files[0]) {
                const fileLabel = dropZone.querySelector('p');
                fileLabel.textContent = `Selected file: ${files[0].name}`;
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 