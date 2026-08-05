/* ============================================
   post.js — Single post detail page
   Loaded on: post.html
   Depends on: common.js (helpers, loadPostCatalog)
   ============================================ */

// ---- Post detail page ----
const postContainer = document.querySelector(".post-full");
if (postContainer) {
  // Get post id / global index from URL query params
  const params = new URLSearchParams(window.location.search);
  const postId = params.get("id");
  const postIndex = params.get("index");

  if (!postId && postIndex === null) {
    postContainer.innerHTML =
      '<p style="color:var(--muted);">No post specified. <a href="blog.html" style="color:var(--cyan);">Go back to blog.</a></p>';
  } else {
    loadPost(postId, postIndex)
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

/**
 * Load a single full post using the post-count.js catalog.
 * Supports:
 *   1. ?id=<id>      — resolved via byId map (key + index within month data file)
 *   2. ?index=<n>    — global index across all months (fast range lookup)
 *   3. Fallback      — scan every month data file for the id
 */
async function loadPost(postId, postIndex) {
  const cat = await loadPostCatalog();
  if (!cat) throw new Error("No post catalog found");

  // 1. byId lookup
  if (postId && cat.byId && cat.byId[postId]) {
    const ref = cat.byId[postId];
    const post = await fetchByMonthIndex(ref.key, ref.index);
    if (post) return post;
  }

  // 2. Global index lookup (?index=N)
  if (postIndex !== null) {
    const n = parseInt(postIndex, 10);
    if (!isNaN(n) && Array.isArray(cat.files)) {
      let offset = 0;
      for (const f of cat.files) {
        if (n >= offset && n < offset + f.count) {
          const post = await fetchByMonthIndex(f.key, n - offset);
          if (post) return post;
        }
        offset += f.count;
      }
    }
  }

  // 3. Fallback: scan all month data files
  if (postId && Array.isArray(cat.files)) {
    for (const f of cat.files) {
      const posts = await fetchMonthData(f.key);
      const match = posts.find((p) => p.id === postId);
      if (match) return match;
    }
  }

  throw new Error(`Post "${postId || postIndex}" not found`);
}

/** Fetch the full post list for a single month data file. */
async function fetchMonthData(key) {
  const res = await fetch(buildDataUrl(getLocalDataUrl(`/data/posts-data-${key}.json`)), {
    cache: "no-store",
  });
  if (!res.ok) throw new Error(`Failed to load posts-data-${key}.json`);
  const json = await res.json();
  return Array.isArray(json) ? json : json.posts || [];
}

/** Fetch a single post at a given index within a month data file. */
async function fetchByMonthIndex(key, index) {
  const posts = await fetchMonthData(key);
  return posts[index] || null;
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
        ${section.paragraphs.join("")}
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

