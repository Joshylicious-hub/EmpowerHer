// Navbar hide on scroll
let lastScrollTop = 0;
const navbar = document.querySelector("nav");

window.addEventListener("scroll", function() {
  let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  navbar.style.top = scrollTop > lastScrollTop ? "-100px" : "0";
  lastScrollTop = scrollTop;
});

// Sliding text and image
document.addEventListener("DOMContentLoaded", () => {
  const elements = document.querySelectorAll(".hidden-left, .hidden-right");

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      entry.target.classList.toggle("show", entry.isIntersecting);
    });
  }, { threshold: 0.2 });

  elements.forEach(el => observer.observe(el));
});

// Navbar background on scroll
document.addEventListener("scroll", () => {
  const nav = document.querySelector("nav");
  nav.classList.toggle("scrolled", window.scrollY > 50);
});

// EmpowerHer hero animation
document.addEventListener("DOMContentLoaded", () => {
  const heroElements = document.querySelectorAll(".hidden-bottom");

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      entry.target.classList.toggle("show", entry.isIntersecting);
    });
  }, { threshold: 0.2 });

  heroElements.forEach(el => observer.observe(el));
});

// Dropdown functionality
const dropdownLinks = document.querySelectorAll('.dropdown > a');
dropdownLinks.forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const submenu = link.nextElementSibling;
    submenu.classList.toggle('open');
  });
});

// Burger menu toggle + close on link click
const burger = document.getElementById('burger');
const navMenu = document.getElementById('nav-menu');
const navLinks = document.querySelectorAll('#nav-menu a');

burger.addEventListener('click', () => {
  navMenu.classList.toggle('active');
  burger.classList.toggle('open');
});

// ✅ Close menu when clicking any link
navLinks.forEach(link => {
  link.addEventListener('click', () => {
    navMenu.classList.remove('active');
    burger.classList.remove('open');
  });
});
