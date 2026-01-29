//para sa navbar
let lastScrollTop = 0;
const navbar = document.querySelector("nav");

window.addEventListener("scroll", function() {
  let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

  if (scrollTop > lastScrollTop) {
    
    navbar.style.top = "-100px";
  } else {
    
    navbar.style.top = "0";
  }
  lastScrollTop = scrollTop;
});

//sliding text and image
document.addEventListener("DOMContentLoaded", () => {
  const elements = document.querySelectorAll(".hidden-left, .hidden-right");

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("show");
      } else {
        entry.target.classList.remove("show"); // pang reset kapag d na kita
      }
    });
  }, { threshold: 0.2 }); // trigger when 20% is visible

  elements.forEach(el => observer.observe(el));
});


//sa navigation bar scroll
document.addEventListener("scroll", () => {
  const nav = document.querySelector("nav");
  if (window.scrollY > 50) {
    nav.classList.add("scrolled");
  } else {
    nav.classList.remove("scrolled");
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const hiddenElements = document.querySelectorAll(".hidden-left, .hidden-right, .hidden-bottom");

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("show");   // slide in
      } else {
        entry.target.classList.remove("show"); // slide out when leaving
      }
    });
  }, {
    threshold: 0.2 // visible 20% before triggering
  });

  hiddenElements.forEach(el => observer.observe(el));
});


  const burger = document.getElementById('burger');
  const navMenu = document.getElementById('nav-menu');

  burger.addEventListener('click', () => {
    navMenu.classList.toggle('active');
    burger.classList.toggle('open');
  });