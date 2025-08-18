// Mobile menu functionality
const mobileMenu = {
  init: function() {
    this.menu = document.getElementById("mobile-menu");
    this.burgerBtn = document.getElementById("burger-btn");
    
    if (this.menu && this.burgerBtn) {
      this.burgerBtn.addEventListener("click", this.toggle.bind(this));
      this.addCloseListeners();
    }
  },
  
  toggle: function() {
    this.menu.classList.toggle("show");
    this.menu.classList.toggle("hidden");
    // Update burger button icon
    this.burgerBtn.innerHTML = this.menu.classList.contains("show") ? "✕" : "☰";
  },
  
  close: function() {
    this.menu.classList.remove("show");
    this.menu.classList.add("hidden");
    this.burgerBtn.innerHTML = "☰";
  },
  
  addCloseListeners: function() {
    // Close when clicking links
    document.querySelectorAll('#mobile-menu a').forEach(link => {
      link.addEventListener('click', this.close.bind(this));
    });
    
    // Close when clicking outside
    document.addEventListener('click', (e) => {
      if (this.menu.classList.contains("show") && 
          !this.menu.contains(e.target) && 
          e.target !== this.burgerBtn) {
        this.close();
      }
    });
  }
};

// Header scroll effect
const headerScroll = {
  init: function() {
    this.header = document.querySelector("header");
    if (this.header) {
      window.addEventListener('scroll', this.handleScroll.bind(this));
      this.handleScroll(); // Initialize
    }
  },
  
  handleScroll: function() {
    if (window.scrollY > 50) {
      this.header.classList.add("scrolled");
    } else {
      this.header.classList.remove("scrolled");
    }
  }
};

// Smooth scrolling
const smoothScroller = {
  init: function() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', this.scroll.bind(this));
    });
  },
  
  scroll: function(event) {
    if (event.currentTarget.getAttribute('href').startsWith('#')) {
      event.preventDefault();
      const targetId = event.currentTarget.getAttribute("href");
      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 80,
          behavior: "smooth"
        });
      }
    }
  }
};

// Initialize everything when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  mobileMenu.init();
  headerScroll.init();
  smoothScroller.init();
  
  // Set current year in footer
  const yearElement = document.getElementById('year');
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }
});

// Optional: Form submission (uncomment if needed)
/*
const formHandler = {
  init: function() {
    const contactForm = document.querySelector('form');
    if (contactForm) {
      contactForm.addEventListener('submit', this.handleSubmit.bind(this));
    }
  },
  
  handleSubmit: function(event) {
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
};
// Add formHandler.init() to DOMContentLoaded if using
*/
