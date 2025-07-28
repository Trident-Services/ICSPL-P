
      // Toggle mobile menu
      function toggleMenu() {
        const menu = document.getElementById("mobile-menu");
        menu.classList.toggle("show");
      }
      
      document.addEventListener("DOMContentLoaded", () => {
        // Close mobile menu when link is clicked
        document.querySelectorAll('#mobile-menu a').forEach(link => {
          link.addEventListener('click', () => {
            const menu = document.getElementById("mobile-menu");
            if (menu.classList.contains("show")) {
              menu.classList.remove("show");
            }
          });
        });
        
        // Set current year in footer
        document.getElementById('year').textContent = new Date().getFullYear();
        
        // Add scroll effect to header
        const header = document.querySelector('header');
        window.addEventListener('scroll', () => {
          if (window.scrollY > 10) {
            header.classList.add('header-shadow');
            header.style.backgroundColor = 'rgba(0, 0, 0, 0.95)';
            header.style.backdropFilter = 'blur(8px)';
          } else {
            header.classList.remove('header-shadow');
            header.style.backgroundColor = '#000';
            header.style.backdropFilter = 'none';
          }
        });
        
        // Load services data
        fetch('/assets/data/service.json')
            .then(response => response.json())
            .then(data => {
                renderServices(data.services);
                initSliders();
            })
            .catch(error => {
                console.error('Error loading services:', error);
            });

        // Render services
        function renderServices(services) {
            const container = document.getElementById('services-container');
            
            // First 6 services displayed normally
            const firstSix = services.slice(0, 6);
            firstSix.forEach((service, index) => {
                const serviceCard = document.createElement('div');
                serviceCard.className = `service-card animate__animated animate__fadeInUp animate__delay-${index % 3}s`;
                serviceCard.innerHTML = createServiceCardHTML(service);
                container.appendChild(serviceCard);
            });

            // Remaining services in slider
            const remainingServices = services.slice(6);
            if (remainingServices.length > 0) {
                const sliderContainer = document.createElement('div');
                sliderContainer.className = 'slider-container col-span-full mt-12';
                sliderContainer.innerHTML = `
                    <h2 class="text-2xl font-bold mb-8 text-white">More Services</h2>
                    <div class="glider-contain relative">
                        <div class="glider" id="services-slider">
                            ${remainingServices.map(service => `
                                <div class="glider-slide px-2">
                                    ${createServiceCardHTML(service)}
                                </div>
                            `).join('')}
                        </div>
                        <button aria-label="Previous" class="glider-prev absolute left-0 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button aria-label="Next" class="glider-next absolute right-0 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <div class="glider-dots text-center mt-4" id="services-dots"></div>
                    </div>
                `;
                container.appendChild(sliderContainer);
            }
        }

        // Helper function to create service card HTML
        function createServiceCardHTML(service) {
            return `
                <div class="service-card h-full">
                    <img src="${service.image}" alt="${service.category}" class="w-full h-48 object-cover">
                    <div class="service-content p-4">
                        <h3 class="text-xl font-semibold mb-2 text-white">${service.category}</h3>
                        <p class="text-gray-400 mb-4">${service.description}</p>
                        <div class="key-offerings">
                            <h4 class="text-blue-400 font-medium mb-2">Key Offerings:</h4>
                            <ul class="space-y-1 text-gray-300">
                                ${service.keyOfferings.map(offering => `<li class="flex items-start">
                                    <svg class="w-4 h-4 mt-1 mr-2 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>${offering}</span>
                                </li>`).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
            `;
        }

        // Initialize sliders
        function initSliders() {
            // Only initialize if Glider is available
            if (typeof Glider !== 'undefined') {
                new Glider(document.querySelector('#services-slider'), {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    draggable: true,
                    dots: '#services-dots',
                    arrows: {
                        prev: '.glider-prev',
                        next: '.glider-next'
                    },
                    responsive: [
                        {
                            breakpoint: 640,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 2
                            }
                        },
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 3
                            }
                        }
                    ]
                });
            }
        }
      });