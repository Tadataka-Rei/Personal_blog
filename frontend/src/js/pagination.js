/* ============================================
   pagination.js — Blog pagination control
   ============================================ */

class Pagination {
  /**
   * @param {Object}   config
   * @param {Element}  config.container       Posts grid element (.posts)
   * @param {Element}  config.paginationEl    Pagination bar element (.pagination)
   * @param {Array}    config.posts           Full list of posts to paginate
   * @param {number}   [config.postsPerPage=8]
   */
  constructor({ container, paginationEl, posts = [], postsPerPage = 8 }) {
    this.container = container;
    this.paginationEl = paginationEl;
    this.posts = posts;
    this.postsPerPage = postsPerPage;
    this.currentPage = 1;
  }

  get totalPages() {
    return Math.max(1, Math.ceil(this.posts.length / this.postsPerPage));
  }

  /** Render a single page of posts + rebuild the pagination bar. */
  renderPage(page) {
    this.currentPage = Math.min(Math.max(1, page), this.totalPages);

    const start = (this.currentPage - 1) * this.postsPerPage;
    const end = start + this.postsPerPage;
    const pagePosts = this.posts.slice(start, end);

    if (!this.container) return;

    this.container.innerHTML = "";

    if (pagePosts.length === 0) {
      this.container.innerHTML =
        '<p style="color:var(--muted);text-align:center;grid-column:1/-1;">No posts match your search.</p>';
    } else {
      pagePosts.forEach((post) => {
        this.container.appendChild(createPostCard(post));
      });
    }

    this.renderPagination();
  }

  /** Build the pagination bar (‹ prev · numbers · next ›). */
  renderPagination() {
    if (!this.paginationEl) return;

    this.paginationEl.innerHTML = "";

    // No bar when there's nothing to paginate or only one page exists.
    if (this.posts.length === 0 || this.totalPages <= 1) return;

    const inner = document.createElement("div");
    inner.className = "pagination-inner";

    // Previous button
    inner.appendChild(
      this._buildBtn("‹", "page-prev", this.currentPage - 1, this.currentPage === 1)
    );

    // Numbered buttons
    for (let i = 1; i <= this.totalPages; i++) {
      inner.appendChild(
        this._buildBtn(String(i), "", i, false, i === this.currentPage)
      );
    }

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

  /** Update the underlying post list and reset to page 1 (used by search). */
  updatePosts(newPosts) {
    this.posts = newPosts;
    this.currentPage = 1;
    this.renderPage(1);
  }
}

