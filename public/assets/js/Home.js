      // Toggle mobile menu
      function toggleMenu() {
        const menu = document.getElementById("mobile-menu");
        menu.classList.toggle("show");
        menu.classList.toggle("hidden");
      }

      // Close mobile menu when link is clicked
      function closeMenu() {
        const menu = document.getElementById("mobile-menu");
        menu.classList.remove("show");
        menu.classList.add("hidden");
      }

      // Header scroll effect
      function handleScroll() {
        const header = document.querySelector("header");
        if (window.scrollY > 50) {
          header.classList.add("scrolled");
        } else {
          header.classList.remove("scrolled");
        }
      }

      // Smooth scrolling for anchor links
      function smoothScroll(event) {
        // Don't prevent default if it's an external link
        if (event.currentTarget.getAttribute('href').startsWith('#')) {
          event.preventDefault();
          const targetId = event.currentTarget.getAttribute("href");
          const targetElement = document.querySelector(targetId);
          window.scrollTo({
            top: targetElement.offsetTop - 80,
            behavior: "smooth"
          });
        }
      }

      // Form submission
      function handleSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const formValues = Object.fromEntries(formData.entries());
        
        // Here you would typically send the form data to a server
        console.log("Form submitted:", formValues);
        
        // Show success message
        alert("Thank you for your message! We'll get back to you soon.");
        form.reset();
      }

      // Initialize when DOM is loaded
      document.addEventListener("DOMContentLoaded", () => {
        // Mobile menu links
        document.querySelectorAll('#mobile-menu a').forEach(link => {
          link.addEventListener('click', closeMenu);
        });

        // Smooth scrolling for all anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
          anchor.addEventListener('click', smoothScroll);
        });

        // Form submission
        const contactForm = document.querySelector('form');
        if (contactForm) {
          contactForm.addEventListener('submit', handleSubmit);
        }

        // Scroll event for header
        window.addEventListener('scroll', handleScroll);
        handleScroll(); // Initialize header state

        // Set current year in footer
        document.getElementById('year').textContent = new Date().getFullYear();
      });
      
      // Blog slider functionality
      let currentSlide = 0;
      const slides = document.querySelectorAll('.blog-slide');
      const indicators = document.querySelectorAll('.slide-indicator');
      
      function updateSlider() {
        const slideWidth = slides[0].offsetWidth;
        document.getElementById('blog-slides').style.transform = `translateX(-${currentSlide * slideWidth}px)`;
        
        // Update indicators
        indicators.forEach((indicator, index) => {
          if (index === currentSlide) {
            indicator.classList.add('active');
          } else {
            indicator.classList.remove('active');
          }
        });
      }
      
      function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        updateSlider();
      }
      
      function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        updateSlider();
      }
      
      function goToSlide(index) {
        currentSlide = index;
        updateSlider();
      }
      
      // Auto slide every 5 seconds
      setInterval(nextSlide, 5000);
      
      // Initialize slider
      window.addEventListener('load', updateSlider);
      window.addEventListener('resize', updateSlider);
      
// fetch('blog_data.json')
//   .then(response => response.json())
//   .then(data => {
//     const blogList = document.getElementById('blog-list');
//     if (!Array.isArray(data)) return;

//     data.forEach(blog => {
//       const blogItem = document.createElement('div');
//       blogItem.style.marginBottom = '30px';
//       blogItem.style.padding = '20px';
//       blogItem.style.backgroundColor = '#f1f5f9';
//       blogItem.style.borderRadius = '10px';

//       blogItem.innerHTML = `
//         <h3>${blog.title}</h3>
//         <small>${new Date(blog.timestamp).toLocaleString()}</small>
//         <p>${blog.content.replace(/\n/g, '<br>')}</p>
//       `;
//       blogList.appendChild(blogItem);
//     });
//   })
//   .catch(err => {
//     console.error('Failed to load blog data:', err);
//   });

