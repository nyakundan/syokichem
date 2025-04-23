<?php
$page_title = 'Privacy Policy - Syokichem Pharmaceuticals';
include 'components/connect.php';
//include 'components/user_header.php';
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
        .privacy-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .privacy-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .privacy-header h1 {
            font-size: 2.5rem;
            color: #006837;
            margin-bottom: 1rem;
        }
        
        .privacy-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .privacy-content {
            line-height: 1.8;
            color: #444;
        }
        
        .privacy-section {
            margin-bottom: 2.5rem;
        }
        
        .privacy-section h2 {
            font-size: 1.5rem;
            color: #004d29;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .privacy-section h3 {
            font-size: 1.2rem;
            color: #006837;
            margin: 1.5rem 0 0.5rem;
        }
        
        .privacy-section ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .privacy-section li {
            margin-bottom: 0.5rem;
        }
        
        .effective-date {
            font-style: italic;
            color: #666;
            text-align: right;
            margin-bottom: 2rem;
        }
        
        .contact-info {
            background: #e8f5e9;
            padding: 2rem 1.5rem 2rem 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
            box-shadow: 0 2px 10px rgba(139,195,74,0.06);
        }
        
        @media (max-width: 768px) {
            .privacy-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .privacy-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="privacy-container">
    <div class="privacy-header">
        <h1>SYOKICHEM PRIVACY POLICY</h1>
        <p>Your trust is important to us. This policy explains how we handle your personal information.</p>
        <div class="effective-date">Effective Date: December 2, 2024</div>
    </div>
    
    <div class="privacy-content">
        <div class="privacy-section">
            <p>At SYOKICHEM, we value your trust and prioritize protecting your personal information. This Privacy Policy outlines how we collect, use, and safeguard your data when you visit our website or use our services.</p>
        </div>
        
        <div class="privacy-section">
            <h2>1. Information We Collect</h2>
            <p>We may collect the following types of personal data:</p>
            <ul>
                <li><strong>Contact Information:</strong> Name, email address, phone number, and delivery address.</li>
                <li><strong>Order Details:</strong> Prescription information, product orders, and transaction details.</li>
                <li><strong>Account Information:</strong> Login credentials and communication preferences.</li>
                <li><strong>Device Information:</strong> Browser type, IP address, and cookies to enhance your experience.</li>
            </ul>
        </div>
        
        <div class="privacy-section">
            <h2>2. How We Use Your Information</h2>
            <p>We use the information collected to:</p>
            <ul>
                <li>Process your orders and deliver medications.</li>
                <li>Verify prescriptions as required by law.</li>
                <li>Communicate order updates, promotions, and customer support.</li>
                <li>Improve our website, products, and services.</li>
                <li>Ensure compliance with legal obligations and safeguard our operations.</li>
            </ul>
        </div>
        
        <div class="privacy-section">
            <h2>3. How We Protect Your Information</h2>
            <p>SYOKICHEM implements advanced security measures to protect your personal data from unauthorized access, loss, or misuse. These include:</p>
            <ul>
                <li>Data encryption for secure transactions.</li>
                <li>Controlled access to sensitive information.</li>
                <li>Regular audits and updates to maintain data protection standards.</li>
            </ul>
        </div>
        
        <div class="privacy-section">
            <h2>4. Sharing Your Information</h2>
            <p>We only share your personal data with:</p>
            <ul>
                <li>Licensed healthcare professionals for prescription verification.</li>
                <li>Trusted third-party partners (e.g., delivery services) to fulfill your orders.</li>
                <li>Insurance providers, if applicable, for billing purposes.</li>
            </ul>
            <p>We do not sell or rent your information to third parties.</p>
        </div>
        
        <div class="privacy-section">
            <h2>5. Cookies and Tracking Technologies</h2>
            <p>Our website uses cookies to enhance functionality and provide a personalized experience. You can adjust your browser settings to refuse cookies, though some features may not function as intended.</p>
        </div>
        
        <div class="privacy-section">
            <h2>6. Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access, update, or correct your personal information.</li>
                <li>Request the deletion of your data (subject to legal requirements).</li>
                <li>Opt out of marketing communications at any time.</li>
            </ul>
            <p>To exercise these rights, please contact our support team at <strong>+254792914662</strong>.</p>
        </div>
        
        <div class="privacy-section">
            <h2>7. Changes to This Policy</h2>
            <p>We may update this Privacy Policy periodically to reflect changes in our practices or legal requirements. Any updates will be posted on our website with a revised effective date.</p>
        </div>
        
        <div class="privacy-section">
            <h2>8. Contact Us</h2>
            <div class="contact-info" style="background:#fff;padding:2rem 1.5rem 2rem 1.5rem;border-radius:10px;margin-top:2rem;box-shadow:0 2px 10px rgba(139,195,74,0.06);color:#222;">
                <p style="color:#004d29;font-weight:600;font-size:1.2rem;margin-bottom:1.2rem;">If you have any questions or concerns about our Privacy Policy, please reach out to:</p>
                <ul style="list-style:none;padding:0;margin:0;font-size:1.1rem;color:#222;">
                  <li style="margin-bottom:0.7rem;color:#222;"><i class="fas fa-building" style="color:#006837;margin-right:0.5rem;"></i> <strong style="color:#222;">SYOKICHEM</strong></li>
                  <li style="margin-bottom:0.7rem;color:#222;"><i class="fas fa-envelope" style="color:#006837;margin-right:0.5rem;"></i> <a href="mailto:sales@syokichem.com" style="color:#222;text-decoration:none;font-weight:600;">sales@syokichem.com</a></li>
                  <li style="margin-bottom:0.7rem;color:#222;"><i class="fas fa-globe" style="color:#006837;margin-right:0.5rem;"></i> <a href="https://www.syokichem.com" style="color:#222;text-decoration:none;font-weight:600;">www.syokichem.com</a></li>
                  <li style="margin-bottom:0.7rem;color:#222;"><i class="fas fa-phone-alt" style="color:#006837;margin-right:0.5rem;"></i> <a href="tel:+254792914662" style="color:#222;text-decoration:none;font-weight:700;">+254792914662</a></li>
                </ul>
                <p style="color:#004d29;margin-top:1.5rem;font-weight:500;">Thank you for trusting SYOKICHEM as your online pharmacy of choice.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>