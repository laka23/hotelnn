<section class="card">
    <h1>Initial Setup</h1>
    <p>Create the first admin user.</p>
    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="?page=auth&action=createAdmin">
        <label>Username
            <input type="text" name="username" required>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button class="btn primary" type="submit">Create Admin</button>
    </form>
</section>

