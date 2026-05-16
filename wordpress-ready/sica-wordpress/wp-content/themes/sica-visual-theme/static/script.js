// ============================================================
// FAQ ACCORDION
// ============================================================
const accordionHeaders = document.querySelectorAll('.accordion-header');
accordionHeaders.forEach(header => {
    header.addEventListener('click', () => {
        const item = header.parentElement;
        const content = header.nextElementSibling;
        const isOpen = header.classList.contains('open');

        // Close all other accordion items
        accordionHeaders.forEach(otherHeader => {
            if (otherHeader !== header) {
                otherHeader.classList.remove('open');
                otherHeader.nextElementSibling.style.display = 'none';
            }
        });

        // Toggle current item
        if (isOpen) {
            header.classList.remove('open');
            content.style.display = 'none';
        } else {
            header.classList.add('open');
            content.style.display = 'block';
        }
    });
});

// ============================================================
// HAMBURGER MENU
// ============================================================
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('navMenu');

if (hamburger && navMenu) {
    const isMobileMenu = () => window.matchMedia('(max-width: 640px)').matches;

    const resetDesktopMenu = () => {
        if (!isMobileMenu()) {
            navMenu.style.removeProperty('display');
        }
    };

    hamburger.addEventListener('click', () => {
        if (!isMobileMenu()) {
            return;
        }

        navMenu.style.display = navMenu.style.display === 'flex' ? 'none' : 'flex';
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!isMobileMenu()) {
            return;
        }

        if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
            navMenu.style.display = 'none';
        }
    });

    // Close menu when nav link clicked
    const navLinks = navMenu.querySelectorAll('a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (isMobileMenu()) {
                navMenu.style.display = 'none';
            }
        });
    });

    window.addEventListener('resize', resetDesktopMenu);
    resetDesktopMenu();
}

// ============================================================
// DROPDOWN MENU
// ============================================================
document.querySelectorAll('.dropdown-trigger').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const parent = trigger.closest('.has-dropdown');
        const isOpen = parent.classList.contains('is-open');
        document.querySelectorAll('.has-dropdown').forEach(d => d.classList.remove('is-open'));
        if (!isOpen) parent.classList.add('is-open');
    });
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.has-dropdown')) {
        document.querySelectorAll('.has-dropdown').forEach(d => d.classList.remove('is-open'));
    }
});

// ============================================================
// SCROLL PROGRESS INDICATOR
// ============================================================
const scrollProgressBar = document.getElementById('scrollProgressBar');

if (scrollProgressBar) {
    const updateScrollProgress = () => {
        const scrollTop = window.scrollY;
        const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = scrollableHeight > 0 ? scrollTop / scrollableHeight : 0;
        scrollProgressBar.style.transform = `scaleY(${progress})`;
    };

    updateScrollProgress();
    window.addEventListener('scroll', updateScrollProgress, { passive: true });
    window.addEventListener('resize', updateScrollProgress);
}

// ============================================================
// BACK TO TOP BUTTON
// ============================================================
const backToTopButton = document.createElement('button');
backToTopButton.type = 'button';
backToTopButton.className = 'back-to-top';
backToTopButton.setAttribute('aria-label', 'Voltar ao topo');
backToTopButton.textContent = '↑';
document.body.appendChild(backToTopButton);

const updateBackToTopVisibility = () => {
    const scrollTop = window.scrollY;
    const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
    const distanceToBottom = Math.max(scrollableHeight - scrollTop, 0);
    const hasScrolledEnough = scrollTop > 360;
    const isNearPageEnd = distanceToBottom < 260;

    backToTopButton.classList.toggle('is-visible', hasScrolledEnough || isNearPageEnd);
};

backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

updateBackToTopVisibility();
window.addEventListener('scroll', updateBackToTopVisibility, { passive: true });
window.addEventListener('resize', updateBackToTopVisibility);

// ============================================================
// HERO KPI COUNT-UP
// ============================================================
const countupItems = document.querySelectorAll('.countup');

if (countupItems.length > 0) {
    const formatCountValue = (value, decimals, prefix, suffix) => {
        const formatted = value.toLocaleString('pt-PT', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
        return `${prefix}${formatted}${suffix}`;
    };

    const animateCount = element => {
        const target = Number(element.dataset.target || 0);
        const decimals = Number(element.dataset.decimals || 0);
        const prefix = element.dataset.prefix || '';
        const suffix = element.dataset.suffix || '';
        const duration = 1500;
        const start = performance.now();

        const step = now => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target * eased;
            element.textContent = formatCountValue(current, decimals, prefix, suffix);

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                element.textContent = formatCountValue(target, decimals, prefix, suffix);
            }
        };

        requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCount(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.45 }
    );

    countupItems.forEach(item => observer.observe(item));
}

// ============================================================
// TESTIMONIALS CAROUSEL - INFINITE CSS LOOP
// ============================================================
const carousel = document.getElementById('testimonialsCarousel');

if (carousel) {
    const cards = Array.from(carousel.children);

    // Duplicate the original set once so CSS can loop seamlessly.
    cards.forEach(card => {
        const clone = card.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        carousel.appendChild(clone);
    });

    carousel.addEventListener('mouseenter', () => {
        carousel.classList.add('paused');
    });

    carousel.addEventListener('mouseleave', () => {
        carousel.classList.remove('paused');
    });
}

