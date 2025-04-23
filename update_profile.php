<?php
session_start();
require_once 'components/connect.php';

// Redirect if not logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = [];

// Fetch current user data
$select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
$select_profile->execute([$user_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if(isset($_POST['submit'])) {
    // Sanitize inputs using modern methods
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8') : '';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone']), ENT_QUOTES, 'UTF-8') : '';
    
    // Handle image upload
    $image = $_FILES['image']['name'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_size = $_FILES['image']['size'];
    $image_folder = 'uploaded_img/';
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    
    // Validate inputs
    if(empty($name)) {
        $message[] = 'Please enter your name!';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = 'Please enter a valid email!';
    } else {
        // Check if email already exists (excluding current user)
        $check_email = $conn->prepare("SELECT * FROM `users` WHERE email = ? AND id != ?");
        $check_email->execute([$email, $user_id]);
        
        if($check_email->rowCount() > 0) {
            $message[] = 'Email already taken!';
        } else {
            // Handle image upload if new image was provided
            if(!empty($image)) {
                $image_extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
                
                if(!in_array($image_extension, $allowed_extensions)) {
                    $message[] = 'Image format not supported! (JPG, JPEG, PNG, WEBP only)';
                } elseif($image_size > 2000000) {
                    $message[] = 'Image size is too large! (Max 2MB)';
                } else {
                    // Delete old image if it exists
                    if(!empty($fetch_profile['image']) && file_exists($image_folder . $fetch_profile['image'])) {
                        unlink($image_folder . $fetch_profile['image']);
                    }
                    
                    // Generate unique filename
                    $unique_image_name = uniqid() . '.' . $image_extension;
                    move_uploaded_file($image_tmp_name, $image_folder . $unique_image_name);
                }
            } else {
                // Keep existing image if no new image was uploaded
                $unique_image_name = $fetch_profile['image'];
            }
            
            // Update database if no errors
            if(empty($message)) {
                $update_profile = $conn->prepare("UPDATE `users` SET name = ?, email = ?, phone = ?, image = ? WHERE id = ?");
                $update_profile->execute([$name, $email, $phone, $unique_image_name, $user_id]);
                
                // Refresh profile data
                $select_profile->execute([$user_id]);
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
                
                $message[] = 'Profile updated successfully!';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .update-profile {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .update-profile form {
            background: #fff;
            border-radius: .5rem;
            padding: 2rem;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
        }
        .update-profile .flex {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .update-profile .inputBox {
            flex: 1 1 30rem;
        }
        .update-profile .inputBox span {
            display: block;
            margin-bottom: .5rem;
            font-size: 1.6rem;
            color: #666;
        }
        .update-profile .inputBox .box {
            width: 100%;
            padding: 1.2rem 1.4rem;
            font-size: 1.6rem;
            color: #333;
            border: .1rem solid #ddd;
            border-radius: .5rem;
            margin-bottom: 1.5rem;
        }
        .update-profile .image-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        .update-profile .image-container img {
            height: 15rem;
            width: 15rem;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
        }
        .update-profile .btn {
            display: inline-block;
            padding: 1rem 3rem;
            background: #8BC34A;
            color: #fff;
            font-size: 1.7rem;
            border-radius: .5rem;
            cursor: pointer;
            margin-top: 1rem;
        }
        .update-profile .btn:hover {
            background: #689F38;
        }
        @media (max-width: 768px) {
            .update-profile .flex {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="update-profile">
    <h1 class="heading">update profile</h1>

    <form action="" method="post" enctype="multipart/form-data">
        <?php
        if(isset($message)) {
            foreach($message as $msg) {
                echo '
                <div class="message">
                    <span>'.htmlspecialchars($msg, ENT_QUOTES, 'UTF-8').'</span>
                    <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
                </div>
                ';
            }
        }
        ?>
        
        <div class="flex">
            <div class="inputBox">
                <div class="image-container">
                    <?php if(!empty($fetch_profile['image'])): ?>
                        <img src="uploaded_img/<?= htmlspecialchars($fetch_profile['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Image">
                    <?php else: ?>
                        <img src="images/default-avatar.jpg" alt="Default Profile Image">
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/jpg, image/jpeg, image/png, image/webp" class="box">
                </div>
                
                <span>Your name:</span>
                <input type="text" name="name" class="box" value="<?= htmlspecialchars($fetch_profile['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                
                <span>Your email:</span>
                <input type="email" name="email" class="box" value="<?= htmlspecialchars($fetch_profile['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                
                <span>Your phone:</span>
                <input type="text" name="phone" class="box" value="<?= htmlspecialchars($fetch_profile['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                
                <input type="submit" value="update profile" name="submit" class="btn">
            </div>
        </div>
    </form>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>