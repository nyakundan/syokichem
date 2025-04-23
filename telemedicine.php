<?php
include 'components/connect.php';
session_start();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (isset($_POST['book_consultation'])) {
    $consultation_type = filter_var($_POST['consultation_type'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $consultation_date = $_POST['consultation_date'];
    $consultation_time = $_POST['consultation_time'];
    $symptoms = filter_var($_POST['symptoms'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if (empty($consultation_type) || empty($consultation_date) || empty($consultation_time)) {
        $message[] = 'Please fill all required fields';
    } else {
        $insert_consultation = $conn->prepare("INSERT INTO `consultations` 
            (user_id, consultation_type, consultation_date, consultation_time, symptoms, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $insert_consultation->execute([
            $user_id,
            $consultation_type,
            $consultation_date, 
            $consultation_time, 
            $symptoms
        ]);
        
        $message[] = 'Consultation booked successfully! We will contact you shortly.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Telemedicine Consultation - Syokichem</title>
   
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
   <!-- Flatpickr for date selection -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
   
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
      
      .consultation-container {
         max-width: 800px;
         margin: 4rem auto;
         padding: 0 2rem;
      }
      
      .consultation-card {
         background: var(--pure-white);
         border-radius: 12px;
         overflow: hidden;
         box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
         transition: transform 0.3s ease, box-shadow 0.3s ease;
      }
      
      .consultation-card:hover {
         transform: translateY(-5px);
         box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
      }
      
      .consultation-header {
         background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%);
         color: var(--pure-white);
         padding: 3rem 2rem;
         text-align: center;
      }
      
      .consultation-header h1 {
         font-size: 2.8rem;
         margin-bottom: 1rem;
         font-weight: 700;
      }
      
      .consultation-header p {
         font-size: 1.6rem;
         opacity: 0.9;
      }
      
      .consultation-body {
         padding: 3rem;
      }
      
      .form-group {
         margin-bottom: 2.5rem;
         position: relative;
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
         background-color: var(--pure-white);
      }
      
      .form-control:focus {
         border-color: var(--primary-green);
         outline: none;
         box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.2);
      }
      
      select.form-control {
         appearance: none;
         background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
         background-repeat: no-repeat;
         background-position: right 1rem center;
         background-size: 16px 12px;
      }
      
      textarea.form-control {
         min-height: 120px;
         resize: vertical;
      }
      
      .btn {
         width: 100%;
         padding: 1.5rem;
         background: linear-gradient(to right, var(--primary-green), var(--dark-green));
         background-color: var(--primary-green);
         color: var(--text-dark);
         border: none;
         border-radius: 8px;
         font-size: 1.6rem;
         cursor: pointer;
         transition: all 0.3s ease;
         font-weight: 600;
         text-transform: uppercase;
         letter-spacing: 0.5px;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 1rem;
      }
      
      .btn:hover {
         background: linear-gradient(to right, var(--dark-green), var(--primary-green));
         box-shadow: 0 5px 15px rgba(139, 195, 74, 0.4);
         transform: translateY(-2px);
      }
      
      .btn:active {
         transform: translateY(0);
      }
      
      .form-grid {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 2rem;
         margin-bottom: 2.5rem;
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
      
      .message.success {
         background: var(--primary-green);
         color: var(--text-dark);
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
      
      @keyframes fadeOut {
         to {
            opacity: 0;
            visibility: hidden;
         }
      }
      
      /* Floating label effect */
      .floating-label {
         position: relative;
      }
      
      .floating-label label {
         position: absolute;
         top: 1.2rem;
         left: 1.5rem;
         color: var(--dark-gray);
         transition: all 0.3s ease;
         pointer-events: none;
         background: var(--pure-white);
         padding: 0 0.5rem;
      }
      
      .floating-label .form-control:focus + label,
      .floating-label .form-control:not(:placeholder-shown) + label {
         top: -0.8rem;
         left: 1rem;
         font-size: 1.2rem;
         color: var(--primary-green);
      }
      
      @media (max-width: 768px) {
         .consultation-container {
            padding: 1.5rem;
            margin: 2rem auto;
         }
         
         .consultation-body {
            padding: 2rem;
         }
         
         .form-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
         }
         
         .consultation-header h1 {
            font-size: 2.4rem;
         }
      }
   </style>
</head>
<body>

<?php include 'components/user_header.php'; ?>

<div class="consultation-container">
   <div class="consultation-card">
      <div class="consultation-header">
         <h1>Book a Telemedicine Consultation</h1>
         <p>Get professional medical advice from the comfort of your home</p>
      </div>
      
      <div class="consultation-body">
         <?php
         if(isset($message)){
            foreach($message as $msg){
               echo '
               <div class="message '.($msg == 'Please fill all required fields' ? 'error' : 'success').'">
                  <span>'.htmlspecialchars($msg, ENT_QUOTES, 'UTF-8').'</span>
                  <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
               </div>
               ';
            }
         }
         ?>
         
         <form action="" method="post">
            <div class="form-group floating-label">
               <select name="consultation_type" id="consultation_type" class="form-control" required>
                  <option value="" selected disabled></option>
                  <option value="General Consultation">General Consultation</option>
                  <option value="Chronic Condition">Chronic Condition Management</option>
                  <option value="Pediatric Consultation">Pediatric Consultation</option>
                  <option value="Women's Health">Women's Health</option>
                  <option value="Mental Health">Mental Health Consultation</option>
                  <option value="Follow-up Consultation">Follow-up Consultation</option>
               </select>
               <label for="consultation_type">Consultation Type</label>
            </div>
            
            <div class="form-grid">
               <div class="form-group floating-label">
                  <input type="text" name="consultation_date" id="consultation-date" class="form-control" placeholder=" " required>
                  <label for="consultation-date">Consultation Date</label>
               </div>
               
               <div class="form-group floating-label">
                  <select name="consultation_time" id="consultation_time" class="form-control" required>
                     <option value="" selected disabled></option>
                     <option value="09:00-10:00">09:00 AM - 10:00 AM</option>
                     <option value="10:00-11:00">10:00 AM - 11:00 AM</option>
                     <option value="11:00-12:00">11:00 AM - 12:00 PM</option>
                     <option value="14:00-15:00">02:00 PM - 03:00 PM</option>
                     <option value="15:00-16:00">03:00 PM - 04:00 PM</option>
                     <option value="16:00-17:00">04:00 PM - 05:00 PM</option>
                  </select>
                  <label for="consultation_time">Time Slot</label>
               </div>
            </div>
            
            <div class="form-group floating-label">
               <textarea name="symptoms" id="symptoms" class="form-control" placeholder=" "></textarea>
               <label for="symptoms">Symptoms / Notes</label>
            </div>
            
            <button type="submit" name="book_consultation" class="btn">
               <i class="fas fa-calendar-check"></i> Schedule Consultation
            </button>
         </form>
      </div>
   </div>
</div>

<?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
   // Initialize date picker with config
   flatpickr("#consultation-date", {
      minDate: "today",
      dateFormat: "Y-m-d",
      disable: [
         function(date) {
            return (date.getDay() === 0 || date.getDay() === 6);
         }
      ]
   });
   
   // Auto-close messages after 5 seconds
   document.addEventListener('DOMContentLoaded', function() {
      const messages = document.querySelectorAll('.message');
      messages.forEach(msg => {
         setTimeout(() => {
            msg.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => msg.remove(), 500);
         }, 5000);
         
         msg.querySelector('i').addEventListener('click', () => {
            msg.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => msg.remove(), 500);
         });
      });
   });
</script>

<script src="js/script.js"></script>
</body>
</html>