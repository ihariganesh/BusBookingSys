<?php
// filepath: c:\xampp\htdocs\Bus_booking_system\register_conductor.php
session_start();
require_once 'includes/config.php';

// Fetch all buses for dropdown
$buses = [];
$stmt = $pdo->query("SELECT bus_id, bus_name, bus_number FROM buses");
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $bus_id = intval($_POST['bus_id'] ?? 0);

    // Validation
    if ($username === '' || $password === '' || $confirm_password === '' || !$bus_id) {
        $errors[] = "All fields are required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    // Check if username exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = "Username already exists.";
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'conductor')");
        $stmt->execute([$username, $hashed]);
        $user_id = $pdo->lastInsertId();

        // Link conductor to bus
        $stmt = $pdo->prepare("INSERT INTO conductors (user_id, bus_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $bus_id]);

        $success = "Registration successful! You can now <a href='login.php'>login</a>.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Conductor Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Register as Conductor</h2>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="card p-4 shadow-sm bg-white">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Select Bus</label>
            <select name="bus_id" class="form-select" required>
                <option value="">-- Select Bus --</option>
                <?php foreach ($buses as $bus): ?>
                    <option value="<?php echo $bus['bus_id']; ?>"
                        <?php if (isset($_POST['bus_id']) && $_POST['bus_id'] == $bus['bus_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($bus['bus_name'] . " (" . $bus['bus_number'] . ")"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Register as Conductor</button>
    </form>
</div>
</body>
</html>