/* ============================================
   post.js — Single post detail page
   Loaded on: post.html
   Depends on: common.js (helpers)
   ============================================ */

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

