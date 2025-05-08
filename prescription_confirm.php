<?php
include 'components/connect.php';
session_start();

// Redirect if previous data not set
if(!isset($_SESSION['prescription_data']) || !isset($_SESSION['recipient_data']) || !isset($_SESSION['payment_data'])){
   header('Location: prescription_upload.php');
   exit;
}

$message = [];

if(isset($_POST['confirm_order'])){
   // Get all data from session
   $prescription_data = $_SESSION['prescription_data'];
   $recipient_data = $_SESSION['recipient_data'];
   $payment_data = $_SESSION['payment_data'];
   
   // Get user ID if logged in
   $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
   
   try {
      // Begin transaction
      $conn->beginTransaction();
      
      // Move file from temp to permanent location if needed
      $temp_path = 'uploads/prescriptions/temp/' . $prescription_data['file_name'];
      $perm_path = 'uploads/prescriptions/' . $prescription_data['file_name'];
      if (file_exists($temp_path)) {
         if (!rename($temp_path, $perm_path)) {
            throw new Exception('Failed to move prescription file from temp to permanent location.');
         }
      }
      // Verify the file exists in the permanent location
      if (!file_exists($perm_path)) {
          throw new Exception('Prescription file not found at: ' . $perm_path);
      }
      // Proceed directly to database insertion since file is already in place
      $insert_order = $conn->prepare("INSERT INTO `prescriptions` 
          (user_id, doctor_name, patient_name, patient_email, patient_phone, prescription_file, notes, 
          recipient_name, recipient_email, recipient_phone, recipient_dob, recipient_gender, delivery_address, special_instructions,
          payment_method, insurance_provider, insurance_number, status, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
      
      $insert_order->execute([
         $user_id,
         $prescription_data['doctor_name'],
         $prescription_data['patient_name'],
         $prescription_data['patient_email'],
         $prescription_data['patient_phone'],
         $prescription_data['file_name'],
         $prescription_data['notes'],
         $recipient_data['full_name'],
         $recipient_data['recipient_email'],
         $recipient_data['recipient_phone'],
         $recipient_data['date_of_birth'],
         $recipient_data['gender'],
         $recipient_data['delivery_address'],
         $recipient_data['special_instructions'],
         $payment_data['payment_method'],
         $payment_data['insurance_provider'] ?? null,
         $payment_data['insurance_number'] ?? null
      ]);
      
      $order_id = $conn->lastInsertId();
      
      // Commit transaction
      $conn->commit();
      
      // Get user phone if logged in
      $phone = '';
      if($user_id){
         $select_user = $conn->prepare("SELECT phone FROM `users` WHERE id = ?");
         $select_user->execute([$user_id]);
         $user = $select_user->fetch(PDO::FETCH_ASSOC);
         $phone = $user['phone'];
      }
      
      // Send SMS (simulated - in production use an SMS API)
      $sms_message = "Thank you for your prescription order #$order_id. We're processing it and will contact you shortly.";
      // In production: send_sms($phone, $sms_message);
      
      // Clear session data
      unset($_SESSION['prescription_data']);
      unset($_SESSION['recipient_data']);
      unset($_SESSION['payment_data']);
      
      // Redirect to success page
      header('Location: prescription_success.php?id='.$order_id);
      exit;
      
   } catch(Exception $e) {
      $conn->rollBack();
      $message[] = 'An error occurred: ' . $e->getMessage();
   }
}

// Format date of birth for display
$recipient_data = $_SESSION['recipient_data'];
$dob = new DateTime($recipient_data['date_of_birth']);
$formatted_dob = $dob->format('F j, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Confirm Order | Syokichem Pharmaceuticals Ltd.</title>
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
         line-height: 1.6;
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
      
      .order-summary {
         background: var(--light-gray);
         border-radius: 8px;
         padding: 2rem;
         margin-bottom: 3rem;
      }
      
      .summary-section {
         margin-bottom: 2rem;
      }
      
      .summary-section:last-child {
         margin-bottom: 0;
      }
      
      .summary-title {
         font-size: 1.8rem;
         color: var(--dark-green);
         margin-bottom: 1rem;
         padding-bottom: 0.5rem;
         border-bottom: 2px solid var(--medium-gray);
      }
      
      .summary-row {
         display: flex;
         margin-bottom: 0.8rem;
      }
      
      .summary-label {
         font-weight: 600;
         width: 150px;
         color: var(--dark-gray);
      }
      
      .summary-value {
         flex: 1;
      }
      
      .prescription-preview {
         margin-top: 1rem;
         padding: 1rem;
         background: var(--pure-white);
         border-radius: 5px;
         border: 1px solid var(--medium-gray);
      }
      
      .prescription-preview img {
         max-width: 100%;
         height: auto;
         border-radius: 5px;
      }
      
      .btn-group {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 1.5rem;
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
      
      .btn-secondary {
         background: var(--medium-gray);
         color: var(--text-dark);
      }
      
      .btn-secondary:hover {
         background: var(--dark-gray);
         color: var(--pure-white);
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
         
         .btn-group {
            grid-template-columns: 1fr;
         }
         
         .summary-row {
            flex-direction: column;
         }
         
         .summary-label {
            width: 100%;
            margin-bottom: 0.3rem;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="prescription-container">
   <div class="prescription-header">
      <h3>Confirm Your Order</h3>
      <p>Please review your information before submitting</p>
   </div>
   
   <div class="progress-steps">
      <div class="step completed">
         <div class="step-number"><i class="fas fa-check"></i></div>
         <div class="step-label">Prescription Details</div>
      </div>
      <div class="step completed">
         <div class="step-number"><i class="fas fa-check"></i></div>
         <div class="step-label">Recipient Info</div>
      </div>
      <div class="step completed">
         <div class="step-number"><i class="fas fa-check"></i></div>
         <div class="step-label">Payment</div>
      </div>
      <div class="step active">
         <div class="step-number">4</div>
         <div class="step-label">Complete</div>
      </div>
   </div>
   
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
   
   <div class="order-summary">
      <div class="summary-section">
         <div class="summary-title">Prescription Details</div>
         <div class="summary-row">
            <div class="summary-label">Doctor's Name:</div>
            <div class="summary-value"><?= htmlspecialchars($_SESSION['prescription_data']['doctor_name']) ?></div>
         </div>
         <div class="summary-row">
            <div class="summary-label">Patient's Name:</div>
            <div class="summary-value"><?= htmlspecialchars($_SESSION['prescription_data']['patient_name']) ?></div>
         </div>
         <div class="summary-row">
            <div class="summary-label">Patient Email:</div>
            <div class="summary-value"><?= htmlspecialchars($_SESSION['prescription_data']['patient_email']) ?></div>
         </div>
         <div class="summary-row">
            <div class="summary-label">Patient Phone:</div>
            <div class="summary-value"><?= htmlspecialchars($_SESSION['prescription_data']['patient_phone']) ?></div>
         </div>
         <?php if(!empty($_SESSION['prescription_data']['notes'])): ?>
         <div class="summary-row">
            <div class="summary-label">Notes:</div>
            <div class="summary-value"><?= htmlspecialchars($_SESSION['prescription_data']['notes']) ?></div>
         </div>
         <?php endif; ?>
         <div class="summary-row">
            <div class="summary-label">Prescription:</div>
            <div class="summary-value">
               <?= htmlspecialchars($_SESSION['prescription_data']['file_name']) ?>
               <div class="prescription-preview">
                  <?php 
                  $file_ext = pathinfo($_SESSION['prescription_data']['file_name'], PATHINFO_EXTENSION);
                  $temp_path = 'uploads/prescriptions/temp/' . $_SESSION['prescription_data']['file_name'];
                  if(in_array($file_ext, ['jpg', 'jpeg', 'png']) && file_exists($temp_path)): ?>
                     <img src="<?= $temp_path ?>" alt="Prescription preview">
                  <?php else: ?>
                     <i class="fas fa-file-pdf" style="font-size: 3rem; color: var(--primary-green);"></i> <?= strtoupper($file_ext) ?> File
                  <?php endif; ?>
               </div>
            </div>
         </div>
      </div>
      
      <div class="summary-section">
         <div class="summary-title">Recipient Information</div>
         <div class="summary-row">
            <div class="summary-label">Full Name:</div>
            <div class="summary-value"><?= htmlspecialchars($recipient_data['full_name']) ?></div>
         </div>
         <div class="summary-row">
            <div class="summary-label">Date of Birth:</div>
            <div class="summary-value"><?= $formatted_dob ?></div>
         </div>
         <div class="summary-row">
            <div class="summary-label">Gender:</div>
            <div class="summary-value"><?= htmlspecialchars($recipient_data['gender']) ?></div>
         </div>
         <div class="summary-row">
            <div class="summary-label">Delivery Address:</div>
            <div class="summary-value"><?= htmlspecialchars($recipient_data['delivery_address']) ?></div>
         </div>
         <div class="summary-row">
            <div class="summary-label">Recipient Email:</div>
            <div class="summary-value"><?= htmlspecialchars($recipient_data['recipient_email']) ?></div>
         </div>
         <div class="summary-row">
            <div class="summary-label">Recipient Phone:</div>
            <div class="summary-value"><?= htmlspecialchars($recipient_data['recipient_phone']) ?></div>
         </div>
         <?php if(!empty($recipient_data['special_instructions'])): ?>
         <div class="summary-row">
            <div class="summary-label">Special Instructions:</div>
            <div class="summary-value"><?= htmlspecialchars($recipient_data['special_instructions']) ?></div>
         </div>
         <?php endif; ?>
      </div>
      
      <div class="summary-section">
         <div class="summary-title">Payment Information</div>
         <div class="summary-row">
            <div class="summary-label">Payment Method:</div>
            <div class="summary-value">
               <?= ($_SESSION['payment_data']['payment_method'] == 'self_pay') ? 'Self Pay' : 'Insurance' ?>
            </div>
         </div>
         <?php if($_SESSION['payment_data']['payment_method'] == 'insurance'): ?>
         <div class="summary-row">
            <div class="summary-label">Insurance Provider:</div>
            <div class="summary-value"><?= htmlspecialchars($_SESSION['payment_data']['insurance_provider']) ?></div>
         </div>
         <div class="summary-row">
            <div class="summary-label">Insurance Number:</div>
            <div class="summary-value"><?= htmlspecialchars($_SESSION['payment_data']['insurance_number']) ?></div>
         </div>
         <?php endif; ?>
      </div>
   </div>
   
   <form action="" method="post">
      <div class="btn-group">
         <a href="prescription_payment.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
         </a>
         <button type="submit" name="confirm_order" class="btn">
            <i class="fas fa-check-circle"></i> Confirm Order
         </button>
      </div>
   </form>
</section>

<?php include 'components/footer.php'; ?>
</body>
</html>