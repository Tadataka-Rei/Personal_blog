/* ============================================
   blog.js — Blog listing, search & post cards
   Loaded on: blog.html
   Depends on: pagination.js (Pagination), common.js (helpers)
   ============================================ */

// ---- Helper: render a single post card ----
function createPostCard(post) {
  const article = document.createElement("article");
  article.className = "post";

  article.innerHTML = `
    <a class="readme-btn" href="post.html?id=${post.id}" aria-label="Read more: ${post.title}">Read more</a>
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
let allPosts = [];

// Pagination instance — 8 posts per page
const pagination = new Pagination({
  container: postsContainer,
  paginationEl,
  posts: [],
  postsPerPage: 8,
  renderItem: createPostCard,
});

if (postsContainer) {
  fetch(buildDataUrl(getLocalDataUrl("/data/posts.json")), { cache: "no-store" })
    .then((res) => {
      if (!res.ok) throw new Error("Failed to load posts.json");
      return res.json();
    })
    .then((data) => {
      allPosts = data.posts;
      pagination.updatePosts(allPosts);
    })
    .catch((err) => {
      console.error(err);
      postsContainer.innerHTML =
        '<p style="color:var(--muted);">Failed to load posts. Please try again later.</p>';
    });
}

// ---- Search / filter posts ----
const searchInput = document.querySelector(".search-input");
if (searchInput) {
  searchInput.addEventListener("input", (e) => {
    const query = e.target.value.trim().toLowerCase();

    if (query === "") {
      pagination.updatePosts(allPosts);
      return;
    }

    const filtered = allPosts.filter((post) => {
      const title = (post.title || "").toLowerCase();
      const desc = (post.description || "").toLowerCase();
      const tags = (post.tags || []).join(" ").toLowerCase();
      return title.includes(query) || desc.includes(query) || tags.includes(query);
    });

    pagination.updatePosts(filtered);
  });
}

