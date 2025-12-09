<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth_check.php';

if (!is_admin()) {
    redirect('/dashboard.php');
}

$migrations_dir = __DIR__ . '/../migrations/';
$all_migration_files = array_filter(scandir($migrations_dir), function($file) {
    return pathinfo($file, PATHINFO_EXTENSION) === 'sql';
});
sort($all_migration_files);

$applied_migrations = [];
try {
    // Check if migrations table exists first
    $pdo->query("SELECT 1 FROM `migrations` LIMIT 1");
    $stmt = $pdo->query("SELECT migration_name FROM `migrations`");
    $applied_migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Table doesn't exist, which is fine. It will be created by the first migration.
    if (strpos($e->getMessage(), "Table 'billpoint.migrations' doesn't exist") === false) {
        set_flash_message('error', 'Database error checking migrations table: ' . $e->getMessage());
    }
}

$pending_migrations = array_diff($all_migration_files, $applied_migrations);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
    } else {
        $run_count = 0;
        try {
            foreach ($pending_migrations as $migration) {
                $sql = file_get_contents($migrations_dir . $migration);
                $pdo->exec($sql);

                $stmt = $pdo->prepare("INSERT INTO `migrations` (migration_name) VALUES (?)");
                $stmt->execute([$migration]);
                $run_count++;
            }
            if ($run_count > 0) {
                set_flash_message('success', "Successfully applied {$run_count} new migration(s).");
            } else {
                set_flash_message('info', "No new migrations to apply.");
            }
        } catch (PDOException $e) {
            set_flash_message('error', "An error occurred during migration: " . $e->getMessage());
        }
        redirect('/admin/migrations.php');
    }
}

$page_title = 'Database Migrations';
include '../includes/header.php';
?>
<div class="container mt-4">
    <h1>Database Migrations</h1>
    <p>Apply new database schema changes to keep the application up to date.</p>

    <?php display_flash_message(); ?>

    <div class="content-box">
        <h3>Pending Migrations</h3>
        <?php if (empty($pending_migrations)): ?>
            <p class="text-success">The database schema is up to date.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($pending_migrations as $migration): ?>
                    <li><?php echo htmlspecialchars($migration); ?></li>
                <?php endforeach; ?>
            </ul>
            <form action="migrations.php" method="POST">
                <?php csrf_field(); ?>
                <button type="submit" name="run_migrations" class="btn btn-primary">Run Pending Migrations</button>
            </form>
        <?php endif; ?>
    </div>

     <div class="content-box mt-4">
        <h3>Applied Migrations</h3>
         <?php if (empty($applied_migrations)): ?>
            <p>No migrations have been applied yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($applied_migrations as $migration): ?>
                    <li><?php echo htmlspecialchars($migration); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
