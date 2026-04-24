# ✅ Zazagram — Project TODO

> Live tracker of feature progress. Updated after each development step.

---

## ✅ Completed

### Infrastructure
- [x] Project folder structure (`/api`, `/assets`, `/data`, `/includes`, `/pages`, `/uploads`)
- [x] `config.php` — Global constants (BASE_URL, paths, session lifetime, upload limits)
- [x] `includes/db.php` — JSON database layer with file locking (`flock`)
- [x] `includes/auth.php` — Session helpers, user getters, notification/message count
- [x] `includes/header.php` — Reusable navigation with search, badges, user dropdown
- [x] `includes/footer.php` — Reusable footer with JS includes
- [x] `index.php` — Entry point / router

### Data Layer
- [x] `data/users.json` — User schema + 5 sample users
- [x] `data/posts.json` — Post schema + 6 sample posts
- [x] `data/comments.json` — Comment schema + 8 sample comments
- [x] `data/friends.json` — Friend relationship schema + 6 relationships
- [x] `data/messages.json` — Message schema + 6 sample messages
- [x] `data/notifications.json` — Notification schema + 7 sample notifications
- [x] `data/likes.json` — Like schema + 13 sample likes

### Authentication (STEP 3)
- [x] `pages/register.php` — Registration with full validation + uniqueness check
- [x] `pages/login.php` — Login by username or email, ban check, session creation
- [x] `api/logout.php` — Logout + session destroy
- [x] Password hashing with `PASSWORD_BCRYPT`
- [x] Demo account quick-fill on login page

### User Profiles (STEP 4)
- [x] `pages/profile.php` — View any user's profile, post grid, friendship status buttons
- [x] `pages/settings.php` — Edit name, email, bio, password
- [x] `api/update_avatar.php` — AJAX avatar upload with type/size validation

### Posts + Feed (STEP 5)
- [x] `pages/feed.php` — Friends-only feed, sidebar with suggestions, quick-post box
- [x] `pages/create_post.php` — Create post with image upload, filter picker, caption
- [x] `api/delete_post.php` — Delete post (own or admin), cascades to likes/comments

### Likes + Comments (STEP 6)
- [x] `api/toggle_like.php` — Like/unlike with notification to post author
- [x] `api/add_comment.php` — Add comment with notification
- [x] `api/get_comments.php` — Fetch comments for a post
- [x] AJAX-only interactions (no page reload)

### Friend System (STEP 7)
- [x] `api/friend_request.php` — Send / accept / decline / remove
- [x] Friendship status shown on profile (Add Friend / Pending / Friends)
- [x] Friend request notifications

### Private Messages (STEP 8)
- [x] `pages/messages.php` — Two-column chat UI, conversation list, chat window
- [x] `api/send_message.php` — Send 1-to-1 message
- [x] `api/get_messages.php` — Poll for new messages
- [x] Auto-polling every 3 seconds via `setInterval`
- [x] Unread message count in navbar badge
- [x] Mark messages as read on conversation open

### Notifications (STEP 9)
- [x] `pages/notifications.php` — All notification types displayed
- [x] Mark all as read on page visit
- [x] Inline Accept/Decline for friend requests
- [x] Unread notification count in navbar badge

### Admin Panel (STEP 9)
- [x] `pages/admin.php` — Protected by `require_admin()`
- [x] Summary stats (users, banned, posts, likes, comments)
- [x] User management: view all, ban, unban, delete (cascade)
- [x] Post management: view all, delete

### Frontend UI (STEP 10)
- [x] `assets/css/main.css` — Full design system, Instagram/Facebook style
- [x] `assets/css/filters.css` — 7 CSS image filters
- [x] `assets/js/main.js` — Global interactions (dropdown, alerts)
- [x] `assets/js/posts.js` — Like/comment AJAX
- [x] `assets/js/search.js` — Live user search (debounced)
- [x] `assets/js/messages.js` — Message send + polling
- [x] `assets/js/create_post.js` — Image preview + filter + drag-and-drop
- [x] `api/search_users.php` — User search endpoint
- [x] Responsive design (mobile breakpoints)

---

## 🟡 In Progress

*None currently — all core features complete.*

---

## ❌ Not Started (Future Enhancements)

### Nice-to-have features
- [ ] Post editing (update caption/filter after posting)
- [ ] Story system (24-hour disappearing posts)
- [ ] Save/bookmark posts
- [ ] Post tags and hashtag search
- [ ] Explore page (discover all public posts)
- [ ] Post sharing / repost
- [ ] Emoji reactions (beyond like)
- [ ] Group chats / multi-user messages
- [ ] User verification badges
- [ ] Two-factor authentication
- [ ] Email notifications (SMTP)
- [ ] Dark mode toggle
- [ ] Progressive Web App (PWA) manifest
- [ ] Image cropping/resizing before upload

### Technical Debt / Improvements
- [ ] Migrate data layer to SQLite or MySQL (drop-in replacement via `db.php`)
- [ ] Add CSRF tokens to all forms and AJAX endpoints
- [ ] Rate limiting on API endpoints
- [ ] Pagination for feed and profile posts
- [ ] Lazy-loading images
- [ ] Unit tests for PHP helper functions
- [ ] API versioning (`/api/v1/`)
- [ ] HTTP cache headers for static assets
- [ ] `.htaccess` clean URLs (remove `.php` extensions)
- [ ] Input sanitization audit

---

*Last updated: 2026-04-23*
