<?php
include 'components/connect.php';
session_start();

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
   <title>About Us - Syokichem Pharmaceuticals | Trusted Online Pharmacy Kenya</title>

   <!-- SEO Meta Tags -->
   <meta name="description" content="Learn about Syokichem Pharmaceuticals - Kenya's trusted online pharmacy with licensed pharmacists and genuine medicines.">
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
         --primary: #006837; /* Deep green - main brand color */
         --primary-light: #4CAF50; /* Lighter green */
         --secondary: #FFC107; /* Accent yellow */
         --dark: #263238; /* Dark text */
         --light: #f8f9fa; /* Light background */
         --gray: #757575; /* Secondary text */
         --primary-green: #006837;
         --primary-yellow: #FFC107;
         --text-dark: #263238;
         --text-medium: #757575;
      }
      
      .about-hero {
         background: linear-gradient(rgba(0, 104, 55, 0.85), rgba(0, 104, 55, 0.9)), url('images/pharmacy-bg.jpg') center/cover no-repeat;
         color: white;
         padding: 6rem 0 4rem;
         text-align: center;
      }
      
      .about-hero h1 {
         font-size: 2.8rem;
         margin-bottom: 1.5rem;
         font-weight: 700;
      }
      
      .about-hero p {
         max-width: 800px;
         margin: 0 auto;
         font-size: 1.1rem;
         line-height: 1.7;
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
         background-color: var(--secondary);
         color: var(--dark);
      }
      
      .btn-primary:hover {
         background-color: #ffb300;
         transform: translateY(-3px);
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }
      
      .btn-outline {
         border: 2px solid white;
         color: white;
      }
      
      .btn-outline:hover {
         background-color: white;
         color: var(--primary);
         transform: translateY(-3px);
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- Hero Banner Section -->
<section class="about-hero">
   <div class="container">
      <h1>Your Trusted Healthcare Partner</h1>
      <p>SYOKICHEM Pharmaceuticals has been serving Kenya since 2014 with quality medications and exceptional pharmaceutical care. From our humble beginnings as a stand-alone pharmacy in Syokimau, we've grown into a trusted name in healthcare, combining traditional values with modern convenience.</p>
   </div>
</section>

<!-- About Content Section -->
<section class="about-section">
   <div class="container">
      <div class="about-grid">
         <div class="about-content">
            <h2>Why Choose Syokichem?</h2>
            <div class="features">
               <div class="feature">
                  <i class="fas fa-shield-alt"></i>
                  <div>
                     <h3>Licensed & Regulated</h3>
                     <p>Fully approved by the Pharmacy and Poisons Board of Kenya, ensuring the highest standards of pharmaceutical care and medication safety.</p>
                  </div>
               </div>
               <div class="feature">
                  <i class="fas fa-pills"></i>
                  <div>
                     <h3>Genuine Medications</h3>
                     <p>We source directly from reputable manufacturers and authorized distributors to guarantee 100% authentic pharmaceutical products.</p>
                  </div>
               </div>
               <div class="feature">
                  <i class="fas fa-truck-fast"></i>
                  <div>
                     <h3>Reliable Delivery Network</h3>
                     <p>Same-day delivery in Nairobi and efficient nationwide shipping. Your medications arrive safely and on time.</p>
                  </div>
               </div>
               <div class="feature">
                  <i class="fas fa-user-md"></i>
                  <div>
                     <h3>Expert Consultations</h3>
                     <p>Our licensed pharmacists are available 24/7 to provide professional advice and personalized care.</p>
                  </div>
               </div>
            </div>
            <a href="contact.php" class="btn btn-primary">Contact Our Team</a>
         </div>
         <div class="about-image">
            <img src="images/pharmacy-team.jpg" alt="Our Professional Pharmacy Team">
         </div>
      </div>
   </div>
</section>

<!-- Mission and Values Section -->
<section class="mission-section" style="padding: 5rem 0; background-color: white;">
   <div class="container">
      <div class="mission-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
         <div class="mission-image">
            <img src="images/pharmacy-interior.jpg" alt="Syokichem Pharmacy Interior" style="border-radius: 10px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
         </div>
         <div class="mission-content">
            <h2 style="color: var(--primary); font-size: 2.2rem; margin-bottom: 1.5rem;">Our Mission & Values</h2>
            
            <div class="value-item" style="margin-bottom: 1.5rem;">
               <h3 style="color: var(--primary-light); font-size: 1.3rem; margin-bottom: 0.5rem; display: flex; align-items: center;">
                  <i class="fas fa-heart" style="margin-right: 10px; color: var(--secondary);"></i> Customer-Centric Care
               </h3>
               <p style="color: var(--gray); line-height: 1.7;">We prioritize your health needs with personalized support and transparent communication to build lasting trust.</p>
            </div>
            
            <div class="value-item" style="margin-bottom: 1.5rem;">
               <h3 style="color: var(--primary-light); font-size: 1.3rem; margin-bottom: 0.5rem; display: flex; align-items: center;">
                  <i class="fas fa-gem" style="margin-right: 10px; color: var(--secondary);"></i> Integrity & Quality
               </h3>
               <p style="color: var(--gray); line-height: 1.7;">Upholding the highest ethical standards and ensuring all products meet strict regulatory requirements.</p>
            </div>
            
            <div class="value-item" style="margin-bottom: 1.5rem;">
               <h3 style="color: var(--primary-light); font-size: 1.3rem; margin-bottom: 0.5rem; display: flex; align-items: center;">
                  <i class="fas fa-lightbulb" style="margin-right: 10px; color: var(--secondary);"></i> Innovation
               </h3>
               <p style="color: var(--gray); line-height: 1.7;">Leveraging technology to enhance your healthcare experience with convenient digital solutions.</p>
            </div>
         </div>
      </div>
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