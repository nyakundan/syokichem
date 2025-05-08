<?php
include 'components/connect.php';
session_start();

$message = [];

if(isset($_POST['submit_prescription'])){
   $doctor_name = isset($_POST['doctor_name']) ? htmlspecialchars(trim($_POST['doctor_name']), ENT_QUOTES, 'UTF-8') : '';
   $patient_name = isset($_POST['patient_name']) ? htmlspecialchars(trim($_POST['patient_name']), ENT_QUOTES, 'UTF-8') : '';
   $patient_email = isset($_POST['patient_email']) ? htmlspecialchars(trim($_POST['patient_email']), ENT_QUOTES, 'UTF-8') : '';
   $patient_phone = isset($_POST['patient_phone']) ? htmlspecialchars(trim($_POST['patient_phone']), ENT_QUOTES, 'UTF-8') : '';
   $notes = isset($_POST['notes']) ? htmlspecialchars(trim($_POST['notes']), ENT_QUOTES, 'UTF-8') : '';
   
   // Handle file upload
   $prescription_file = $_FILES['prescription_file']['name'];
   $prescription_size = $_FILES['prescription_file']['size'];
   $prescription_tmp_name = $_FILES['prescription_file']['tmp_name'];
   $prescription_error = $_FILES['prescription_file']['error'];
   
   // Validate inputs
   if(empty($doctor_name) || empty($patient_name) || empty($patient_email) || empty($patient_phone)){
      $message[] = 'Doctor, patient name, email, and phone are required';
   } elseif($prescription_error !== UPLOAD_ERR_OK){
      $message[] = 'Please upload a valid prescription file';
   } elseif($prescription_size > 2097152){ // 2MB
      $message[] = 'File size must be less than 2MB';
   } else {
      // Create upload directory if it doesn't exist
      $upload_dir = 'uploads/prescriptions/temp/';
      if (!file_exists($upload_dir)) {
         mkdir($upload_dir, 0755, true);
      }
      
      // Generate secure filename
      $file_ext = strtolower(pathinfo($prescription_file, PATHINFO_EXTENSION));
      $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
      
      if(!in_array($file_ext, $allowed_ext)){
         $message[] = 'Invalid file type. Only JPG, PNG, PDF allowed';
      } else {
         $new_filename = 'rx_'.bin2hex(random_bytes(8)).'.'.$file_ext;
         $prescription_destination = $upload_dir . $new_filename;
         
         if(move_uploaded_file($prescription_tmp_name, $prescription_destination)){
            // Store data in session for later use
            $_SESSION['prescription_data'] = [
               'doctor_name' => $doctor_name,
               'patient_name' => $patient_name,
               'patient_email' => $patient_email,
               'patient_phone' => $patient_phone,
               'notes' => $notes,
               'file_name' => $new_filename
            ];
            
            // Redirect to recipient details page
            header('Location: prescription_recipient.php');
            exit;
         } else {
            $message[] = 'Failed to upload file. Please try again.';
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
   <title>Upload Prescription | Syokichem Pharmaceuticals Ltd.</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <style>
      :root {
         --primary-green: #8BC34A;
         --dark-green: #689F38;
         --primary-yellow: #FFEB3B;
         --dark-yellow: #FBC02D;
         --pure-white: #FFFFFF;
         --text-dark: #212121;
         --light-gray: #f5f5f5;
         --medium-gray: #e0e0e0;
         --dark-gray: #757575;
      }
      
      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Poppins', sans-serif;
      }
      
      body {
         font-size: 1.6rem;
         background-color: var(--light-gray);
         color: var(--text-dark);
      }
      
      html {
         font-size: 62.5%;
      }
      
      .prescription-container {
         max-width: 800px;
         margin: 4rem auto;
         padding: 3rem;
         background: var(--pure-white);
         border-radius: 12px;
         box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      }
      
      .prescription-header {
         text-align: center;
         margin-bottom: 3rem;
      }
      
      .prescription-header h3 {
         font-size: 2.5rem;
         color: var(--dark-green);
         margin-bottom: 1rem;
         font-weight: 700;
      }
      
      .prescription-header p {
         color: var(--dark-gray);
         font-size: 1.6rem;
      }
      
      .progress-steps {
         display: flex;
         justify-content: space-between;
         margin-bottom: 3rem;
         position: relative;
      }
      
      .progress-steps::before {
         content: '';
         position: absolute;
         top: 15px;
         left: 0;
         right: 0;
         height: 3px;
         background: var(--medium-gray);
         z-index: 1;
      }
      
      .step {
         display: flex;
         flex-direction: column;
         align-items: center;
         position: relative;
         z-index: 2;
      }
      
      .step-number {
         width: 30px;
         height: 30px;
         border-radius: 50%;
         background: var(--medium-gray);
         color: var(--pure-white);
         display: flex;
         align-items: center;
         justify-content: center;
         font-weight: 600;
         margin-bottom: 0.5rem;
      }
      
      .step.active .step-number {
         background: var(--primary-green);
      }
      
      .step.completed .step-number {
         background: var(--dark-green);
      }
      
      .step-label {
         font-size: 1.2rem;
         color: var(--dark-gray);
         text-align: center;
      }
      
      .step.active .step-label {
         color: var(--text-dark);
         font-weight: 600;
      }
      
      .form-group {
         margin-bottom: 2rem;
      }
      
      .form-group label {
         display: block;
         margin-bottom: 0.8rem;
         font-weight: 600;
         color: var(--text-dark);
         font-size: 1.6rem;
      }
      
      .form-control {
         width: 100%;
         padding: 1.2rem 1.5rem;
         border: 2px solid var(--medium-gray);
         border-radius: 8px;
         font-size: 1.6rem;
         transition: all 0.3s ease;
      }
      
      .form-control:focus {
         border-color: var(--primary-green);
         outline: none;
         box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.2);
      }
      
      textarea.form-control {
         min-height: 120px;
         resize: vertical;
      }
      
      .file-upload-wrapper {
         position: relative;
         margin-bottom: 2rem;
      }
      
      .file-upload-label {
         display: flex;
         flex-direction: column;
         align-items: center;
         padding: 3rem 2rem;
         border: 2px dashed var(--medium-gray);
         border-radius: 8px;
         cursor: pointer;
         transition: all 0.3s ease;
         background: var(--light-gray);
      }
      
      .file-upload-label:hover {
         border-color: var(--primary-green);
         background: rgba(139, 195, 74, 0.05);
      }
      
      .file-upload-label i {
         font-size: 4rem;
         color: var(--primary-green);
         margin-bottom: 1.5rem;
      }
      
      .file-upload-label h4 {
         margin-bottom: 0.5rem;
         color: var(--text-dark);
         font-size: 1.6rem;
         font-weight: 600;
      }
      
      .file-upload-label p {
         color: var(--dark-gray);
         font-size: 1.4rem;
         text-align: center;
         margin-bottom: 0.5rem;
      }
      
      .file-upload-input {
         position: absolute;
         left: 0;
         top: 0;
         opacity: 0;
         width: 100%;
         height: 100%;
         cursor: pointer;
      }
      
      .file-name {
         margin-top: 1rem;
         font-size: 1.4rem;
         color: var(--primary-green);
         font-weight: 500;
      }
      
      .btn {
         width: 100%;
         padding: 1.5rem;
         background: var(--primary-green);
         color: var(--text-dark);
         border: none;
         border-radius: 8px;
         font-size: 1.6rem;
         cursor: pointer;
         transition: all 0.3s ease;
         font-weight: 600;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 1rem;
      }
      
      .btn:hover {
         background: var(--dark-green);
         color: var(--pure-white);
         transform: translateY(-2px);
         box-shadow: 0 5px 15px rgba(139, 195, 74, 0.4);
      }
      
      .btn:active {
         transform: translateY(0);
      }
      
      .message {
         position: fixed;
         top: 20px;
         right: 20px;
         padding: 1.5rem 2rem;
         border-radius: 8px;
         color: var(--pure-white);
         font-weight: 500;
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
         display: flex;
         align-items: center;
         justify-content: space-between;
         z-index: 1000;
         max-width: 400px;
         font-size: 1.6rem;
         animation: slideIn 0.5s ease forwards;
      }
      
      .message.error {
         background: #f44336;
      }
      
      .message i {
         margin-left: 1.5rem;
         cursor: pointer;
         font-size: 1.8rem;
         opacity: 0.8;
         transition: opacity 0.2s ease;
      }
      
      .message i:hover {
         opacity: 1;
      }
      
      @keyframes slideIn {
         from {
            transform: translateX(100%);
            opacity: 0;
         }
         to {
            transform: translateX(0);
            opacity: 1;
         }
      }
      
      @media (max-width: 768px) {
         .prescription-container {
            padding: 2rem;
            margin: 2rem;
         }
         
         .progress-steps {
            margin-bottom: 2rem;
         }
         
         .step-label {
            font-size: 1rem;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="prescription-container">
   <div class="prescription-header">
      <h3>Upload Your Prescription</h3>
      <p>Upload a clear photo or scan of your prescription for our pharmacists to review</p>
   </div>
   
   <div class="progress-steps">
      <div class="step active">
         <div class="step-number">1</div>
         <div class="step-label">Prescription Details</div>
      </div>
      <div class="step">
         <div class="step-number">2</div>
         <div class="step-label">Recipient Info</div>
      </div>
      <div class="step">
         <div class="step-number">3</div>
         <div class="step-label">Payment</div>
      </div>
      <div class="step">
         <div class="step-number">4</div>
         <div class="step-label">Complete</div>
      </div>
   </div>
   
   <form class="prescription-form" action="" method="post" enctype="multipart/form-data">
      <?php
      if(!empty($message)){
         foreach($message as $msg){
            echo '
            <div class="message error">
               <span>'.htmlspecialchars($msg, ENT_QUOTES, 'UTF-8').'</span>
               <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
            ';
         }
      }
      ?>
      
      <div class="form-group">
         <label for="doctor_name">Doctor's Full Name</label>
         <input type="text" name="doctor_name" id="doctor_name" class="form-control" placeholder="Dr. John Smith" required>
      </div>
      
      <div class="form-group">
         <label for="patient_name">Patient's Full Name</label>
         <input type="text" name="patient_name" id="patient_name" class="form-control" placeholder="Your full name" required>
      </div>
      
      <div class="form-group">
         <label for="patient_email">Patient's Email</label>
         <input type="email" name="patient_email" id="patient_email" class="form-control" placeholder="Your email address" required>
      </div>
      
      <div class="form-group">
         <label for="patient_phone">Patient's Phone</label>
         <input type="text" name="patient_phone" id="patient_phone" class="form-control" placeholder="Your phone number" required>
      </div>
      
      <div class="form-group">
         <label for="notes">Additional Notes (Optional)</label>
         <textarea name="notes" id="notes" class="form-control" placeholder="Any special instructions or notes for our pharmacists..."></textarea>
      </div>
      
      <div class="file-upload-wrapper">
         <label class="file-upload-label" id="fileLabel">
            <i class="fas fa-file-upload"></i>
            <h4>Select Prescription File</h4>
            <p>Drag & drop or click to browse</p>
            <p>Supports: JPG, PNG, PDF (Max 2MB)</p>
            <span class="file-name" id="fileName">No file selected</span>
            <input type="file" name="prescription_file" id="fileInput" class="file-upload-input" accept=".pdf,.jpg,.jpeg,.png" required>
         </label>
      </div>
      
      <button type="submit" name="submit_prescription" class="btn">
         <i class="fas fa-arrow-right"></i> Continue to Recipient Info
      </button>
   </form>
</section>

<script>
   // File input display handler
   document.getElementById('fileInput').addEventListener('change', function(e) {
      const fileName = this.files[0] ? this.files[0].name : 'No file selected';
      document.getElementById('fileName').textContent = fileName;
      
      if(this.files[0]) {
         document.getElementById('fileLabel').style.borderColor = 'var(--primary-green)';
      }
   });
</script>

<?php include 'components/footer.php'; ?>
</body>
</html>