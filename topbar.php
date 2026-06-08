<header class="top-bar">
    <div class="search-bar">
        <input type="text" placeholder="Search...">
    </div>
    <div class="top-bar-actions">
        <button class="notification-btn">🔔</button>
        <div class="user-avatar">
            <span><?php echo isset($user['name']) ? substr($user['name'], 0, 1) : '?'; ?></span>
        </div>
    </div>
</header>