/* ============================================
   blog.js — Blog listing, search & post cards
   Loaded on: blog.html
   Depends on: pagination.js (Pagination), common.js (helpers, loadPostCatalog)
   ============================================ */

// ---- Helper: render a single post card ----
function createPostCard(post) {
  const article = document.createElement("article");
  article.className = "post";

  article.innerHTML = `
    <a class="readme-btn" href="post?id=${post.id}" aria-label="Read more: ${post.title}">Read more</a>
    <div class="post-image" role="img" aria-label="Blog image placeholder">
      <img class="post-image-img" src="${post.image}" alt="${post.title}" loading="lazy">
    </div>
    <h2 class="post-title">${post.title}</h2>
    <p class="post-desc">${post.description}</p>
    <ul class="tags">
      ${post.tags.map((tag) => `<li class="tag">${tag}</li>`).join("")}
    </ul>
  `;

  return article;
}

// ---- Blog listing page ----
const postsContainer = document.querySelector(".posts");
const paginationEl = document.querySelector(".pagination");
let catalog = null; // parsed post-count.js
let allPosts = [];  // flattened summaries (used only while searching)

// Pagination instance — 8 posts per page
const pagination = new Pagination({
  container: postsContainer,
  paginationEl,
  posts: [],
  postsPerPage: 8,
  renderItem: createPostCard,
});

if (postsContainer) {
  loadPostCatalog()
    .then((cat) => {
      catalog = cat;
      if (!cat || !Array.isArray(cat.files) || cat.files.length === 0 || cat.total === 0) {
        postsContainer.innerHTML =
          '<p style="color:var(--muted);text-align:center;grid-column:1/-1;">No posts yet.</p>';
        return;
      }
      // Lazy pagination: only fetch the summary files needed for the current page.
      pagination.setLazyLoader(loadRange, cat.total);
    })
    .catch((err) => {
      console.error(err);
      postsContainer.innerHTML =
        '<p style="color:var(--muted);">Failed to load posts. Please try again later.</p>';
    });
}

// ---- Fetch one or more month summary files ----
async function fetchMonthSummaries(keys) {
  const map = {};
  await Promise.all(
    keys.map(async (key) => {
      const res = await fetch(buildDataUrl(getLocalDataUrl(`/data/posts-${key}.json`)), {
        cache: "no-store",
      });
      if (!res.ok) throw new Error(`Failed to load posts-${key}.json`);
      const json = await res.json();
      map[key] = Array.isArray(json) ? json : json.posts || [];
    })
  );
  return map;
}

// ---- Lazy loader: return only summaries for global range [start, end) ----
async function loadRange(start, end) {
  if (!catalog) return [];

  // Determine which month files intersect the requested range.
  const needed = [];
  let offset = 0;
  for (const f of catalog.files) {
    const fStart = offset;
    const fEnd = offset + f.count;
    if (fEnd > start && fStart < end) {
      needed.push({
        key: f.key,
        sliceStart: Math.max(0, start - fStart),
        sliceEnd: Math.min(f.count, end - fStart),
      });
    }
    offset = fEnd;
    if (offset >= end) break;
  }

  if (needed.length === 0) return [];

  const summaries = await fetchMonthSummaries(needed.map((n) => n.key));

  const items = [];
  for (const n of needed) {
    items.push(...(summaries[n.key] || []).slice(n.sliceStart, n.sliceEnd));
  }
  return items;
}

// ---- Search / filter posts (across all months) ----
const searchInput = document.querySelector(".search-input");
if (searchInput) {
  searchInput.addEventListener("input", async (e) => {
    const query = e.target.value.trim().toLowerCase();

    // Empty query → return to lazy catalog pagination.
    if (query === "") {
      if (catalog) pagination.setLazyLoader(loadRange, catalog.total);
      return;
    }

    if (!catalog) return;

    try {
      // For search, we need every summary, so load all month files.
      const keys = catalog.files.map((f) => f.key);
      const summaries = await fetchMonthSummaries(keys);
      allPosts = [];
      for (const key of keys) allPosts.push(...(summaries[key] || []));

      const filtered = allPosts.filter((post) => {
        const title = (post.title || "").toLowerCase();
        const desc = (post.description || "").toLowerCase();
        const tags = (post.tags || []).join(" ").toLowerCase();
        return title.includes(query) || desc.includes(query) || tags.includes(query);
      });

      pagination.updatePosts(filtered);
    } catch (err) {
      console.error(err);
    }
  });
}

