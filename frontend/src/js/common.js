/* ============================================
   common.js — Shared helpers & nav behavior
   Loaded on: index.html, about.html, blog.html, post.html
   ============================================ */

// ---- Nav item active toggle (for about/home pages) ----
const navItems = document.querySelectorAll(".nav-item");
navItems.forEach((item) => {
  item.addEventListener("click", () => {
    document.querySelector(".nav-item.active")?.classList.remove("active");
    item.classList.add("active");
  });
});

// ---- URL helpers ----
function buildDataUrl(path) {
  const separator = path.includes("?") ? "&" : "?";
  return `${path}${separator}t=${Date.now()}`;
}

function getLocalDataUrl(path) {
  return path.startsWith("/") ? path : `/${path}`;
}

// ---- Date formatting helper ----
function formatDate(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