// ============================================================
// PROCESS SECTION - SCROLL SYSTEM
// ============================================================
const processSection = document.getElementById('process');
const processSystem = document.getElementById('processSystem');
const processItems = processSection ? Array.from(processSection.querySelectorAll('.process-item')) : [];

if (processSection && processSystem && processItems.length > 0) {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
    let targetProgress = 0;
    let renderedProgress = 0;
    let progressRafId = null;

    const smoothstep = value => value * value * (3 - 2 * value);

    const renderProgress = () => {
        const delta = targetProgress - renderedProgress;
        renderedProgress += delta * 0.14;

        if (Math.abs(delta) < 0.0015) {
            renderedProgress = targetProgress;
        }

        processSystem.style.setProperty('--process-progress', `${renderedProgress * 100}%`);

        if (Math.abs(targetProgress - renderedProgress) >= 0.0015) {
            progressRafId = requestAnimationFrame(renderProgress);
        } else {
            progressRafId = null;
        }
    };

    const updateProcessProgress = () => {
        const sectionTop = processSection.offsetTop;
        const sectionHeight = processSection.offsetHeight;
        const viewportHeight = window.innerHeight;

        const start = sectionTop - viewportHeight * 0.72;
        const end = sectionTop + sectionHeight - viewportHeight * 0.2;
        const ratio = clamp((window.scrollY - start) / (end - start), 0, 1);
        targetProgress = smoothstep(ratio);

        if (!progressRafId) {
            progressRafId = requestAnimationFrame(renderProgress);
        }
    };

    const updateActiveStep = () => {
        const viewportTarget = window.innerHeight * 0.5;
        let activeIndex = 0;
        let smallestDistance = Number.POSITIVE_INFINITY;

        processItems.forEach((item, index) => {
            const rect = item.getBoundingClientRect();
            const itemCenter = rect.top + rect.height / 2;
            const distance = Math.abs(itemCenter - viewportTarget);

            if (distance < smallestDistance) {
                smallestDistance = distance;
                activeIndex = index;
            }
        });

        processItems.forEach((item, index) => {
            item.classList.toggle('is-active', index <= activeIndex);
        });
    };

    const onProcessScroll = () => {
        updateProcessProgress();
        updateActiveStep();
    };

    onProcessScroll();
    window.addEventListener('scroll', onProcessScroll, { passive: true });
    window.addEventListener('resize', onProcessScroll);
}

// CONTACT FORM SUBMISSION
// ============================================================
const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = contactForm.querySelector('[name="name"]');
        const email = contactForm.querySelector('[name="email"]');
        const subject = contactForm.querySelector('[name="subject"]');
        const message = contactForm.querySelector('[name="message"]');
        const submitBtn = contactForm.querySelector('[type="submit"]');

        if (!name.value || !email.value || !message.value) {
            alert('Por favor, preencha todos os campos obrigatórios.');
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            alert('Por favor, insira um email válido.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'A enviar...';

        try {
            const res = await fetch('https://formspree.io/f/mojyylgl', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new FormData(contactForm)
            });

            if (res.ok) {
                submitBtn.textContent = 'Mensagem enviada!';
                contactForm.reset();
            } else {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Mensagem';
                alert('Erro ao enviar. Tente novamente ou contacte-nos directamente.');
            }
        } catch {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar Mensagem';
            alert('Erro de ligação. Verifique a sua internet e tente novamente.');
        }
    });
}

// ============================================================
// CALENDAR MODAL
// ============================================================
(function () {
    var modalEl = document.createElement('div');
    modalEl.id = 'cal-modal';
    modalEl.innerHTML =
        '<div id="cal-backdrop">' +
            '<div id="cal-box">' +
                '<button id="cal-close" aria-label="Fechar">&times;</button>' +
                '<iframe src="https://calendar.app.google/LcG9SHbLgkQKNKnu8" frameborder="0" allowfullscreen></iframe>' +
            '</div>' +
        '</div>';
    document.body.appendChild(modalEl);

    function openCal() {
        modalEl.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    function closeCal() {
        modalEl.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    window.openCalendarModal = openCal;

    document.getElementById('cal-close').addEventListener('click', closeCal);
    document.getElementById('cal-backdrop').addEventListener('click', function (e) {
        if (e.target === this) closeCal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeCal();
    });

    // Intercept primary CTA buttons on non-clinic pages
    var path = window.location.pathname;
    var isClinicPage = path.indexOf('marketing-digital-clinicas') !== -1 ||
                       path.indexOf('digital-marketing-dental') !== -1;

    if (!isClinicPage) {
        document.querySelectorAll('.btn-primary').forEach(function (btn) {
            var href = btn.getAttribute('href') || '';
            if (href.indexOf('quiz/') !== -1 ||
                href.indexOf('mailto:') !== -1 ||
                href.indexOf('#final-cta') !== -1 ||
                href.indexOf('final-cta') !== -1) {
                btn.setAttribute('href', '#');
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    openCal();
                });
            }
        });
    }
}());
