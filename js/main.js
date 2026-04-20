// ===== MOBILE MENU =====
const burger = document.getElementById('burger');
const mobileMenu = document.getElementById('mobileMenu');

burger.addEventListener('click', () => {
  mobileMenu.classList.toggle('open');
});

document.querySelectorAll('.mobile-link').forEach(link => {
  link.addEventListener('click', () => mobileMenu.classList.remove('open'));
});

// ===== STICKY HEADER SHADOW =====
const header = document.getElementById('header');
window.addEventListener('scroll', () => {
  header.style.boxShadow = window.scrollY > 20
    ? '0 4px 24px rgba(0,0,0,0.12)'
    : '0 2px 16px rgba(0,0,0,0.08)';
});

// ===== SMOOTH SCROLL =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const top = target.getBoundingClientRect().top + window.pageYOffset - 80;
      window.scrollTo({ top, behavior: 'smooth' });
    }
  });
});

// ===== CONTACT FORM - AJAX SUBMIT =====
const form = document.getElementById('contactForm');
if (form) {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const originalHTML = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando…';

    try {
      const response = await fetch('submit.php', {
        method: 'POST',
        body: new FormData(form)
      });
      const data = await response.json();

      if (data.success) {
        form.innerHTML = `
          <div class="form-success">
            <i class="fa-solid fa-circle-check"></i>
            <h3>¡Solicitud enviada!</h3>
            <p>Te contactamos en menos de 24 horas.<br>Revisa también tu WhatsApp.</p>
          </div>`;
      } else {
        alert(data.message || 'Error al enviar. Intenta de nuevo.');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
      }
    } catch {
      alert('Error de conexión. Por favor intenta de nuevo.');
      btn.disabled = false;
      btn.innerHTML = originalHTML;
    }
  });
}

// ===== SCROLL REVEAL =====
const revealEls = document.querySelectorAll(
  '.problem-card, .service-card, .step-card, .why-card, .benefit-card, .hero__content, .about__content, .commitment__content'
);

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

revealEls.forEach(el => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(24px)';
  el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
  observer.observe(el);
});
