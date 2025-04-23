<?php
$page_title = 'Returns & Refunds - Syokichem Pharmaceuticals';
include 'components/connect.php';
//include 'components/header.php';
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
        .returns-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .returns-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .returns-header h1 {
            font-size: 2.5rem;
            color: #006837;
            margin-bottom: 1rem;
        }
        
        .returns-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .effective-date {
            font-style: italic;
            color: #666;
            text-align: right;
            margin-bottom: 2rem;
        }
        
        .returns-content {
            line-height: 1.8;
            color: #444;
        }
        
        .returns-section {
            margin-bottom: 2.5rem;
        }
        
        .returns-section h2 {
            font-size: 1.5rem;
            color: #004d29;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .returns-section h3 {
            font-size: 1.2rem;
            color: #006837;
            margin: 1.5rem 0 0.5rem;
        }
        
        .returns-section ul, .returns-section ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .returns-section li {
            margin-bottom: 0.8rem;
        }
        
        .process-steps {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .step-card {
            flex: 1 1 200px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #006837;
        }
        
        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #006837;
            color: white;
            border-radius: 50%;
            line-height: 40px;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .step-title {
            font-weight: 600;
            color: #004d29;
            margin-bottom: 0.5rem;
        }
        
        .contact-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        
        .highlight-box {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1.5rem 0;
        }
        
        .non-returnable {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 1rem;
            margin: 1.5rem 0;
        }
        
        @media (max-width: 768px) {
            .returns-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .returns-header h1 {
                font-size: 2rem;
            }
            
            .process-steps {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="returns-container">
    <div class="returns-header">
        <h1>Returns & Refunds Policy</h1>
        <p>SYOKICHEM PHARMACEUTICALS LTD - Customer Satisfaction Guarantee</p>
        <div class="effective-date">Effective as of 24th December 2024</div>
    </div>
    
    <div class="returns-content">
        <div class="returns-section">
            <p>At SYOKICHEM, Kenya, we value our customers and strive to ensure your satisfaction with every purchase. However, due to the sensitive nature of pharmaceutical products, we have established a strict yet customer-friendly return policy to protect public health and comply with legal requirements.</p>
        </div>
        
        <div class="returns-section">
            <h2>Our Returns Process</h2>
            <div class="process-steps">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-title">Report Issue</div>
                    <p>Contact us within 24 hours of delivery</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-title">Get Approval</div>
                    <p>Wait for our team's inspection approval</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-title">Return Item</div>
                    <p>Deliver to our facility for inspection</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-title">Refund Processed</div>
                    <p>Receive refund within 7-14 business days</p>
                </div>
            </div>
        </div>
        
        <div class="returns-section">
            <h2>1. Customer Rights</h2>
            <p>Your rights under this Returns & Refunds Policy are in addition to any legal rights you may have under the Consumer Protection Act or other applicable laws in Kenya. Our goal is to address your concerns promptly while adhering to regulatory standards.</p>
        </div>
        
        <div class="returns-section">
            <h2>2. Non-Returnable Policy</h2>
            <div class="non-returnable">
                <p><strong>Important:</strong> For public safety, products cannot be returned once they leave our premises. However, certain exceptions apply under the conditions outlined below.</p>
            </div>
        </div>
        
        <div class="returns-section">
            <h2>3. Eligibility for Returns or Refunds</h2>
            <p>A faulty, damaged, or expired product may qualify for an exchange or refund if it meets all the following conditions:</p>
            <ol>
                <li><strong>Time Frame:</strong> The product was purchased or delivered within the last 48 hours.</li>
                <li><strong>Original Condition:</strong> The product remains unused, unopened, and in its original packaging.</li>
                <li><strong>Proof of Purchase:</strong> You must provide the original receipt, invoice, or other valid proof of purchase.</li>
                <li><strong>Contact Period:</strong> You have contacted SYOKICHEM customer support team within 24 hours of delivery to initiate the return process.</li>
                <li><strong>Product Category:</strong> The product is not categorized as a prescription medication, which is non-returnable under Kenyan law.</li>
            </ol>
        </div>
        
        <div class="returns-section">
            <h2>4. Returns or Refunds Not Permitted</h2>
            <div class="highlight-box">
                <p>We regret to inform you that we cannot process returns or refunds in the following cases:</p>
                <ul>
                    <li>If you change your mind after purchase.</li>
                    <li>If you purchased an item by mistake.</li>
                    <li>If the product is classified as a prescription medication or legally non-returnable.</li>
                </ul>
            </div>
        </div>
        
        <div class="returns-section">
            <h2>5. Return Process</h2>
            <p>To return an eligible product, follow these steps:</p>
            <ol>
                <li><strong>Contact Us:</strong> Notify our support team within 24 hours of delivery via:
                    <ul>
                        <li>Phone: +254792914662</li>
                        <li>Email: sales@syokichem.com</li>
                        <li>WhatsApp Chat</li>
                    </ul>
                </li>
                <li><strong>Inspection Approval:</strong> Once your request is reviewed, we will provide further instructions. Products are subject to inspection to confirm eligibility for a refund or exchange.</li>
                <li><strong>Delivery to Facility:</strong> Securely package the product and deliver it to SYOKICHEM, Reliance Center, Westlands, or the designated location provided by our team. Include the receipt or proof of purchase and indicate whether you prefer an exchange or refund.</li>
            </ol>
        </div>
        
        <div class="returns-section">
            <h2>6. Shipping and Handling Charges</h2>
            <ul>
                <li><strong>Non-Refundable Costs:</strong> Shipping fees incurred for returning a product are non-refundable.</li>
                <li><strong>Customer Responsibility:</strong> You are responsible for the costs of return shipping and bear the risk of loss or damage during transit.</li>
            </ul>
        </div>
        
        <div class="returns-section">
            <h2>7. Refund Processing</h2>
            <p>Once a returned product is received and inspected, refunds will be processed as follows:</p>
            <ol>
                <li>Refunds will be issued within 7–14 business days.</li>
                <li>Refunds will be credited back to your original payment method or issued as SYOKICHEM store credit, based on your preference.</li>
                <li>Refunds may take longer during peak periods, but we will notify you promptly if delays occur.</li>
            </ol>
        </div>
        
        <div class="returns-section">
            <h2>8. Damaged or Defective Products</h2>
            <p>If you receive a damaged or defective product, please notify us immediately. Eligible cases will be resolved with a replacement or refund after verification.</p>
        </div>
        
        <div class="returns-section">
            <h2>9. Product Recalls</h2>
            <p>In the rare event of a product recall due to safety or regulatory issues, SYOKICHEM will provide clear instructions for the return. You will be entitled to a full refund or replacement at no additional cost.</p>
        </div>
        
        <div class="returns-section">
            <h2>10. Partial Refunds</h2>
            <p>In special cases, partial refunds may be issued for products that do not fully meet the return criteria but are deemed eligible upon inspection. Contact our support team for more details.</p>
        </div>
        
        <div class="returns-section">
            <h2>11. Extended Return Periods for Loyalty Members</h2>
            <p>SYOKICHEM Members enjoy additional benefits, including:</p>
            <ul>
                <li>Extended return periods for eligible non-prescription items.</li>
                <li>Faster refund processing. For more information, visit the SYOKICHEM Membership Page.</li>
            </ul>
        </div>
        
        <div class="returns-section">
            <h2>12. Contact Options</h2>
            <div class="contact-info">
                <p>For any inquiries or assistance, please reach out to us through the following channels:</p>
                <p><strong>Phone:</strong> +254792914662</p>
                <p><strong>Email:</strong> sales@syokichem.com</p>
                <p><strong>Social Media Support:</strong> Message us on LinkedIn, Facebook, Instagram, or Twitter (@Syokichem Kenya).</p>
                <p><strong>Final Note:</strong> At SYOKICHEM, we strive to make every shopping experience seamless and reliable. If you have any feedback about our policy or service, we encourage you to contact us.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>