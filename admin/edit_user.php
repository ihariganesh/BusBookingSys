<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = (int)$_GET['id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php');
    exit;
}

// Get assigned bus for conductor (if any)
$assigned_bus_id = null;
$assigned_bus = $pdo->prepare("SELECT bus_id FROM conductors WHERE user_id = ?");
$assigned_bus->execute([$user_id]);
$assigned_bus_id = $assigned_bus->fetchColumn();

// Fetch all buses for dropdown
$buses = $pdo->query("SELECT bus_id, bus_name, bus_number FROM buses")->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $role = $_POST['role'];
    $bus_id = isset($_POST['bus_id']) ? intval($_POST['bus_id']) : null;

    // Validate inputs
    $errors = [];

    if (empty($username)) {
        $errors[] = "Username is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if ($role === 'conductor' && !$bus_id) {
        $errors[] = "Please assign a bus to the conductor.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET 
                                 username = :username,
                                 full_name = :full_name,
                                 email = :email,
                                 phone = :phone,
                                 is_admin = :is_admin,
                                 role = :role
                                 WHERE user_id = :user_id");
            $stmt->execute([
                ':username' => $username,
                ':full_name' => $full_name,
                ':email' => $email,
                ':phone' => $phone,
                ':is_admin' => $is_admin,
                ':role' => $role,
                ':user_id' => $user_id
            ]);

            // Handle conductor assignment
            if ($role === 'conductor' && $bus_id) {
                // Insert or update conductor's bus assignment
                $exists = $pdo->prepare("SELECT conductor_id FROM conductors WHERE user_id = ?");
                $exists->execute([$user_id]);
                if ($exists->fetch()) {
                    $stmt = $pdo->prepare("UPDATE conductors SET bus_id = ? WHERE user_id = ?");
                    $stmt->execute([$bus_id, $user_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO conductors (user_id, bus_id) VALUES (?, ?)");
                    $stmt->execute([$user_id, $bus_id]);
                }
            } else {
                // Remove from conductors table if not a conductor
                $stmt = $pdo->prepare("DELETE FROM conductors WHERE user_id = ?");
                $stmt->execute([$user_id]);
            }

            $_SESSION['success'] = "User updated successfully!";
            header('Location: users.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Error updating user: " . $e->getMessage();
        }
    }
}
$page_title = "Edit User";
include('../includes/header.php');
?>

<div class="container">
    <h2 class="mb-4">Edit User</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" 
                           <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_admin">Admin User</label>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required onchange="toggleBusSelect()">
                        <option value="user" <?php if($user['role']=='user') echo 'selected'; ?>>User</option>
                        <option value="conductor" <?php if($user['role']=='conductor') echo 'selected'; ?>>Conductor</option>
                        <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
                    </select>
                </div>
                <div class="mb-3" id="busSelectDiv" style="display: none;">
                    <label for="bus_id" class="form-label">Assign Bus (for Conductor)</label>
                    <select class="form-select" id="bus_id" name="bus_id">
                        <option value="">-- Select Bus --</option>
                        <?php foreach ($buses as $bus): ?>
                            <option value="<?php echo $bus['bus_id']; ?>"
                                <?php if($assigned_bus_id == $bus['bus_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($bus['bus_name'] . " (" . $bus['bus_number'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update User</button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<script>
function toggleBusSelect() {
    var role = document.getElementById('role').value;
    document.getElementById('busSelectDiv').style.display = (role === 'conductor') ? 'block' : 'none';
}
window.onload = toggleBusSelect;
</script>

<?php include('../includes/footer.php'); ?>