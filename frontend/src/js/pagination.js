/* ============================================
   pagination.js — Blog pagination control
   Supports:
     - In-memory mode (search results): posts array is sliced locally.
     - Lazy mode (catalog pagination): an async loader fetches only the
       items for [start, end) from the monthly JSON files.
   ============================================ */

class Pagination {
  /**
   * @param {Object}   config
   * @param {Element}  config.container       Posts grid element (.posts)
   * @param {Element}  config.paginationEl    Pagination bar element (.pagination)
   * @param {Array}    config.posts           Full list of posts to paginate (in-memory mode)
   * @param {number}   [config.postsPerPage=8]
   * @param {Function} config.renderItem      Renders a single post card
   */
  constructor({ container, paginationEl, posts = [], postsPerPage = 8, renderItem }) {
    this.container = container;
    this.paginationEl = paginationEl;
    this.posts = posts;
    this.postsPerPage = postsPerPage;
    this.renderItem = renderItem;
    this.currentPage = 1;
    this.totalPosts = posts.length;
    this.lazyLoader = null; // async (start, end) => Promise<Array>
    this.loading = false;
  }

  get totalPages() {
    return Math.max(1, Math.ceil(this.totalPosts / this.postsPerPage));
  }

  async renderPage(page) {
    if (this.loading) return;
    this.currentPage = Math.min(Math.max(1, page), this.totalPages);

    const start = (this.currentPage - 1) * this.postsPerPage;
    const end = Math.min(start + this.postsPerPage, this.totalPosts);

    if (!this.container) return;

    if (this.lazyLoader) {
      // Lazy mode — fetch only the slice we need from the monthly files.
      this.loading = true;
      this.container.innerHTML =
        '<p style="color:var(--muted);text-align:center;grid-column:1/-1;">Loading posts…</p>';
      try {
        const pagePosts = await this.lazyLoader(start, end);
        this._renderItems(pagePosts);
      } catch (err) {
        console.error(err);
        this.container.innerHTML =
          '<p style="color:var(--muted);">Failed to load posts. Please try again later.</p>';
      } finally {
        this.loading = false;
      }
    } else {
      // In-memory mode — slice the local array.
      const pagePosts = this.posts.slice(start, end);
      this._renderItems(pagePosts);
    }

    this.renderPagination();
  }

  /** Internal: render a list of post cards into the container. */
  _renderItems(pagePosts) {
    if (!this.container) return;

    this.container.innerHTML = "";

    if (pagePosts.length === 0) {
      this.container.innerHTML =
        '<p style="color:var(--muted);text-align:center;grid-column:1/-1;">No posts match your search.</p>';
    } else {
      pagePosts.forEach((post) => {
        const card = this.renderItem ? this.renderItem(post) : document.createElement('div');
        this.container.appendChild(card);
      });
    }
  }

  /** Build the pagination bar (‹ prev · numbers · next ›). */
  renderPagination() {
    if (!this.paginationEl) return;

    this.paginationEl.innerHTML = "";

    // No bar when there's nothing to paginate or only one page exists.
    if (this.totalPosts === 0 || this.totalPages <= 1) return;

    const inner = document.createElement("div");
    inner.className = "pagination-inner";

    // Previous button
    inner.appendChild(
      this._buildBtn("‹", "page-prev", this.currentPage - 1, this.currentPage === 1)
    );

    // Numbered buttons
    this._getVisiblePages().forEach((page) => {
      if (page === "...") {
        const span = document.createElement("span");
        span.className = "page-ellipsis";
        span.textContent = "…";
        inner.appendChild(span);
      } else {
        inner.appendChild(
          this._buildBtn(
            String(page),
            "",
            page,
            false,
            page === this.currentPage
          )
        );
      }
    });

    // Next button
    inner.appendChild(
      this._buildBtn("›", "page-next", this.currentPage + 1, this.currentPage === this.totalPages)
    );

    this.paginationEl.appendChild(inner);
  }

  /** Internal helper to build a single page button. */
  _buildBtn(label, extraClass, targetPage, disabled = false, active = false) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = `page-btn ${extraClass}${active ? " page-active" : ""}`.trim();
    btn.textContent = label;
    btn.disabled = disabled;

    if (!disabled && !active) {
      btn.addEventListener("click", () => this.renderPage(targetPage));
    }

    return btn;
  }

  /** Switch to in-memory mode with a new post list (used by search). */
  updatePosts(newPosts) {
    this.posts = newPosts;
    this.totalPosts = newPosts.length;
    this.lazyLoader = null;
    this.currentPage = 1;
    this.renderPage(1);
  }

  /** Switch to lazy mode: fetch items via loader for each page range. */
  setLazyLoader(loader, total) {
    this.lazyLoader = loader;
    this.totalPosts = total;
    this.currentPage = 1;
    this.renderPage(1);
  }

  _getVisiblePages() {
    const total = this.totalPages;
    const current = this.currentPage;

    // Small page counts: show everything.
    if (total <= 7) {
      return Array.from({ length: total }, (_, i) => i + 1);
    }

    const pages = [];

    pages.push(1);

    let start = Math.max(2, current - 1);
    let end = Math.min(total - 1, current + 1);

    // Keep 3 middle buttons near the edges
    if (current <= 3) {
      start = 2;
      end = 4;
    }

    if (current >= total - 2) {
      start = total - 3;
      end = total - 1;
    }

    if (start > 2) {
      pages.push("...");
    }

    for (let i = start; i <= end; i++) {
      pages.push(i);
    }

    if (end < total - 1) {
      pages.push("...");
    }

    pages.push(total);

    return pages;
  }
}

