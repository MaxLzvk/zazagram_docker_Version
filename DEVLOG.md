# 📓 Zazagram — Development Log

> Chronological record of all implemented features and changes.

---

## [2026-04-23 00:00]
### STEP 1 — JSON Data Structures + Sample Data
- **Files created:**
  - `data/users.json`
  - `data/posts.json`
  - `data/comments.json`
  - `data/friends.json`
  - `data/messages.json`
  - `data/notifications.json`
  - `data/likes.json`
- **Description:** Defined all core JSON schemas with realistic sample data for 5 demo users (alex_photo, mia_creates, jordan_fit, sophie_eats, dev_sam). Each schema includes unique IDs, relational foreign keys, timestamps, and all fields required for social network functionality. Users have hashed passwords (all "password"), roles (admin/user), and ban status. Posts support text + image + CSS filter. Likes and comments are cross-referenced by post_id and user_id. Friends support pending/accepted status. Messages track read/unread state. Notifications cover likes, comments, friend_requests, and messages.

---

## [2026-04-23 00:05]
### STEP 2 — Project Architecture + Core Infrastructure
- **Files created:**
  - `config.php` — Global constants (paths, URLs, session config, limits)
  - `includes/db.php` — JSON database abstraction layer (read, write, CRUD helpers, file locking)
  - `includes/auth.php` — Authentication helpers (session checks, user getters, notification counts)
  - `includes/header.php` — Global HTML head + navigation bar with search, notifications, messages
  - `includes/footer.php` — Closing HTML, JS includes
  - `index.php` — Entry point (redirects to feed or login)
- **Description:** Established a clean MVC-inspired structure. All JSON I/O goes through `db.php` with `flock()` for concurrency safety. Auth helpers centralise session logic. Header/footer are reusable components included by all pages. Configuration constants make the project portable.

---

## [2026-04-23 00:10]
### STEP 3 — Authentication System
- **Files created:**
  - `pages/register.php` — Registration form with validation (username regex, email, password strength, uniqueness checks)
  - `pages/login.php` — Login form (username or email), session creation, ban check
  - `api/logout.php` — Session destruction + redirect
- **Description:** Full auth system using PHP sessions. Passwords hashed with `PASSWORD_BCRYPT`. Includes demo account quick-fill buttons on the login page. Banned users cannot log in. All redirects use the `BASE_URL` constant for portability.

---

## [2026-04-23 00:15]
### STEP 4 — User Profiles
- **Files created:**
  - `pages/profile.php` — View any user's profile (avatar, bio, stats, post grid, friend status buttons)
  - `pages/settings.php` — Edit own profile (name, email, bio, password change)
  - `api/update_avatar.php` — AJAX avatar upload with validation (file type, size, real image check)
- **Description:** Profile pages show post count, friend count, and friendship status. Own profile shows an edit button. Other users show Add Friend / Accept / Decline / Message buttons depending on relationship. Avatar upload validates type and size, deletes old file, updates JSON.

---

## [2026-04-23 00:20]
### STEP 5 — Posts + Feed
- **Files created:**
  - `pages/feed.php` — Friends-only feed with sidebar (own info + suggestions), quick-post box, full post cards with likes/comments
  - `pages/create_post.php` — Post creation form with image upload, drag-and-drop, live filter preview, caption character counter
  - `api/delete_post.php` — Delete post (own or admin), removes image file, cleans likes/comments
- **Description:** Feed shows posts from friends + self, sorted newest-first. Each post card has author info, image with CSS filter applied, caption, like button (AJAX), comment toggle. Post creator sees a delete menu. Create post page has a visual filter picker with live CSS preview.

---

## [2026-04-23 00:25]
### STEP 6 — Likes + Comments (AJAX)
- **Files created:**
  - `api/toggle_like.php` — Like/unlike a post, updates likes.json, sends notification to post author
  - `api/add_comment.php` — Add comment to a post, updates comments.json, sends notification
  - `api/get_comments.php` — Fetch all comments for a post (with author usernames)
- **Description:** All like/comment actions are fully AJAX-driven using the Fetch API. Liking a post fires a notification to the author (excluding self-likes). Comments are loaded dynamically when the user expands the comment section. All endpoints return JSON responses.

---

## [2026-04-23 00:30]
### STEP 7 — Friend System
- **Files created:**
  - `api/friend_request.php` — Multi-action endpoint: send, accept, decline, remove friend requests
- **Description:** Friend requests have pending → accepted flow stored in friends.json. Sending a request creates a notification for the receiver. Profile page and feed sidebar both use this API. The system correctly handles all four states: none, pending_sent, pending_received, friends.

---

## [2026-04-23 00:35]
### STEP 8 — Private Messages
- **Files created:**
  - `pages/messages.php` — Full messaging UI (conversation list + chat window), marks messages as read on open
  - `api/send_message.php` — Send a message, creates notification
  - `api/get_messages.php` — Poll for new messages since last_id, marks incoming as read
- **Description:** 1-to-1 messaging system with a two-column layout. Messages are polled every 3 seconds via JavaScript. Chat window auto-scrolls to the latest message. Unread message count shown in navbar badge. New conversation can be started from the search input inside the messages page.

---

## [2026-04-23 00:40]
### STEP 9 — Notifications + Admin Panel
- **Files created:**
  - `pages/notifications.php` — Lists all notifications for current user, marks all as read on visit, shows Accept/Decline buttons for friend requests
  - `pages/admin.php` — Admin-only panel (requires admin role), shows stats, users table with ban/unban/delete, posts table with delete
- **Description:** Notifications page handles all types: likes, comments, friend requests, messages. Admin panel is protected by `require_admin()`. Shows summary stats cards at the top. Banning a user prevents login; deleting a user cascades to remove all their data. Posts deletion also removes associated likes and comments.

---

## [2026-04-23 00:45]
### STEP 10 — Frontend UI
- **Files created:**
  - `assets/css/main.css` — Full design system: CSS variables, navbar, cards, buttons, forms, auth, feed, profiles, messages, notifications, admin, responsive
  - `assets/css/filters.css` — 7 CSS image filters (none, warm, cool, mono, vintage, fade, vivid)
  - `assets/js/main.js` — Global JS (nav dropdown, auto-dismiss alerts, click-away handlers)
  - `assets/js/posts.js` — AJAX like/unlike, comment toggle, load comments, submit comment
  - `assets/js/search.js` — Debounced live user search in navbar and message new-conversation
  - `assets/js/messages.js` — Message sending + 3-second polling for new messages
  - `assets/js/create_post.js` — Image preview, drag-and-drop upload, live filter preview, character counter
  - `api/search_users.php` — User search API endpoint
  - `assets/images/default_avatar.png` — Default user avatar
- **Description:** Complete Instagram/Facebook hybrid UI. Dark gradient logo, sticky navbar with badges, card-based content, gradient primary color. Fully responsive with mobile breakpoints. All interactions use vanilla JS with fetch API. No frameworks used.

---
