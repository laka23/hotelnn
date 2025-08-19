<section class="card auth-card">
    <h1>Login</h1>
    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="?page=auth&action=login">
        <label>Username
            <input type="text" name="username" required>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button class="btn primary" type="submit">Login</button>
    </form>
</section>

