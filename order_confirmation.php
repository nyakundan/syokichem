<?php
include 'components/connect.php';
session_start();

//if(!isset($_SESSION['user_id'])){
  //  header('location:user_login.php');
   // exit;
//}

// Get the latest prescription order for the user or guest
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// If user_id is set, fetch the latest prescription for the logged-in user
if ($user_id) {
    $select_prescription = $conn->prepare("SELECT * FROM `prescriptions` WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $select_prescription->execute([$user_id]);
} else {
    // If user_id is not set, fetch the latest prescription for guests
    $select_prescription = $conn->prepare("SELECT * FROM `prescriptions` WHERE user_id IS NULL ORDER BY id DESC LIMIT 1");
    $select_prescription->execute();
}

$prescription = $select_prescription->fetch(PDO::FETCH_ASSOC);

// Get medical staff details if prescription exists
$medical_staff = null;
if($prescription && isset($prescription['medical_staff_id'])) {
    $select_staff = $conn->prepare("SELECT * FROM `medical_staff` WHERE id = ?");
    $select_staff->execute([$prescription['medical_staff_id']]);
    $medical_staff = $select_staff->fetch(PDO::FETCH_ASSOC);
}

// Get user details if user is logged in
$user_details = null;
if ($user_id) {
    $select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
    $select_user->execute([$user_id]);
    $user_details = $select_user->fetch(PDO::FETCH_ASSOC);
}

// Send confirmation email
if($prescription && !isset($_SESSION['email_sent'])) {
    sendPrescriptionEmail($prescription, $medical_staff, $user_details);
    $_SESSION['email_sent'] = true;
}

function sendPrescriptionEmail($prescription, $staff, $user) {
    $to = $user ? $user['email'] : 'guest@example.com'; // Default email for guests
    $subject = "Prescription Order Confirmation - SYOKICHEM";
    
    $message = '<!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #006837; color: white; padding: 20px; text-align: center; }
            .details { margin: 20px 0; border: 1px solid #ddd; padding: 15px; }
            .footer { margin-top: 20px; font-size: 0.9em; color: #777; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>SYOKICHEM Pharmaceuticals</h2>
                <h3>Prescription Order Confirmation</h3>
            </div>
            
            <p>Dear ' . ($user ? htmlspecialchars($user['name']) : 'Guest') . ',</p>
            <p>Thank you for your prescription order. Here are your order details:</p>
            
            <div class="details">
                <h4>Order Details</h4>
                <p><strong>Order Number:</strong> #' . $prescription['id'] . '</p>
                <p><strong>Date:</strong> ' . date('F j, Y, g:i a', strtotime($prescription['created_at'])) . '</p>
                <p><strong>Status:</strong> ' . ucfirst($prescription['status']) . '</p>
                
                <h4>Prescription Information</h4>
                <p><strong>Doctor:</strong> ' . htmlspecialchars($prescription['doctor_name']) . '</p>
                <p><strong>Patient:</strong> ' . htmlspecialchars($prescription['patient_name']) . '</p>
                <p><strong>Notes:</strong> ' . htmlspecialchars($prescription['notes']) . '</p>
                
                <h4>Delivery Information</h4>
                <p><strong>Recipient:</strong> ' . htmlspecialchars($prescription['recipient_name']) . '</p>
                <p><strong>Phone:</strong> ' . htmlspecialchars($prescription['recipient_phone']) . '</p>
                <p><strong>Address:</strong> ' . htmlspecialchars($prescription['delivery_address']) . '</p>
                
                <h4>Assigned Medical Staff</h4>
                <p><strong>Name:</strong> ' . ($staff ? htmlspecialchars($staff['name']) : 'Not assigned yet') . '</p>
                <p><strong>Specialization:</strong> ' . ($staff ? htmlspecialchars($staff['specialization']) : '') . '</p>
                
                <h4>Payment Information</h4>
                <p><strong>Method:</strong> ' . ucfirst($prescription['payment_method']) . '</p>
            </div>
            
            <p>Our medical staff will review your prescription and prepare your order. You will receive another email when your order has been processed.</p>
            
            <div class="footer">
                <p>SYOKICHEM Pharmaceuticals Ltd</p>
                <p>Katani Road, Syokimau</p>
                <p>Phone: +254 792 914 662</p>
            </div>
        </div>
    </body>
    </html>';
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: SYOKICHEM <noreply@syokichem.com>' . "\r\n";
    
    mail($to, $subject, $message, $headers);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - SYOKICHEM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary: #006837;
            --primary-light: #4CAF50;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: var(--dark);
        }
        
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary);
        }
        
        .confirmation-header h1 {
            color: var(--primary);
            font-size: 2.2rem;
        }
        
        .confirmation-header .icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .detail-card {
            background: var(--light);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .detail-card h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .prescription-info {
            margin: 2rem 0;
            padding: 1.5rem;
            background: var(--light);
            border-radius: 8px;
        }
        
        .prescription-info h2 {
            color: var(--primary);
            margin-top: 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-item strong {
            display: block;
            color: var(--primary);
            margin-bottom: 0.3rem;
        }
        
        .next-steps {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f0f8ff;
            border-radius: 8px;
        }
        
        .next-steps h2 {
            margin-top: 0;
            color: var(--primary);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/user_header.php'; ?>
    
    <div class="confirmation-container">
        <?php if($prescription): ?>
            <div class="confirmation-header">
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Prescription Order Confirmed!</h1>
                <p>Thank you for your order, <?= $user_details ? htmlspecialchars($user_details['name']) : 'Guest' ?></p>
            </div>
            
            <div class="order-details">
                <div class="detail-card">
                    <h3>Order Information</h3>
                    <p><strong>Order Number:</strong> #PR<?= $prescription['id'] ?></p>
                    <p><strong>Date:</strong> <?= date('F j, Y, g:i a', strtotime($prescription['created_at'])) ?></p>
                    <p><strong>Status:</strong> <?= ucfirst($prescription['status']) ?></p>
                </div>
                
                <div class="detail-card">
                    <h3>Delivery Information</h3>
                    <p><strong>Recipient:</strong> <?= htmlspecialchars($prescription['recipient_name']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($prescription['recipient_phone']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($prescription['delivery_address']) ?></p>
                </div>
            </div>
            
            <div class="prescription-info">
                <h2>Prescription Details</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Doctor's Name</strong>
                        <?= htmlspecialchars($prescription['doctor_name']) ?>
                    </div>
                    <div class="info-item">
                        <strong>Patient's Name</strong>
                        <?= htmlspecialchars($prescription['patient_name']) ?>
                    </div>
                    <div class="info-item">
                        <strong>Medical Notes</strong>
                        <?= htmlspecialchars($prescription['notes'] ?? 'No additional notes') ?>
                    </div>
                    <div class="info-item">
                        <strong>Assigned Staff</strong>
                        <?php if($medical_staff): ?>
                            <?= htmlspecialchars($medical_staff['name']) ?>
                            <?php if(isset($medical_staff['specialization']) && !empty($medical_staff['specialization'])): ?>
                                (<?= htmlspecialchars($medical_staff['specialization']) ?>)
                            <?php endif; ?>
                        <?php else: ?>
                            Not assigned yet
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <strong>Payment Method</strong>
                        <?= ucfirst($prescription['payment_method']) ?>
                    </div>
                </div>
            </div>
            
            <div class="next-steps">
                <h2>What Happens Next?</h2>
                <p>1. We've sent a confirmation email to <?= $user_details ? htmlspecialchars($user_details['email']) : 'guest@example.com' ?></p>
                <p>2. Our medical staff will review your prescription</p>
                <p>3. You'll receive another email when your order is ready</p>
                <p>4. Delivery typically takes 1-3 business days</p>
            </div>
            
            <div class="action-buttons">
                <a href="shop.php" class="btn btn-outline">Continue Shopping</a>
                <a href="orders.php?type=prescription" class="btn btn-primary">View My Orders</a>
            </div>
        <?php else: ?>
            <div class="confirmation-header">
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1>Order Not Found</h1>
                <p>We couldn't find the prescription order you're looking for.</p>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="prescription.php" class="btn btn-primary">Upload Prescription</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'components/footer.php'; ?>
</body>
</html>