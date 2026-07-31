/* ============================================
   script.js — Dynamic content & nav behavior
   ============================================ */

// ---- Nav item active toggle (for about/home pages) ----
const navItems = document.querySelectorAll(".nav-item");
navItems.forEach((item) => {
  item.addEventListener("click", () => {
    document.querySelector(".nav-item.active")?.classList.remove("active");
    item.classList.add("active");
  });
});

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

function buildDataUrl(path) {
  const separator = path.includes("?") ? "&" : "?";
  return `${path}${separator}t=${Date.now()}`;
}

function getLocalDataUrl(path) {
  return path.startsWith("/") ? path : `/${path}`;
}

// ---- Blog listing page ----
const postsContainer = document.querySelector(".posts");
let allPosts = [];

function renderPosts(posts) {
  postsContainer.innerHTML = "";
  if (posts.length === 0) {
    postsContainer.innerHTML = '<p style="color:var(--muted);text-align:center;grid-column:1/-1;">No posts match your search.</p>';
    return;
  }
  posts.forEach((post) => {
    postsContainer.appendChild(createPostCard(post));
  });
}

if (postsContainer) {
  fetch(buildDataUrl(getLocalDataUrl("/data/posts.json")), { cache: "no-store" })
    .then((res) => {
      if (!res.ok) throw new Error("Failed to load posts.json");
      return res.json();
    })
    .then((data) => {
      allPosts = data.posts;
      renderPosts(allPosts);
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
      renderPosts(allPosts);
      return;
    }

    const filtered = allPosts.filter((post) => {
      const title = (post.title || "").toLowerCase();
      const desc = (post.description || "").toLowerCase();
      const tags = (post.tags || []).join(" ").toLowerCase();
      return title.includes(query) || desc.includes(query) || tags.includes(query);
    });

    renderPosts(filtered);
  });
}

// ---- Post detail page ----
const postContainer = document.querySelector(".post-full");
if (postContainer) {
  // Get post id from URL query param
  const params = new URLSearchParams(window.location.search);
  const postId = params.get("id");

  if (!postId) {
    postContainer.innerHTML =
      '<p style="color:var(--muted);">No post specified. <a href="blog.html" style="color:var(--cyan);">Go back to blog.</a></p>';
  } else {
    // Fetch the master index to find the detail path
    fetch(buildDataUrl(getLocalDataUrl("/data/posts.json")), { cache: "no-store" })
      .then((res) => {
        if (!res.ok) throw new Error("Failed to load posts.json");
        return res.json();
      })
      .then((data) => {
        const match = data.posts.find((p) => p.id === postId);
        if (!match) throw new Error(`Post "${postId}" not found`);

        // Fetch the individual post detail
        return fetch(buildDataUrl(getLocalDataUrl(`/${match.detail}`)), { cache: "no-store" }).then((res) => {
          if (!res.ok) throw new Error(`Failed to load ${match.detail}`);
          return res.json();
        });
      })
      .then((post) => {
        renderPostDetail(post);
        // Update page title
        document.title = `${post.title} — Post`;
      })
      .catch((err) => {
        console.error(err);
        postContainer.innerHTML = `
          <p style="color:var(--muted);">
            ${err.message}. <a href="blog.html" style="color:var(--cyan);">Go back to blog.</a>
          </p>`;
      });
  }
}

// ---- Render full post detail ----
function renderPostDetail(post) {
  const pageMain = document.querySelector(".page");
  if (!pageMain) return;

  // Update back link href
  const backLink = document.querySelector(".post-back");
  if (backLink) backLink.href = "blog.html";

  // Render the article
  postContainer.innerHTML = `
    <header class="post-header">
      <h1 class="post-title">${post.title}</h1>
      <div class="post-meta">
        <span class="post-author">By ${post.author}</span>
        <span class="post-date">Published: <time datetime="${post.published}">${formatDate(post.published)}</time></span>
        <span class="post-update">Updated: <time datetime="${post.updated}">${formatDate(post.updated)}</time></span>
      </div>
    </header>

    <div class="post-image" role="img" aria-label="Featured blog image">
      <img class="post-image-img" src="${post.image}" alt="${post.title}">
    </div>

    <div class="post-body">
      <p class="post-lead">${post.lead}</p>
      ${post.sections
        .map(
          (section) => `
        <h2>${section.heading}</h2>
        ${section.paragraphs.map((p) => `<p>${p}</p>`).join("")}
      `
        )
        .join("")}
    </div>

    <footer class="post-footer">
      <ul class="tags">
        ${post.tags.map((tag) => `<li class="tag">${tag}</li>`).join("")}
      </ul>
      <a class="post-back-btn" href="blog.html">← Back to Blog</a>
    </footer>
  `;
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

