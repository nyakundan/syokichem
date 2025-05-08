<?php
session_start();
include 'components/connect.php';

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>About Us - Syokichem Pharmaceuticals | Kenya's No.1 Reliable Online Pharmacy</title>

   <!-- SEO Meta Tags -->
   <meta name="description" content="Learn about Syokichem Pharmaceuticals - Kenya's  No.1 Reliable Online pharmacy with licensed pharmacists and genuine medicines.">
   <meta name="keywords" content="online pharmacy Kenya, prescription drugs, healthcare products, licensed pharmacists, Syokimau pharmacy, Katani Road pharmacy">
   <meta name="author" content="Syokichem Pharmaceuticals">

   <!-- Favicon -->
   <link rel="icon" href="images/favicon.ico" type="image/x-icon">

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   <!-- Swiper JS -->
   <link rel="stylesheet" href="https://unpkg.com/swiper@9/swiper-bundle.min.css" />

   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">
   
   <style>
      :root {
         --primary: #8BC34A; /* Updated to match lime-btn (primary-green) */
         --primary-light: #4CAF50;
         --secondary: #FFC107;
         --dark: #263238;
         --light: #f8f9fa;
         --gray: #757575;
         --primary-green: #8BC34A;
         --primary-yellow: #FFC107;
         --text-dark: #263238;
         --text-medium: #757575;
      }
      
      .about-hero {
         background: var(--primary-green);
         color: #fff;
         padding: 6rem 0 4rem;
         text-align: left;
      }
      .about-hero-grid {
         display: flex;
         flex-wrap: wrap;
         align-items: flex-start;
         gap: 4rem;
         justify-content: space-between;
      }
      .about-hero-info {
         flex: 1 1 420px;
         min-width: 340px;
         display: flex;
         flex-direction: column;
         gap: 2.2rem;
         background: rgba(255,255,255,0.09);
         border-radius: 14px;
         padding: 2.5rem 2rem;
         box-shadow: 0 4px 24px rgba(139,195,74,0.04);
      }
      .about-hero-info h2 {
         color: #fff;
         font-size: 2.4rem;
         margin-bottom: 0.8rem;
         letter-spacing: 0.01em;
      }
      .about-hero-info h3 {
         color: #FFC107;
         font-size: 1.5rem;
         margin-bottom: 0.5rem;
      }
      .about-hero-info p {
         color: #fff;
         font-size: 1.15rem;
         line-height: 1.7;
         margin-bottom: 0.8rem;
         letter-spacing: 0.01em;
      }
      .about-hero-info strong {
         color: #FFC107;
      }
      .about-hero-features {
         flex: 1 1 420px;
         min-width: 340px;
         display: flex;
         flex-direction: column;
         gap: 2.5rem;
      }
      .about-hero-features h1 {
         font-size: 2.2rem;
         color: #fff;
         margin-bottom: 1.2rem;
      }
      .features {
         display: flex;
         flex-direction: column;
         gap: 1.2rem;
      }
      .feature {
         display: flex;
         gap: 1.3rem;
         background: rgba(255,255,255,0.07);
         border-radius: 10px;
         padding: 1.2rem 1.4rem;
         align-items: flex-start;
         box-shadow: 0 2px 10px rgba(0,0,0,0.03);
         transition: transform 0.3s;
      }
      .feature:hover {
         transform: translateY(-3px);
         background: rgba(255,255,255,0.13);
      }
      .feature i {
         font-size: 2rem;
         color: #FFC107;
         min-width: 44px;
         text-align: center;
         padding-top: 5px;
      }
      .feature h3 {
         color: #fff;
         margin-bottom: 0.3rem;
         font-size: 1.18rem;
      }
      .feature p {
         color: #e6e6e6;
         line-height: 1.6;
         font-size: 1.02rem;
      }
      .about-team-section {
         display: flex;
         flex-wrap: wrap;
         align-items: flex-start;
         gap: 3.5rem;
         justify-content: space-between;
         margin-top: 4rem;
      }
      .about-team-info {
         flex: 1 1 340px;
         min-width: 320px;
         background: #fff;
         color: #263238;
         border-radius: 14px;
         padding: 2.5rem 2rem;
         box-shadow: 0 4px 24px rgba(139,195,74,0.08);
         display: flex;
         flex-direction: column;
         justify-content: center;
      }
      .about-team-info h2 {
         color: var(--primary-green);
         font-size: 2rem;
         margin-bottom: 1.2rem;
      }
      .about-team-info p {
         color: #263238;
         font-size: 1.13rem;
         line-height: 1.7;
      }
      .about-team-image {
         flex: 1 1 340px;
         min-width: 320px;
         display: flex;
         align-items: center;
         justify-content: center;
      }
      .about-team-image img {
         width: 100%;
         max-width: 400px;
         border-radius: 14px;
         box-shadow: 0 6px 24px rgba(139,195,74,0.08);
      }
      @media (max-width: 992px) {
         .about-hero-grid, .about-team-section {
            flex-direction: column;
            gap: 2.5rem;
         }
         .about-hero-info, .about-hero-features, .about-team-info, .about-team-image {
            min-width: 0;
         }
      }
      
      .about-section {
         padding: 5rem 0;
         background-color: var(--light);
      }
      
      .about-grid {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 3rem;
         align-items: center;
      }
      
      .about-content h2 {
         color: var(--primary-green);
         font-size: 2.2rem;
         margin-bottom: 2rem;
         position: relative;
         display: inline-block;
      }
      
      .about-content h2::after {
         content: '';
         position: absolute;
         bottom: -10px;
         left: 0;
         width: 60px;
         height: 4px;
         background: var(--primary-yellow);
      }
      
      .feature {
         display: flex;
         gap: 1.5rem;
         margin-bottom: 2rem;
         padding: 1.5rem;
         background: white;
         border-radius: 8px;
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
         transition: transform 0.3s ease;
      }
      
      .feature:hover {
         transform: translateY(-5px);
      }
      
      .feature i {
         font-size: 2rem;
         color: var(--primary-green);
         min-width: 50px;
         text-align: center;
         padding-top: 5px;
      }
      
      .feature h3 {
         color: var(--text-dark);
         margin-bottom: 0.5rem;
         font-size: 1.3rem;
      }
      
      .feature p {
         color: var(--text-medium);
         line-height: 1.6;
      }
      
      .about-image {
         border-radius: 10px;
         overflow: hidden;
         box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      }
      
      .about-image img {
         width: 100%;
         height: auto;
         display: block;
         transition: transform 0.5s ease;
      }
      
      .about-image:hover img {
         transform: scale(1.03);
      }
      
      .stats-section {
         padding: 4rem 0;
         background-color: var(--primary);
         color: white;
         text-align: center;
      }
      
      .stats-grid {
         display: grid;
         grid-template-columns: repeat(4, 1fr);
         gap: 2rem;
         max-width: 1200px;
         margin: 0 auto;
      }
      
      .stat {
         padding: 2rem;
      }
      
      .stat h3 {
         font-size: 2.5rem;
         margin-bottom: 0.5rem;
         color: var(--secondary);
         font-weight: 700;
      }
      
      .stat p {
         font-size: 1.1rem;
         opacity: 0.9;
      }
      
      .testimonials {
         padding: 5rem 0;
         background-color: white;
      }
      
      .testimonials h2 {
         text-align: center;
         color: var(--primary);
         font-size: 2.2rem;
         margin-bottom: 1rem;
      }
      
      .testimonials .subtitle {
         text-align: center;
         color: var(--gray);
         margin-bottom: 3rem;
         font-size: 1.1rem;
      }
      
      .testimonial-card {
         background: var(--light);
         padding: 2rem;
         border-radius: 10px;
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
         height: 100%;
      }
      
      .rating {
         color: var(--secondary);
         margin-bottom: 1rem;
      }
      
      .review {
         color: var(--dark);
         line-height: 1.7;
         margin-bottom: 1.5rem;
         font-style: italic;
      }
      
      .customer {
         display: flex;
         align-items: center;
         gap: 1rem;
      }
      
      .customer img {
         width: 60px;
         height: 60px;
         border-radius: 50%;
         object-fit: cover;
         border: 3px solid var(--primary-light);
      }
      
      .customer h4 {
         color: var(--primary);
         margin-bottom: 0.2rem;
      }
      
      .customer p {
         color: var(--gray);
         font-size: 0.9rem;
      }
      
      .cta-section {
         padding: 4rem 0;
         background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
         color: white;
         text-align: center;
      }
      
      .cta-section h2 {
         font-size: 2.2rem;
         margin-bottom: 1rem;
      }
      
      .cta-section p {
         font-size: 1.1rem;
         max-width: 700px;
         margin: 0 auto 2rem;
         opacity: 0.9;
      }
      
      .cta-buttons {
         display: flex;
         gap: 1rem;
         justify-content: center;
      }
      
      .btn {
         display: inline-block;
         padding: 0.8rem 2rem;
         border-radius: 50px;
         font-weight: 600;
         text-decoration: none;
         transition: all 0.3s ease;
      }
      
      .btn-primary {
         background-color: var(--primary-green);
         color: #fff;
      }
      
      .btn-primary:hover {
         background-color: #689F38;
         transform: translateY(-3px);
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }
      
      .btn-outline {
         border: 2px solid var(--primary-green);
         color: var(--primary-green);
         background: #fff;
      }
      
      .btn-outline:hover {
         background-color: #8BC34A;
         color: #fff;
      }
      
      @media (max-width: 992px) {
         .about-grid {
            grid-template-columns: 1fr;
         }
         
         .stats-grid {
            grid-template-columns: repeat(2, 1fr);
         }
      }
      
      @media (max-width: 768px) {
         .about-hero h1 {
            font-size: 2.2rem;
         }
         
         .cta-buttons {
            flex-direction: column;
            align-items: center;
         }
      }
      
      @media (max-width: 576px) {
         .stats-grid {
            grid-template-columns: 1fr;
         }
         
         .feature {
            flex-direction: column;
            text-align: center;
         }
         
         .feature i {
            margin-bottom: 1rem;
         }
      }
      
      .white-card {
         background: #fff;
         color: #222;
         border-radius: 12px;
         box-shadow: 0 3px 18px rgba(44,62,80,0.06);
         padding: 1.5rem 1.4rem 1.4rem 1.4rem;
         margin-bottom: 1.3rem;
         transition: box-shadow 0.2s, transform 0.2s;
      }
      .white-card h2, .white-card h3, .white-card p, .white-card strong {
         color: #222 !important;
      }
      .white-card h3, .white-card h2 {
         color: var(--primary-green) !important;
      }
      .white-card .value-item h3 i {
         color: var(--secondary) !important;
      }
      .white-card .value-item p {
         color: var(--gray) !important;
      }
      .mission-values.white-card {
         margin-top: 2.5rem;
         padding: 2rem 1.5rem 1.5rem 1.5rem;
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- Hero Banner Section -->
<section class="about-hero">
   <div class="container">
      <div class="about-hero-grid">
         <!-- Left: About Us full info -->
         <div class="about-hero-info white-card">
            <h2>ABOUT US</h2>
            <h3>Who are we</h3>
            <p>Welcome to SYOKICHEM, your trusted online pharmacy committed to delivering safe, affordable, and accessible healthcare directly to your doorstep. We specialize in providing prescription and over-the-counter medications through a secure digital platform, ensuring convenience without compromising on quality or patient safety.</p>
            <p>Fully licensed and operated by qualified pharmacists, SYOKICHEM complies with all applicable pharmaceutical regulations and standards. We source our medications exclusively from certified manufacturers and authorized distributors, guaranteeing authenticity and efficacy with every order.</p>
            <p>Our online services are designed to simplify the pharmacy experience—from easy prescription uploads and pharmacist consultations to discreet packaging and reliable delivery. With robust data protection measures in place, we safeguard your personal health information at every step.</p>
            <p>Whether managing a chronic condition or ordering everyday healthcare essentials, SYOKICHEM is here to provide professional, pharmacy-grade support—securely and efficiently, wherever you are.</p>
            <h3>Our Quality Statement</h3>
            <p>We are committed to providing safe, reliable, and affordable medications through a secure and licensed platform. Our pharmacy operates in full compliance with regulatory standards, ensuring that every product we dispense is sourced from certified manufacturers and handled with the highest standards of quality control. Customer safety, privacy, and satisfaction are at the core of everything we do.</p>
            <p><strong>MOTTO:</strong> THINK MEDICINES, THINK SYOKICHEM</p>
            <p><strong>MISSION:</strong> To provide safe, affordable and accessible healthcare solutions by delivery high quality medications and personalized pharmaceutical care. We are committed to enhancing customer wellbeing through convenience, innovation and exceptional service.</p>
            <p><strong>VISION:</strong> To be the most trusted and innovative pharmacy revolutionizing healthcare delivery by ensuring seamless access to medications and empowering individuals to take control of their health with confidence, convenience and care</p>
         </div>
         <!-- Right: Why Choose Syokichem features -->
         <div class="about-hero-features">
            <h1>Your Trusted Healthcare Partner</h1>
            <div class="features">
               <div class="feature white-card">
                  <i class="fas fa-shield-alt"></i>
                  <div>
                     <h3>Licensed & Regulated</h3>
                     <p>Fully approved by the Pharmacy and Poisons Board of Kenya, ensuring the highest standards of pharmaceutical care and medication safety.</p>
                  </div>
               </div>
               <div class="feature white-card">
                  <i class="fas fa-pills"></i>
                  <div>
                     <h3>Genuine Medications</h3>
                     <p>We source directly from reputable manufacturers and authorized distributors to guarantee 100% authentic pharmaceutical products.</p>
                  </div>
               </div>
               <div class="feature white-card">
                  <i class="fas fa-truck-fast"></i>
                  <div>
                     <h3>Reliable Delivery Network</h3>
                     <p>Same-day delivery in Nairobi and efficient nationwide shipping. Your medications arrive safely and on time.</p>
                  </div>
               </div>
               <div class="feature white-card">
                  <i class="fas fa-user-md"></i>
                  <div>
                     <h3>Expert Consultations</h3>
                     <p>Our licensed pharmacists are available 24/7 to provide professional advice and personalized care.</p>
                  </div>
               </div>
            </div>
            <div class="mission-values white-card">
               <h2 style="color: var(--primary-green); font-size: 2.2rem; margin: 2.5rem 0 1.5rem 0;">Our Core Values</h2>
               <ul class="core-values-list" style="list-style: none; padding: 0; margin: 0;">
                 <li class="value-item" style="margin-bottom: 2rem;">
                   <h3 style="color: var(--primary-light); font-size: 1.25rem; margin-bottom: 0.4rem; display: flex; align-items: center;">
                     <i class="fas fa-user-heart" style="margin-right: 10px; color: var(--secondary);"></i> Customer-Centric Care
                   </h3>
                   <p style="color: var(--gray); line-height: 1.7;">Prioritizing customer needs, ensuring personalized support, and building trust through transparent communication.</p>
                 </li>
                 <li class="value-item" style="margin-bottom: 2rem;">
                   <h3 style="color: var(--primary-light); font-size: 1.25rem; margin-bottom: 0.4rem; display: flex; align-items: center;">
                     <i class="fas fa-shield-alt" style="margin-right: 10px; color: var(--secondary);"></i> Integrity and Trust
                   </h3>
                   <p style="color: var(--gray); line-height: 1.7;">Upholding the highest ethical standards, ensuring authenticity of medications, and maintaining customer confidentiality.</p>
                 </li>
                 <li class="value-item" style="margin-bottom: 2rem;">
                   <h3 style="color: var(--primary-light); font-size: 1.25rem; margin-bottom: 0.4rem; display: flex; align-items: center;">
                     <i class="fas fa-universal-access" style="margin-right: 10px; color: var(--secondary);"></i> Accessibility and Convenience
                   </h3>
                   <p style="color: var(--gray); line-height: 1.7;">Providing affordable and timely access to quality medications and healthcare products.</p>
                 </li>
                 <li class="value-item" style="margin-bottom: 2rem;">
                   <h3 style="color: var(--primary-light); font-size: 1.25rem; margin-bottom: 0.4rem; display: flex; align-items: center;">
                     <i class="fas fa-certificate" style="margin-right: 10px; color: var(--secondary);"></i> Quality Assurance
                   </h3>
                   <p style="color: var(--gray); line-height: 1.7;">Ensuring all products meet regulatory standards, safety protocols, and quality benchmarks.</p>
                 </li>
                 <li class="value-item" style="margin-bottom: 2rem;">
                   <h3 style="color: var(--primary-light); font-size: 1.25rem; margin-bottom: 0.4rem; display: flex; align-items: center;">
                     <i class="fas fa-lightbulb" style="margin-right: 10px; color: var(--secondary);"></i> Innovation
                   </h3>
                   <p style="color: var(--gray); line-height: 1.7;">Leveraging technology to improve user experience, streamline delivery processes, and enhance customer satisfaction.</p>
                 </li>
                 <li class="value-item" style="margin-bottom: 2rem;">
                   <h3 style="color: var(--primary-light); font-size: 1.25rem; margin-bottom: 0.4rem; display: flex; align-items: center;">
                     <i class="fas fa-graduation-cap" style="margin-right: 10px; color: var(--secondary);"></i> Education and Empowerment
                   </h3>
                   <p style="color: var(--gray); line-height: 1.7;">Offering clear, accessible information about medications, usage, and health topics to empower customers to make informed decisions.</p>
                 </li>
                 <li class="value-item" style="margin-bottom: 2rem;">
                   <h3 style="color: var(--primary-light); font-size: 1.25rem; margin-bottom: 0.4rem; display: flex; align-items: center;">
                     <i class="fas fa-handshake" style="margin-right: 10px; color: var(--secondary);"></i> Reliability and Accountability
                   </h3>
                   <p style="color: var(--gray); line-height: 1.7;">Being dependable in delivering services and addressing concerns promptly and effectively.</p>
                 </li>
                 <li class="value-item">
                   <h3 style="color: var(--primary-light); font-size: 1.25rem; margin-bottom: 0.4rem; display: flex; align-items: center;">
                     <i class="fas fa-hands-holding-heart" style="margin-right: 10px; color: var(--secondary);"></i> Compassion
                   </h3>
                   <p style="color: var(--gray); line-height: 1.7;">Understanding the importance of healthcare, showing empathy to customers, and offering respectful support.</p>
                 </li>
               </ul>
            </div>
         </div>
      </div>
   </div>
</section>

<!-- Our Team Section -->
<section class="about-team-section">
   <div class="about-team-info">
      <h2>Our Team</h2>
      <p>Our dedicated team at SYOKICHEM combines pharmaceutical expertise with a passion for care, ensuring safe, reliable, and convenient access to your medications—right from our pharmacy to your doorstep.</p>
   </div>
   <div class="about-team-image">
      <img src="images/pharmacy-team.jpg" alt="Our Professional Pharmacy Team">
   </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
   <div class="container">
      <div class="stats-grid">
         <div class="stat">
            <h3>10,000+</h3>
            <p>Satisfied Customers</p>
         </div>
         <div class="stat">
            <h3>500+</h3>
            <p>Healthcare Products</p>
         </div>
         <div class="stat">
            <h3>9+</h3>
            <p>Years of Service</p>
         </div>
         <div class="stat">
            <h3>100%</h3>
            <p>Quality Guarantee</p>
         </div>
      </div>
   </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials">
   <div class="container">
      <h2>Trusted by Kenyans Nationwide</h2>
      <p class="subtitle">Hear what our valued customers say about their experience</p>
      
      <div class="swiper testimonials-slider">
         <div class="swiper-wrapper">

            <div class="swiper-slide">
               <div class="testimonial-card">
                  <div class="rating">
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                  </div>
                  <p class="review">"Syokichem has transformed how my family accesses medications. Their same-day delivery is a lifesaver when we can't make it to a physical pharmacy. The pharmacists are knowledgeable and always willing to answer my questions."</p>
                  <div class="customer">
                     <img src="images/customer-1.jpg" alt="James Mwangi">
                     <div>
                        <h4>James Mwangi</h4>
                        <p>Nairobi</p>
                     </div>
                  </div>
               </div>
            </div>

            <div class="swiper-slide">
               <div class="testimonial-card">
                  <div class="rating">
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star-half-alt"></i>
                  </div>
                  <p class="review">"As someone with chronic medication needs, I appreciate Syokichem's reliable service. They remember my prescription history and often remind me when it's time to refill. The personal touch makes all the difference."</p>
                  <div class="customer">
                     <img src="images/customer-2.jpg" alt="Sarah Wambui">
                     <div>
                        <h4>Sarah Wambui</h4>
                        <p>Mombasa</p>
                     </div>
                  </div>
               </div>
            </div>

            <div class="swiper-slide">
               <div class="testimonial-card">
                  <div class="rating">
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                  </div>
                  <p class="review">"The online consultation service saved me during the pandemic when I couldn't see my regular doctor. The pharmacist was thorough and my prescription arrived the same day. I've been a loyal customer ever since."</p>
                  <div class="customer">
                     <img src="images/customer-3.jpg" alt="David Ochieng">
                     <div>
                        <h4>David Ochieng</h4>
                        <p>Kisumu</p>
                     </div>
                  </div>
               </div>
            </div>

         </div>
         <div class="swiper-pagination"></div>
      </div>
   </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
   <div class="container">
      <h2>Experience Healthcare Made Simple</h2>
      <p>Join thousands of Kenyans who trust Syokichem for convenient, reliable access to quality medications and pharmaceutical care.</p>
      <div class="cta-buttons">
         <a href="shop.php" class="btn btn-primary">Browse Products</a>
         <a href="contact.php" class="btn btn-outline">Get in Touch</a>
      </div>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<!-- JavaScript Libraries -->
<script src="https://unpkg.com/swiper@9/swiper-bundle.min.js"></script>
<script src="js/script.js"></script>

<script>
// Initialize Testimonial Slider
var testimonialSwiper = new Swiper(".testimonials-slider", {
   loop: true,
   spaceBetween: 30,
   pagination: {
      el: ".swiper-pagination",
      clickable: true,
   },
   breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      1024: { slidesPerView: 3 }
   },
   autoplay: {
      delay: 6000,
      disableOnInteraction: false,
   },
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
   anchor.addEventListener('click', function (e) {
      e.preventDefault();
      document.querySelector(this.getAttribute('href')).scrollIntoView({
         behavior: 'smooth'
      });
   });
});
</script>

</body>
</html>