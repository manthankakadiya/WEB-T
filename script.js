/* ==========================================================================
   StudentHub - app.js
   Simple JavaScript for all pages: collapsible FAQ, modal popup,
   content slider, notification banner, hamburger menu, theme switcher.
   Add this line before </body> on every page:
   <script src="app.js"></script>
   ========================================================================== */

// Run everything after the page has loaded
window.onload = function () {
  setupThemeSwitcher();
  setupHamburgerMenu();
  setupCollapsibleFAQ();
  setupNotificationBanner();
  setupModal();
  setupSlider();
};

/* ---------- 1. Light / Dark theme switcher ---------- */
function setupThemeSwitcher() {
  var header = document.querySelector("header");
  if (!header) return;

  var btn = document.createElement("button");
  btn.className = "theme-toggle";
  btn.textContent = "🌙";
  header.style.position = "relative";
  header.appendChild(btn);

  // load saved theme
  if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-theme");
    btn.textContent = "☀️";
  }

  btn.onclick = function () {
    document.body.classList.toggle("dark-theme");
    var isDark = document.body.classList.contains("dark-theme");
    btn.textContent = isDark ? "☀️" : "🌙";
    localStorage.setItem("theme", isDark ? "dark" : "light");
  };
}

/* ---------- 2. Hamburger menu ---------- */
function setupHamburgerMenu() {
  var nav = document.querySelector("header nav");
  if (!nav) return;

  var btn = document.createElement("button");
  btn.className = "hamburger";
  btn.innerHTML = "<span></span><span></span><span></span>";
  nav.parentNode.insertBefore(btn, nav);

  btn.onclick = function () {
    nav.classList.toggle("nav-open");
    btn.classList.toggle("open");
  };
}

/* ---------- 3. Collapsible FAQ / event articles ---------- */
function setupCollapsibleFAQ() {
  var articles = document.querySelectorAll("main article");

  articles.forEach(function (article, i) {
    var heading = article.querySelector("h3");
    var text = article.querySelector("p");
    if (!heading || !text) return;

    article.classList.add("collapsible");
    if (i === 0) article.classList.add("open"); // first one open by default

    heading.onclick = function () {
      article.classList.toggle("open");
    };
  });
}

/* ---------- 4. Notification banner ---------- */
function setupNotificationBanner() {
  if (localStorage.getItem("bannerClosed") === "yes") return;

  var header = document.querySelector("header");
  if (!header) return;

  var banner = document.createElement("div");
  banner.className = "notification-banner";
  banner.innerHTML =
    "📢 Registrations for the Web Development Workshop are open!" +
    '<button class="banner-close">✕</button>';

  header.parentNode.insertBefore(banner, header);

  banner.querySelector(".banner-close").onclick = function () {
    banner.remove();
    localStorage.setItem("bannerClosed", "yes");
  };
}

/* ---------- 5. Modal popup ---------- */
function setupModal() {
  // build the modal once
  var overlay = document.createElement("div");
  overlay.className = "modal-overlay";
  overlay.innerHTML =
    '<div class="modal-box">' +
    '<button class="modal-close">✕</button>' +
    '<h3 class="modal-title"></h3>' +
    '<div class="modal-body"></div>' +
    "</div>";
  document.body.appendChild(overlay);

  var titleEl = overlay.querySelector(".modal-title");
  var bodyEl = overlay.querySelector(".modal-body");

  function openModal(title, text) {
    titleEl.textContent = title;
    bodyEl.innerHTML = "<p>" + text + "</p>";
    overlay.classList.add("active");
  }

  function closeModal() {
    overlay.classList.remove("active");
  }

  overlay.querySelector(".modal-close").onclick = closeModal;
  overlay.onclick = function (e) {
    if (e.target === overlay) closeModal();
  };

  // "About" button in the footer, on every page
  var footer = document.querySelector("footer");
  if (footer) {
    var infoBtn = document.createElement("button");
    infoBtn.className = "event-details-btn";
    infoBtn.textContent = "ℹ️ About StudentHub";
    footer.appendChild(infoBtn);

    infoBtn.onclick = function () {
      openModal(
        "About StudentHub",
        "StudentHub helps students connect, discover events and stay involved in college life."
      );
    };
  }

  // "View details" button for each event/article
  var articles = document.querySelectorAll("main article");
  articles.forEach(function (article) {
    var heading = article.querySelector("h3");
    var text = article.querySelector("p");
    if (!heading || !text) return;

    var btn = document.createElement("button");
    btn.className = "event-details-btn";
    btn.textContent = "View details";

    btn.onclick = function (e) {
      e.stopPropagation();
      openModal(heading.textContent, text.textContent);
    };

    article.appendChild(btn);
  });
}

/* ---------- 6. Content slider (home page only) ---------- */
function setupSlider() {
  var isHome = window.location.pathname.indexOf("index.html") !== -1;
  if (!isHome) return;

  var main = document.querySelector("main");
  if (!main) return;

  var slides = [
    { title: "Welcome to StudentHub", text: "Connect with your college community in one place." },
    { title: "Discover Events", text: "Never miss a workshop, sports day, or campus activity." },
    { title: "Share Your Voice", text: "Give feedback and help us improve StudentHub." }
  ];

  var current = 0;

  var slider = document.createElement("div");
  slider.className = "slider";

  var slidesHTML = "";
  for (var i = 0; i < slides.length; i++) {
    slidesHTML +=
      '<div class="slide' + (i === 0 ? " active" : "") + '">' +
      "<h3>" + slides[i].title + "</h3>" +
      "<p>" + slides[i].text + "</p>" +
      "</div>";
  }

  var dotsHTML = "";
  for (var j = 0; j < slides.length; j++) {
    dotsHTML += '<button class="' + (j === 0 ? "active" : "") + '"></button>';
  }

  slider.innerHTML =
    '<button class="slider-btn prev">‹</button>' +
    '<div class="slider-track">' + slidesHTML + "</div>" +
    '<button class="slider-btn next">›</button>' +
    '<div class="slider-dots">' + dotsHTML + "</div>";

  main.insertBefore(slider, main.firstChild);

  var slideEls = slider.querySelectorAll(".slide");
  var dotEls = slider.querySelectorAll(".slider-dots button");

  function showSlide(index) {
    slideEls[current].classList.remove("active");
    dotEls[current].classList.remove("active");
    current = (index + slideEls.length) % slideEls.length;
    slideEls[current].classList.add("active");
    dotEls[current].classList.add("active");
  }

  slider.querySelector(".next").onclick = function () {
    showSlide(current + 1);
  };
  slider.querySelector(".prev").onclick = function () {
    showSlide(current - 1);
  };

  dotEls.forEach(function (dot, i) {
    dot.onclick = function () {
      showSlide(i);
    };
  });

  // auto play every 4 seconds
  setInterval(function () {
    showSlide(current + 1);
  }, 4000);
}