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

// ---- Load the post catalog (post-count.js) ----
// post-count.js is a generated JS file in frontend/data/:
//   window.POST_COUNT = { files:[{key,summary,data,count}], total:N, byId:{id:{key,index}} };
// Returns the parsed catalog object, or null if it doesn't exist yet.
async function loadPostCatalog() {
  const res = await fetch(buildDataUrl(getLocalDataUrl("/data/post-count.js")), {
    cache: "no-store",
  });
  if (!res.ok) return null;

  const text = await res.text();
  const match = text.match(/window\.POST_COUNT\s*=\s*([\s\S]*)$/);
  if (!match) return null;

  let json = match[1].trim();
  if (json.endsWith(";")) json = json.slice(0, -1).trim();

  try {
    return JSON.parse(json);
  } catch (err) {
    console.error("Invalid post-count.js payload", err);
    return null;
  }
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

