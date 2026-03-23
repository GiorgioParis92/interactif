/* =====================================================
   MIA — main.js
   Interactions, animations, scroll
===================================================== */

/* ─── CURSOR ─── */
const cursorDot      = document.getElementById('cursor-dot');
const cursorFollower = document.getElementById('cursor-follower');
let mx = 0, my = 0, fx = 0, fy = 0;
document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; });
(function animateCursor() {
  cursorDot.style.left = mx + 'px';
  cursorDot.style.top  = my + 'px';
  fx += (mx - fx) * 0.15;
  fy += (my - fy) * 0.15;
  cursorFollower.style.left = fx + 'px';
  cursorFollower.style.top  = fy + 'px';
  requestAnimationFrame(animateCursor);
})();

/* ─── LOADER ─── */
window.addEventListener('load', () => {
  const loader = document.getElementById('loader');
  setTimeout(() => {
    loader.style.transition = 'opacity 0.6s ease';
    loader.style.opacity = '0';
    setTimeout(() => {
      loader.style.display = 'none';
      initAnimations();
    }, 600);
  }, 1400);
});

/* ─── LENIS SMOOTH SCROLL ─── */
let lenis;
function initLenis() {
  if (lenis) lenis.destroy();
  lenis = new Lenis({ lerp: 0.08, smoothWheel: true });
  const raf = time => { lenis.raf(time); requestAnimationFrame(raf); };
  requestAnimationFrame(raf);
}

/* ─── NAV HIDE ON SCROLL ─── */
let lastScrollY = 0;
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  const y = window.scrollY;
  navbar.classList.toggle('hidden', y > lastScrollY && y > 80);
  lastScrollY = y;
}, { passive: true });

/* ─── PAGE TRANSITION (entre pages PHP) ─── */
document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('page-transition');
  document.querySelectorAll('a[href$=".php"]').forEach(link => {
    link.addEventListener('click', e => {
      const href = link.getAttribute('href');
      if (!href || href.startsWith('#') || link.target === '_blank') return;
      e.preventDefault();
      overlay.style.transition = 'transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
      overlay.style.transformOrigin = 'bottom';
      overlay.style.transform = 'scaleY(1)';
      setTimeout(() => { window.location.href = href; }, 400);
    });
  });
  // Entrée : refermer l'overlay
  overlay.style.transformOrigin = 'top';
  overlay.style.transform = 'scaleY(0)';
});

/* ─── HERO WORD ANIMATION ─── */
function animateHero() {
  document.querySelectorAll('.word-inner').forEach((el, i) => {
    el.style.transition = `transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) ${i * 0.1}s`;
    el.style.transform = 'translateY(0)';
  });
}

/* ─── SCROLL REVEAL ─── */
function initScrollReveal() {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
}

/* ─── COUNT-UP ─── */
function animateCount(el, target, duration) {
  let start = 0;
  const step = target / (duration / 16);
  const timer = setInterval(() => {
    start += step;
    if (start >= target) { start = target; clearInterval(timer); }
    el.textContent = Math.floor(start);
  }, 16);
}
function initCounters() {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const counter = e.target.querySelector('.counter');
        const count   = parseInt(e.target.dataset.count || 0);
        if (counter && counter.textContent === '0' && count) animateCount(counter, count, 1800);
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.3 });
  document.querySelectorAll('.stat-item[data-count]').forEach(s => observer.observe(s));
}

/* ─── FAQ ACCORDION ─── */
function toggleFaq(btn) {
  const item    = btn.closest('.faq-item');
  const wasOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
  if (!wasOpen) item.classList.add('open');
}
// Attacher les événements une fois le DOM prêt
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => toggleFaq(btn));
  });
});

/* ─── INIT ─── */
function initAnimations() {
  initLenis();
  animateHero();
  initScrollReveal();
  initCounters();
}
