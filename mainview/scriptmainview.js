// Módulo de Tema
const ThemeManager = (() => {
    const applySavedTheme = () => {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const savedTheme = localStorage.getItem('theme') || (prefersDark ? 'dark' : 'light');
      
      if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
      }
    };
  
    const updateThemeIcon = () => {
      const isDarkMode = document.body.classList.contains('dark-mode');
      const moonIcon = document.querySelector('.fa-moon');
      const sunIcon = document.querySelector('.fa-sun');
  
      if (moonIcon && sunIcon) {
        moonIcon.style.opacity = isDarkMode ? '0' : '1';
        sunIcon.style.opacity = isDarkMode ? '1' : '0';
      }
    };
  
    const toggleTheme = () => {
      document.body.classList.toggle('dark-mode');
      const theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
      localStorage.setItem('theme', theme);
      updateThemeIcon();
    };
  
    const init = () => {
      applySavedTheme();
      updateThemeIcon();
      
      const themeToggle = document.getElementById('theme-toggle');
      if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
      }
    };
  
    return { init };
  })();
 
  
  // Módulo de Carrossel
  const Carousel = (() => {
    let currentIndex = 0;
    let autoSlideInterval;
    const SLIDE_INTERVAL = 5000;
    const SWIPE_THRESHOLD = 50;
  
    const init = (carouselElement) => {
      if (!carouselElement) return;
  
      const inner = carouselElement.querySelector('.carousel-inner');
      const items = carouselElement.querySelectorAll('.carousel-item');
      const prevBtn = carouselElement.querySelector('.carousel-control.prev');
      const nextBtn = carouselElement.querySelector('.carousel-control.next');
      const indicators = carouselElement.querySelectorAll('.indicator');
      
      if (!inner || items.length === 0) return;
  
      const totalItems = items.length;
  
      const updateCarousel = () => {
        inner.style.transform = `translateX(-${currentIndex * 100}%)`;
        
        indicators.forEach((indicator, index) => {
          indicator.classList.toggle('active', index === currentIndex);
        });
      };
  
      const goToSlide = (index) => {
        currentIndex = (index + totalItems) % totalItems;
        updateCarousel();
        resetAutoSlide();
      };
  
      const nextSlide = () => goToSlide(currentIndex + 1);
      const prevSlide = () => goToSlide(currentIndex - 1);
  
      const startAutoSlide = () => {
        autoSlideInterval = setInterval(nextSlide, SLIDE_INTERVAL);
      };
  
      const resetAutoSlide = () => {
        clearInterval(autoSlideInterval);
        startAutoSlide();
      };
  
      // Event listeners
      if (nextBtn) nextBtn.addEventListener('click', nextSlide);
      if (prevBtn) prevBtn.addEventListener('click', prevSlide);
  
      indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => goToSlide(index));
      });
  
      // Touch events
      let touchStartX = 0;
      
      const handleTouchStart = (e) => {
        touchStartX = e.changedTouches[0].screenX;
      };
      
      const handleTouchEnd = (e) => {
        const touchEndX = e.changedTouches[0].screenX;
        const diff = touchStartX - touchEndX;
        
        if (diff > SWIPE_THRESHOLD) {
          nextSlide();
        } else if (diff < -SWIPE_THRESHOLD) {
          prevSlide();
        }
      };
  
      carouselElement.addEventListener('touchstart', handleTouchStart, { passive: true });
      carouselElement.addEventListener('touchend', handleTouchEnd, { passive: true });
  
      // Keyboard navigation
      const handleKeyDown = (e) => {
        if (e.key === 'ArrowRight') nextSlide();
        if (e.key === 'ArrowLeft') prevSlide();
      };
      
      document.addEventListener('keydown', handleKeyDown);
  
      // Auto slide and hover control
      carouselElement.addEventListener('mouseenter', () => clearInterval(autoSlideInterval));
      carouselElement.addEventListener('mouseleave', startAutoSlide);
  
      startAutoSlide();
    };
  
    return { init };
  })();
  
  // Módulo de Utilitários
  const Utils = {
    showError: (message) => {
      alert(message);
    },
    
    handleLoginForm: () => {
      const loginForm = document.querySelector('.login-form');
      if (!loginForm) return;
  
      loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
  
        if (!email || !password) {
          Utils.showError('Por favor, preencha todos os campos');
          return;
        }
  
        try {
          const { token, nome } = await AuthService.login(email, password);
          localStorage.setItem('token', token);
          localStorage.setItem('userName', nome);
          
          Utils.showError('Login realizado com sucesso!');
          window.location.href = '/auth/dashboard';
        } catch (error) {
          Utils.showError(error.message);
          console.error('Login error:', error);
        }
      });
    }
  };
  
  // Inicializa o gerenciador de tema
  document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
  
    // Inicializa o carrossel
    const carousel = document.querySelector('.hero-carousel');
    Carousel.init(carousel);
  });

       