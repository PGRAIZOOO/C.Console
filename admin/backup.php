<?php
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/backup.php');
$admin = require_admin();

if (is_post()) {
    if (upload_too_large()) {
        flash('error', 'The backup file is larger than the upload limit of ' . round(backup_upload_limit() / 1048576, 1) . ' MB.');
        redirect('admin/backup.php');
    }
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'download') {
        @set_time_limit(0);
        $path = tempnam(sys_get_temp_dir(), 'ccd');
        try {
            if ($path === false) {
                throw new RuntimeException('A temporary file could not be created.');
            }
            backup_build($path);
        } catch (Throwable $error) {
            if ($path !== false) {
                @unlink($path);
            }
            flash('error', 'The backup could not be created. Nothing was changed.');
            redirect('admin/backup.php');
        }
        header('Content-Type: application/sql; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . backup_file_name() . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        readfile($path);
        @unlink($path);
        exit;
    }

    if ($action === 'restore') {
        if (($_POST['confirm_text'] ?? '') !== 'RESTORE' || empty($_POST['confirm_box'])) {
            flash('error', 'Tick the confirmation box and type RESTORE to confirm. Nothing was changed.');
            redirect('admin/backup.php');
        }
        $uploadError = backup_upload_error($_FILES['backup'] ?? null);
        if ($uploadError !== null) {
            flash('error', $uploadError . ' Nothing was changed.');
            redirect('admin/backup.php');
        }
        try {
            $statements = backup_validate($_FILES['backup']['tmp_name']);
        } catch (Throwable $error) {
            flash('error', $error->getMessage() . ' Nothing was changed.');
            redirect('admin/backup.php');
        }
        try {
            $safety = backup_save_safety();
        } catch (Throwable $error) {
            flash('error', 'A safety copy of the current data could not be saved, so the restore was cancelled. Nothing was changed.');
            redirect('admin/backup.php');
        }
        try {
            backup_apply($statements);
        } catch (Throwable $error) {
            try {
                backup_apply(backup_validate($safety));
                flash('error', 'The restore failed and the previous data was put back. Nothing was lost.');
            } catch (Throwable $again) {
                flash('error', 'The restore failed and the previous data could not be put back automatically. Import the safety copy ' . basename($safety) . ' from the backups folder on the server.');
            }
            redirect('admin/backup.php');
        }
        clear_auth_state();
        session_regenerate_id(true);
        flash('success', 'The backup was restored. A safety copy of the previous data was saved as ' . basename($safety) . '. Please sign in again.');
        redirect('user/login.php');
    }

    flash('error', 'That action is not available.');
    redirect('admin/backup.php');
}

$limitMb = round(backup_upload_limit() / 1048576, 1);
[$tableNames, $viewNames] = backup_names();

$title = 'Backup and recovery';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Backup and recovery</h1>
        <p><?= count($tableNames) ?> tables and <?= count($viewNames) ?> views in the database</p>
    </div>
</div>

<div class="two">
    <div class="card" style="padding:24px">
        <h2 style="margin-bottom:8px">Create a backup</h2>
        <p style="color:var(--muted);margin-bottom:16px">Downloads a .sql copy of every table and view: users, games, stock, orders, reviews and messages. Sign-in sessions are not included. Uploaded photos and game images are files on the server and are not part of the backup.</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="download">
            <button class="btn btn-primary" type="submit">Download backup</button>
        </form>
    </div>

    <div class="card" style="padding:24px">
        <h2 style="margin-bottom:8px">Restore a backup</h2>
        <p style="color:var(--muted);margin-bottom:16px">Replaces all current data with the contents of a backup downloaded from this page. The file is checked first, and a safety copy of the current data is saved on the server before anything is replaced. Everyone, including you, will be signed out afterwards. Maximum file size: <?= e($limitMb) ?> MB.</p>
        <form method="post" enctype="multipart/form-data" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="restore">
            <div class="field">
                <label for="backup">Backup file (.sql)</label>
                <input type="file" id="backup" name="backup" accept=".sql" required>
            </div>
            <div class="field">
                <label for="confirm_text">Type RESTORE to confirm</label>
                <input type="text" id="confirm_text" name="confirm_text" autocomplete="off" required>
            </div>
            <label class="check"><input type="checkbox" name="confirm_box" value="1" required> I understand this replaces all current data.</label>
            <button class="btn btn-primary" type="submit" style="margin-top:12px">Restore backup</button>
        </form>
    </div>
</div>
<?php include('../includes/footer.php'); ?>
