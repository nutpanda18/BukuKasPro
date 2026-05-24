/* ==========================================================================
   BUKUKASPRO CLIENT INTERACTION GRAPH & ANIMATION SCRIPT
   ========================================================================== */

document.addEventListener("DOMContentLoaded", function () {
    
    // 1. STICKY NAV ACTION CONTROLLER
    const navbar = document.getElementById("mainNavbar");
    window.addEventListener("scroll", function () {
        if (window.scrollY > 50) {
            navbar.classList.add("scrolled");
        } else {
            navbar.classList.remove("scrolled");
        }
    });

    // 2. SCROLL SCENE FADE-IN ELEMENT TRIGGER (INTERSECTION OBSERVER API)
    const fadeElements = document.querySelectorAll(".fade-in-element");
    
    const observerOptions = {
        root: null,
        threshold: 0.1, // Element terpicu ketika 10% masuk ke viewport
        rootMargin: "0px 0px -40px 0px"
    };

    const scrollObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("is-visible");
                // Stop mengamati elemen setelah animasi berjalan sekali
                scrollObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    fadeElements.forEach(element => {
        scrollObserver.observe(element);
    });

});

// 3. TESTIMONIAL CAROUSEL/SLIDER ENGINE CODE
let currentSlideIndex = 0;
const slides = document.getElementsByClassName("testimonial-slide");
const dots = document.getElementsByClassName("dot-indicator");

function currentSlide(index) {
    // Validasi rentang indeks slider
    if (index >= slides.length) { index = 0; }
    if (index < 0) { index = slides.length - 1; }
    
    // Matikan seluruh keaktifan slide & indicator dot
    for (let i = 0; i < slides.length; i++) {
        slides[i].classList.remove("active");
        dots[i].classList.remove("active");
    }
    
    // Aktifkan slide target sasaran terpilih
    slides[index].classList.add("active");
    dots[index].classList.add("active");
    currentSlideIndex = index;
}

// Jalankan auto-play loop slider setiap 6 detik
setInterval(function() {
    currentSlideIndex++;
    if (currentSlideIndex >= slides.length) { currentSlideIndex = 0; }
    currentSlide(currentSlideIndex);
}, 6000);