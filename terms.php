<?php
$page_title = 'Terms & Conditions - Syokichem Pharmaceuticals';
include 'components/connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .terms-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .terms-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.2rem;
            border-bottom: 1px solid #eee;
        }
        .terms-header h1 {
            font-size: 2.3rem;
            color: #006837;
            margin-bottom: 1rem;
        }
        .terms-header p {
            color: #666;
            font-size: 1.1rem;
        }
        .terms-content {
            line-height: 1.8;
            color: #444;
        }
        .terms-section {
            margin-bottom: 2.5rem;
        }
        .terms-section h2 {
            font-size: 1.5rem;
            color: #004d29;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .terms-section ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        .terms-section li {
            margin-bottom: 0.5rem;
        }
        .company-info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .company-info-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            align-items: center;
        }
        .company-info-card li i {
            color: #006837;
            margin-right: 0.5rem;
        }
        @media (max-width: 768px) {
            .terms-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            .terms-header h1 {
                font-size: 1.7rem;
            }
            .company-info-card ul {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
<?php include 'components/user_header.php'; ?>
<div class="terms-container">
    <div class="terms-header">
        <h1>Terms & Conditions</h1>
        <p>Welcome to SYOKICHEM. Please read these terms and conditions carefully before using our website and services.</p>
    </div>
    <div class="company-info-card">
        <ul>
            <li><i class="fas fa-building"></i> <strong>SYOKICHEM</strong></li>
            <li><i class="fas fa-envelope"></i> <a href="mailto:sales@syokichem.com">sales@syokichem.com</a></li>
            <li><i class="fas fa-globe"></i> <a href="https://www.syokichem.com">www.syokichem.com</a></li>
            <li><i class="fas fa-phone-alt"></i> <a href="tel:+254792914662">+254792914662</a></li>
        </ul>
    </div>
    <div class="terms-content">
        <div class="terms-section">
            <h2>1. Acceptance of Terms</h2>
            <p>By accessing or using our website, you agree to be bound by these Terms & Conditions and our Privacy Policy.</p>
        </div>
        <div class="terms-section">
            <h2>2. Use of the Website</h2>
            <ul>
                <li>You must be at least 18 years old or have the involvement of a parent or guardian to use our services.</li>
                <li>You agree to provide accurate and complete information when placing orders or registering an account.</li>
                <li>Unauthorized use or access of the website is strictly prohibited.</li>
            </ul>
        </div>
        <div class="terms-section">
            <h2>3. Orders & Prescriptions</h2>
            <ul>
                <li>Prescription medications require a valid prescription from a licensed healthcare provider.</li>
                <li>We reserve the right to verify prescriptions and refuse service where necessary.</li>
                <li>All orders are subject to availability and confirmation.</li>
            </ul>
        </div>
        <div class="terms-section">
            <h2>4. Payments & Pricing</h2>
            <ul>
                <li>All prices are listed in Kenyan Shillings (KES) and are subject to change without notice.</li>
                <li>Payments must be made in full before delivery or collection.</li>
                <li>We accept M-Pesa, Visa, Mastercard, and other payment methods as displayed on the website.</li>
            </ul>
        </div>
        <div class="terms-section">
            <h2>5. Delivery & Returns</h2>
            <ul>
                <li>Delivery timelines are estimates and may vary due to circumstances beyond our control.</li>
                <li>Returns are accepted in accordance with our Refund Policy. Prescription medicines are not returnable unless faulty or supplied in error.</li>
            </ul>
        </div>
        <div class="terms-section">
            <h2>6. Limitation of Liability</h2>
            <ul>
                <li>SYOKICHEM is not liable for any indirect, incidental, or consequential damages arising from the use of our website or services.</li>
                <li>Information provided on the website is for general purposes and does not substitute professional medical advice.</li>
            </ul>
        </div>
        <div class="terms-section">
            <h2>7. Changes to Terms</h2>
            <p>We may update these Terms & Conditions at any time. Continued use of our website constitutes acceptance of the updated terms.</p>
        </div>
        <div class="terms-section">
            <h2>8. Contact Information</h2>
            <div class="company-info-card">
                <ul>
                    <li><i class="fas fa-building"></i> <strong>SYOKICHEM</strong></li>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:sales@syokichem.com">sales@syokichem.com</a></li>
                    <li><i class="fas fa-globe"></i> <a href="https://www.syokichem.com">www.syokichem.com</a></li>
                    <li><i class="fas fa-phone-alt"></i> <a href="tel:+254792914662">+254792914662</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
