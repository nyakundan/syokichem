<?php
include 'components/connect.php';
session_start();

$message = [];

if(isset($_POST['submit_recipient'])){
   $full_name = isset($_POST['full_name']) ? htmlspecialchars(trim($_POST['full_name']), ENT_QUOTES, 'UTF-8') : '';
   $date_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
   $gender = isset($_POST['gender']) ? htmlspecialchars(trim($_POST['gender']), ENT_QUOTES, 'UTF-8') : '';
   $delivery_address = isset($_POST['delivery_address']) ? htmlspecialchars(trim($_POST['delivery_address']), ENT_QUOTES, 'UTF-8') : '';
   $special_instructions = isset($_POST['special_instructions']) ? htmlspecialchars(trim($_POST['special_instructions']), ENT_QUOTES, 'UTF-8') : '';
   
   // Validate inputs
   if(empty($full_name) || empty($date_of_birth) || empty($gender) || empty($delivery_address)){
      $message[] = 'Please fill all required fields';
   } else {
      // Store recipient data in session
      $_SESSION['recipient_data'] = [
         'full_name' => $full_name,
         'date_of_birth' => $date_of_birth,
         'gender' => $gender,
         'delivery_address' => $delivery_address,
         'special_instructions' => $special_instructions
      ];
      
      // Redirect to payment page
      header('Location: prescription_payment.php');
      exit;
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Recipient Information | Syokichem Pharmaceuticals Ltd.</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
   
      <style>
      /* Same root variables and base styles as previous file */
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
      
      .form-grid {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 2rem;
         margin-bottom: 2rem;
      }
      
      .radio-group {
         display: flex;
         gap: 2rem;
         margin-bottom: 2rem;
      }
      
      .radio-option {
         display: flex;
         align-items: center;
         gap: 0.5rem;
      }
      
      .radio-option input {
         width: auto;
      }
      
      @media (max-width: 768px) {
         .form-grid {
            grid-template-columns: 1fr;
         }
      }
   </style>
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="prescription-container">
   <div class="prescription-header">
      <h3>Recipient Information</h3>
      <p>Please provide details about who will receive the medication</p>
   </div>
   
   <div class="progress-steps">
      <div class="step completed">
         <div class="step-number"><i class="fas fa-check"></i></div>
         <div class="step-label">Prescription Details</div>
      </div>
      <div class="step active">
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
   
   <form class="prescription-form" action="" method="post">
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
         <label for="full_name">Full Name</label>
         <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Recipient's full name" required>
      </div>
      
      <div class="form-grid">
         <div class="form-group">
            <label for="date_of_birth">Date of Birth</label>
            <input type="text" name="date_of_birth" id="date_of_birth" class="form-control" placeholder="Select date" required>
         </div>
         
         <div class="form-group">
            <label>Gender</label>
            <div class="radio-group">
               <label class="radio-option">
                  <input type="radio" name="gender" value="Male" required> Male
               </label>
               <label class="radio-option">
                  <input type="radio" name="gender" value="Female"> Female
               </label>
               <label class="radio-option">
                  <input type="radio" name="gender" value="Other"> Other
               </label>
            </div>
         </div>
      </div>
      
      <div class="form-group">
         <label for="delivery_address">Delivery Address</label>
         <textarea name="delivery_address" id="delivery_address" class="form-control" placeholder="Full delivery address including postal code" required></textarea>
      </div>
      
      <div class="form-group">
         <label for="special_instructions">Special Instructions (Optional)</label>
         <textarea name="special_instructions" id="special_instructions" class="form-control" placeholder="Any special delivery instructions, medication preferences, etc."></textarea>
      </div>
      
      <button type="submit" name="submit_recipient" class="btn">
         <i class="fas fa-arrow-right"></i> Continue to Payment
      </button>
   </form>
</section>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
   // Initialize date picker
   flatpickr("#date_of_birth", {
      dateFormat: "Y-m-d",
      maxDate: "today"
   });
</script>

<?php include 'components/footer.php'; ?>
</body>
</html>