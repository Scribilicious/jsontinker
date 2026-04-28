<?php
session_start();

require_once 'libs/config.php';
require_once 'libs/JsonFile.php';
require_once 'libs/Helper.php';

$showLogin = false;
$loginError = '';
if (!empty($config['keys']) && empty($_SESSION['key'])) {
    if (!empty($_GET['k']) && in_array(hash('sha256', $_GET['k']), $config['keys'])) {
        $_SESSION['key'] = hash('sha256', $_GET['k']);
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $hash = hash('sha256', $_POST['username'] . $_POST['password']);
        if (in_array($hash, $config['keys'])) {
            $_SESSION['key'] = $hash;
        } else {
            $showLogin = true;
            $loginError = 'Invalid username or password';
        }
    } else {
        $showLogin = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 16 16'><text x='0' y='14'><?= $config['favicon'] ?? '🌻'; ?></text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config['title']; ?></title>
    <meta name="description" content="<?= $config['description']; ?>">
    <meta name="robots" content="<?= empty($config['visible']) ? 'noindex, nofollow' : 'index, follow'; ?>">
    <link rel="stylesheet" href="styles/app.css">
</head>
<body>
<?php
if ($showLogin):
?>
<div class="container login">
    <header>
        <h1>Login</h1>
    </header>
    <div class="login-form">
        <?php if (!empty($loginError)): ?>
            <div class="message error"><?php echo htmlspecialchars($loginError); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</div>
<?php
else:

// Configuration
$dataDir = 'data';
$jsonFiles = [];
$Helper = new Helper();

// Scan data directory for JSON files
if (is_dir($dataDir)) {
    $files = scandir($dataDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $jsonFiles[] = $file;
        }
    }
}

// Get selected file
$selectedFile = $_GET['file'] ?? ($jsonFiles[0] ?? '');
$jsonFilePath = $dataDir . '/' . $selectedFile;
$jsonData = null;
$message = '';

// Initialize JsonFile object
if ($selectedFile && file_exists($jsonFilePath)) {
    $jsonFile = new JsonFile($jsonFilePath);
    $jsonData = $jsonFile->read();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['data'])) {
        $newData = $_POST['data'];

        // Process form data
        $processedData = $Helper->processFormData($newData);

        try {
            $jsonFile->validate($processedData);
            $jsonFile->write($processedData);
            $jsonData = $processedData;
            $message = 'File saved successfully!';
        } catch (Exception $e) {
            $message = 'Error saving file: ' . $e->getMessage();
        }
    }
}
?>
<div class="container">
    <header>
        <h1><?= $config['title']; ?></h1>
        <div class="subtitle"><?= $config['description']; ?></div>
        <button class="menu-toggle"><span class="toggle-icon">▶</span> <span class="toggle-text">Menu</span></button>
    </header>
    <div class="main-content">
        <div class="sidebar-overlay"></div>
        <div class="sidebar">
            <h2>Files</h2>
            <ul class="file-list">
                <?php foreach ($jsonFiles as $file): ?>
                    <li>
                        <a href="?file=<?php echo urlencode($file); ?>"
                           class="<?php echo $selectedFile === $file ? 'active' : ''; ?>">
                            <?php echo $Helper->createTitle($file); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="editor-area">
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($selectedFile && $jsonData !== null): ?>
                <?php if (empty($jsonData) && is_array($jsonData)): ?>
                    <div class="no-file">
                        <h2><?php echo $Helper->createTitle($selectedFile); ?> is empty</h2>
                        <p>This JSON file contains no data.</p>
                    </div>
                <?php else: ?>
                    <?php $renderedFields = $Helper->renderFormFields($jsonData); ?>
                    <form method="post" action="?file=<?php echo urlencode($selectedFile); ?>">
                        <h2 class="toggle-all-header" onclick="toggleAllGroups()"><?php if (strpos($renderedFields, '<div class="nested-section') !== false): ?><span class="toggle-all-arrow">▼</span> <?php endif; ?>Editing: <?php echo $Helper->createTitle($selectedFile); ?></h2>
                        <?php echo $renderedFields; ?>
                    <div class="actions">
                        <button type="reset" class="btn btn-reset">Reset Changes</button>
                        <button type="submit" class="btn">Save Changes</button>
                    </div>
                </form>
                <?php endif; ?>
            <?php elseif ($selectedFile): ?>
                <div class="no-file">
                    <h2>File not found or invalid JSON</h2>
                    <p>The file "<?php echo htmlspecialchars($selectedFile); ?>" could not be loaded.</p>
                </div>
            <?php else: ?>
                <div class="no-file">
                    <h2>No JSON files found</h2>
                    <p>Place JSON files in the "data" directory to start editing.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<footer>Build with ❤️ by <a href="https://www.mrbot.de" target="_blank">Mr.Bot</a> v<?= $config['version']; ?></footer>
<script src="js/app.js"></script>
<?php
endif;
?>
</body>
</html>
