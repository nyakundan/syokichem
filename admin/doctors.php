<?php
session_start();
require_once '../components/connect.php';

// Admin authentication
//if(!isset($_SESSION['admin_id'])){
  //  header('location:admin_login.php');
  //  exit;
//}

//$admin_id = $_SESSION['admin_id'];
//$message = [];

// CRUD Operations
if(isset($_POST['add_staff'])){
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $profession = filter_input(INPUT_POST, 'profession', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $qualification = filter_input(INPUT_POST, 'qualification', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $experience = filter_input(INPUT_POST, 'experience', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Image handling
    $image = $_FILES['image']['name'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_size = $_FILES['image']['size'];
    $image_folder = '../uploaded_img/staff/';
    
    if(empty($name) || empty($profession)){
        $message[] = 'Name and profession are required!';
    } elseif($image_size > 2000000){
        $message[] = 'Image size is too large!';
    } else {
        // Generate unique filename
        $image_extension = pathinfo($image, PATHINFO_EXTENSION);
        $unique_image_name = uniqid().'.'.$image_extension;
        
        $insert_staff = $conn->prepare("INSERT INTO `medical_staff` (name, profession, qualification, bio, experience, image) VALUES (?,?,?,?,?,?)");
        $insert_staff->execute([$name, $profession, $qualification, $bio, $experience, $unique_image_name]);
        
        if($insert_staff){
            move_uploaded_file($image_tmp_name, $image_folder.$unique_image_name);
            $message[] = 'New staff member added successfully!';
        }
    }
}

if(isset($_GET['delete'])){
    $delete_id = $_GET['delete'];
    $delete_staff = $conn->prepare("SELECT * FROM `medical_staff` WHERE id = ?");
    $delete_staff->execute([$delete_id]);
    $fetch_delete = $delete_staff->fetch(PDO::FETCH_ASSOC);
    
    // Delete image file
    unlink('../uploaded_img/staff/'.$fetch_delete['image']);
    
    $delete_staff = $conn->prepare("DELETE FROM `medical_staff` WHERE id = ?");
    $delete_staff->execute([$delete_id]);
    header('location:doctors.php');
}

if(isset($_POST['update_staff'])){
    $update_id = $_POST['update_id'];
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $profession = filter_input(INPUT_POST, 'profession', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $qualification = filter_input(INPUT_POST, 'qualification', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $experience = filter_input(INPUT_POST, 'experience', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Image handling for update
    $update_image = $_FILES['update_image']['name'];
    $update_image_tmp_name = $_FILES['update_image']['tmp_name'];
    $update_image_size = $_FILES['update_image']['size'];
    $update_image_folder = '../uploaded_img/staff/';
    
    if(empty($name) || empty($profession)){
        $message[] = 'Name and profession are required!';
    } else {
        if(!empty($update_image)){
            if($update_image_size > 2000000){
                $message[] = 'Image size is too large!';
            } else {
                // Get old image
                $select_old_image = $conn->prepare("SELECT image FROM `medical_staff` WHERE id = ?");
                $select_old_image->execute([$update_id]);
                $fetch_old_image = $select_old_image->fetch(PDO::FETCH_ASSOC);
                
                // Delete old image
                unlink($update_image_folder.$fetch_old_image['image']);
                
                // Upload new image
                $image_extension = pathinfo($update_image, PATHINFO_EXTENSION);
                $unique_image_name = uniqid().'.'.$image_extension;
                move_uploaded_file($update_image_tmp_name, $update_image_folder.$unique_image_name);
                
                $update_staff = $conn->prepare("UPDATE `medical_staff` SET name = ?, profession = ?, qualification = ?, bio = ?, experience = ?, image = ? WHERE id = ?");
                $update_staff->execute([$name, $profession, $qualification, $bio, $experience, $unique_image_name, $update_id]);
            }
        } else {
            $update_staff = $conn->prepare("UPDATE `medical_staff` SET name = ?, profession = ?, qualification = ?, bio = ?, experience = ? WHERE id = ?");
            $update_staff->execute([$name, $profession, $qualification, $bio, $experience, $update_id]);
        }
        $message[] = 'Staff member updated successfully!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Medical Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css">
    <style>
        .staff-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .staff-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .staff-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .staff-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .staff-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .staff-image {
            height: 250px;
            overflow: hidden;
        }
        
        .staff-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .staff-info {
            padding: 1.5rem;
        }
        
        .staff-name {
            font-size: 1.4rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .staff-profession {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: #e74c3c;
            color: #fff;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .staff-qualification {
            font-size: 1rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
        }
        
        .staff-experience {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 1rem;
        }
        
        .staff-bio {
            font-size: 0.95rem;
            color: #34495e;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .staff-actions {
            display: flex;
            gap: 1rem;
        }
        
        .staff-actions .btn {
            flex: 1;
            padding: 0.6rem;
            text-align: center;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .staff-actions .edit-btn {
            background: #3498db;
            color: #fff;
        }
        
        .staff-actions .delete-btn {
            background: #e74c3c;
            color: #fff;
        }
        
        .add-staff-form {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 3rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .submit-btn {
            background: #2ecc71;
            color: #fff;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #27ae60;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            width: 80%;
            max-width: 600px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        @media (max-width: 768px) {
            .staff-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>
   
<?php include 'includes/admin_header.php'; ?>

<section class="staff-container">
    <div class="staff-header">
        <h1>Manage Medical Staff</h1>
        <p>Add, edit, or remove doctors, nurses, and pharmacists</p>
    </div>

    <?php
    if(isset($message)){
        foreach($message as $msg){
            echo '
            <div class="message">
                <span>'.$msg.'</span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
            ';
        }
    }
    ?>

    <div class="add-staff-form">
        <h2>Add New Staff Member</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" required>
            </div>
            
            <div class="form-group">
                <label for="profession">Profession</label>
                <select name="profession" id="profession" required>
                    <option value="">Select Profession</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Nurse">Nurse</option>
                    <option value="Pharmacist">Pharmacist</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="qualification">Qualification</label>
                <input type="text" name="qualification" id="qualification">
            </div>
            
            <div class="form-group">
                <label for="experience">Experience</label>
                <input type="text" name="experience" id="experience" placeholder="e.g., 5 years">
            </div>
            
            <div class="form-group">
                <label for="bio">Bio/Description</label>
                <textarea name="bio" id="bio"></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">Profile Image</label>
                <input type="file" name="image" id="image" accept="image/*" required>
            </div>
            
            <button type="submit" name="add_staff" class="submit-btn">Add Staff Member</button>
        </form>
    </div>

    <div class="staff-grid">
        <?php
        $select_staff = $conn->prepare("SELECT * FROM `medical_staff`");
        $select_staff->execute();
        
        if($select_staff->rowCount() > 0){
            while($fetch_staff = $select_staff->fetch(PDO::FETCH_ASSOC)){
        ?>
        <div class="staff-card">
            <div class="staff-image">
                <img src="../uploaded_img/staff/<?= $fetch_staff['image']; ?>" alt="<?= $fetch_staff['name']; ?>">
            </div>
            <div class="staff-info">
                <h3 class="staff-name"><?= $fetch_staff['name']; ?></h3>
                <span class="staff-profession"><?= $fetch_staff['profession']; ?></span>
                <?php if(!empty($fetch_staff['qualification'])): ?>
                    <p class="staff-qualification"><?= $fetch_staff['qualification']; ?></p>
                <?php endif; ?>
                <?php if(!empty($fetch_staff['experience'])): ?>
                    <p class="staff-experience">Experience: <?= $fetch_staff['experience']; ?></p>
                <?php endif; ?>
                <?php if(!empty($fetch_staff['bio'])): ?>
                    <p class="staff-bio"><?= $fetch_staff['bio']; ?></p>
                <?php endif; ?>
                <div class="staff-actions">
                    <button class="btn edit-btn" onclick="openEditModal(
                        '<?= $fetch_staff['id']; ?>',
                        '<?= htmlspecialchars($fetch_staff['name'], ENT_QUOTES); ?>',
                        '<?= $fetch_staff['profession']; ?>',
                        '<?= htmlspecialchars($fetch_staff['qualification'], ENT_QUOTES); ?>',
                        '<?= htmlspecialchars($fetch_staff['experience'], ENT_QUOTES); ?>',
                        '<?= htmlspecialchars($fetch_staff['bio'], ENT_QUOTES); ?>',
                        '<?= $fetch_staff['image']; ?>'
                    )">Edit</button>
                    <a href="doctors.php?delete=<?= $fetch_staff['id']; ?>" class="btn delete-btn" onclick="return confirm('Delete this staff member?');">Delete</a>
                </div>
            </div>
        </div>
        <?php
            }
        } else {
            echo '<p class="empty">No staff members added yet!</p>';
        }
        ?>
    </div>
</section>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Staff Member</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="update_id" id="update_id">
            <input type="hidden" name="old_image" id="old_image">
            
            <div class="form-group">
                <label for="update_name">Full Name</label>
                <input type="text" name="name" id="update_name" required>
            </div>
            
            <div class="form-group">
                <label for="update_profession">Profession</label>
                <select name="profession" id="update_profession" required>
                    <option value="Doctor">Doctor</option>
                    <option value="Nurse">Nurse</option>
                    <option value="Pharmacist">Pharmacist</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="update_qualification">Qualification</label>
                <input type="text" name="qualification" id="update_qualification">
            </div>
            
            <div class="form-group">
                <label for="update_experience">Experience</label>
                <input type="text" name="experience" id="update_experience" placeholder="e.g., 5 years">
            </div>
            
            <div class="form-group">
                <label for="update_bio">Bio/Description</label>
                <textarea name="bio" id="update_bio"></textarea>
            </div>
            
            <div class="form-group">
                <label for="update_image">Update Profile Image (Leave blank to keep current)</label>
                <input type="file" name="update_image" id="update_image" accept="image/*">
                <div id="current-image-container" style="margin-top: 1rem;">
                    <p>Current Image:</p>
                    <img id="current-image-preview" src="" alt="Current Image" style="max-width: 150px; margin-top: 0.5rem;">
                </div>
            </div>
            
            <button type="submit" name="update_staff" class="submit-btn">Update Staff Member</button>
        </form>
    </div>
</div>

<script>
    // Modal functionality
    const modal = document.getElementById("editModal");
    const span = document.getElementsByClassName("close")[0];
    
    function openEditModal(id, name, profession, qualification, experience, bio, image) {
        document.getElementById("update_id").value = id;
        document.getElementById("update_name").value = name;
        document.getElementById("update_profession").value = profession;
        document.getElementById("update_qualification").value = qualification;
        document.getElementById("update_experience").value = experience;
        document.getElementById("update_bio").value = bio;
        document.getElementById("old_image").value = image;
        document.getElementById("current-image-preview").src = "../uploaded_img/staff/" + image;
        
        modal.style.display = "block";
    }
    
    span.onclick = function() {
        modal.style.display = "none";
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

<script src="../js/admin_script.js"></script>
</body>
</html>