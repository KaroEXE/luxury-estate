<?php
session_start();
require_once __DIR__ . '/config/database.php';
$db = new Database();
$conn = $db->getConnection();
if (isset($_GET['boot'])) {
    $_SESSION['is_admin'] = true;
    header('Location: admin.php');
    exit;
}
if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}
$errors = [];
$success = '';
function post($k){return isset($_POST[$k])?trim($_POST[$k]):'';}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'create') {
        try {
            $stmt = $conn->prepare("INSERT INTO properties (title, description, price, property_type, bedrooms, bathrooms, area_sqft, location, address, latitude, longitude, features, status, agent_id) VALUES (:title, :description, :price, :property_type, :bedrooms, :bathrooms, :area_sqft, :location, :address, :latitude, :longitude, :features, :status, NULL)");
            $stmt->execute([
                ':title'=>post('title'),
                ':description'=>post('description'),
                ':price'=>post('price'),
                ':property_type'=>post('property_type'),
                ':bedrooms'=>post('bedrooms'),
                ':bathrooms'=>post('bathrooms'),
                ':area_sqft'=>post('area_sqft'),
                ':location'=>post('location'),
                ':address'=>post('address'),
                ':latitude'=>post('latitude') !== '' ? post('latitude') : null,
                ':longitude'=>post('longitude') !== '' ? post('longitude') : null,
                ':features'=>post('features'),
                ':status'=>post('status') !== '' ? post('status') : 'available'
            ]);
            $pid = $conn->lastInsertId();
            $images = array_filter(array_map('trim', preg_split('/\r?\n/', post('images'))));
            if ($images) {
                $stmtI = $conn->prepare("INSERT INTO property_images (property_id, image_url, is_primary) VALUES (:pid, :url, :primary)");
                foreach ($images as $i => $url) {
                    $stmtI->execute([':pid'=>$pid, ':url'=>$url, ':primary'=>$i===0?1:0]);
                }
            }
            $success = 'Created';
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    } elseif ($action === 'update') {
        try {
            $pid = (int)post('id');
            $stmt = $conn->prepare("UPDATE properties SET title=:title, description=:description, price=:price, property_type=:property_type, bedrooms=:bedrooms, bathrooms=:bathrooms, area_sqft=:area_sqft, location=:location, address=:address, latitude=:latitude, longitude=:longitude, features=:features, status=:status WHERE id=:id");
            $stmt->execute([
                ':title'=>post('title'),
                ':description'=>post('description'),
                ':price'=>post('price'),
                ':property_type'=>post('property_type'),
                ':bedrooms'=>post('bedrooms'),
                ':bathrooms'=>post('bathrooms'),
                ':area_sqft'=>post('area_sqft'),
                ':location'=>post('location'),
                ':address'=>post('address'),
                ':latitude'=>post('latitude') !== '' ? post('latitude') : null,
                ':longitude'=>post('longitude') !== '' ? post('longitude') : null,
                ':features'=>post('features'),
                ':status'=>post('status'),
                ':id'=>$pid
            ]);
            if (post('images') !== '') {
                $conn->prepare("DELETE FROM property_images WHERE property_id=:pid")->execute([':pid'=>$pid]);
                $images = array_filter(array_map('trim', preg_split('/\r?\n/', post('images'))));
                if ($images) {
                    $stmtI = $conn->prepare("INSERT INTO property_images (property_id, image_url, is_primary) VALUES (:pid, :url, :primary)");
                    foreach ($images as $i => $url) {
                        $stmtI->execute([':pid'=>$pid, ':url'=>$url, ':primary'=>$i===0?1:0]);
                    }
                }
            }
            $success = 'Updated';
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    } elseif ($action === 'delete') {
        try {
            $pid = (int)post('id');
            $conn->prepare("DELETE FROM properties WHERE id=:id")->execute([':id'=>$pid]);
            $success = 'Deleted';
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
$props = [];
try {
    $stmt = $conn->query("SELECT p.*, (SELECT image_url FROM property_images WHERE property_id=p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image FROM properties p ORDER BY p.created_at DESC");
    $props = $stmt->fetchAll();
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel - Luxury Estate</title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
body { padding-top: 70px; }
.header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.admin-wrap { max-width: 1200px; margin: 0 auto; padding: 20px; }
.admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.admin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 20px; }
.admin-card { background: var(--card-bg,#fff); border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.08); padding: 20px; }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th, .admin-table td { padding: 10px; border-bottom: 1px solid rgba(0,0,0,.06); text-align: left; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.form-grid .full { grid-column: 1 / -1; }
input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: transparent; }
.actions { display: flex; gap: 8px; }
img.thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
.badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #f1f1f1; font-size: 12px; }
.edit-form { display: none; margin-top: 12px; }
.btn { cursor: pointer; }
</style>
</head>
<body>
<header class="header">
    <nav class="navbar">
        <div class="nav-brand">
            <span>Luxury Estate</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="#">Admin</a></li>
        </ul>
        <div class="nav-actions">
            <a class="btn btn-outline" href="admin.php?logout=1">Logout</a>
        </div>
    </nav>
</header>
<div class="admin-wrap">
    <div class="admin-header">
        <h2>Admin Panel</h2>
        <?php if ($success): ?><div class="badge"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="badge" style="background:#ffe0e0">Error</div><?php endif; ?>
    </div>
    <div class="admin-grid">
        <div class="admin-card">
            <h3>Add Property</h3>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div><input name="title" placeholder="Title" required></div>
                    <div><input name="price" placeholder="Price" type="number" step="0.01" required></div>
                    <div>
                        <select name="property_type" required>
                            <option value="apartment">Apartment</option>
                            <option value="house">House</option>
                            <option value="villa">Villa</option>
                            <option value="townhouse">Townhouse</option>
                            <option value="penthouse">Penthouse</option>
                            <option value="commercial">Commercial</option>
                        </select>
                    </div>
                    <div><input name="location" placeholder="Location" required></div>
                    <div><input name="address" placeholder="Address" required></div>
                    <div><input name="bedrooms" type="number" placeholder="Bedrooms" required></div>
                    <div><input name="bathrooms" type="number" placeholder="Bathrooms" required></div>
                    <div><input name="area_sqft" type="number" placeholder="Area Sqft" required></div>
                    <div><input name="latitude" type="number" step="0.00000001" placeholder="Latitude"></div>
                    <div><input name="longitude" type="number" step="0.00000001" placeholder="Longitude"></div>
                    <div class="full"><textarea name="features" placeholder="Features"></textarea></div>
                    <div class="full"><textarea name="description" placeholder="Description"></textarea></div>
                    <div class="full"><textarea name="images" placeholder="Image URLs, one per line"></textarea></div>
                    <div>
                        <select name="status">
                            <option value="available">Available</option>
                            <option value="sold">Sold</option>
                            <option value="rented">Rented</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div><button class="btn btn-primary" type="submit">Create</button></div>
                </div>
            </form>
        </div>
        <div class="admin-card">
            <h3>Properties</h3>
            <?php if ($errors): ?>
                <div style="color:#c00;margin-bottom:10px"><?php echo htmlspecialchars(implode(' | ', $errors)); ?></div>
            <?php endif; ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($props as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php if ($p['primary_image']): ?><img class="thumb" src="<?php echo htmlspecialchars($p['primary_image']); ?>"><?php endif; ?></td>
                        <td><?php echo htmlspecialchars($p['title']); ?></td>
                        <td><span class="badge"><?php echo htmlspecialchars($p['property_type']); ?></span></td>
                        <td>$<?php echo number_format((float)$p['price'],2); ?></td>
                        <td><?php echo htmlspecialchars($p['status']); ?></td>
                        <td class="actions">
                            <button class="btn btn-outline" onclick="toggleEdit(<?php echo (int)$p['id']; ?>)">Edit</button>
                            <form method="post" onsubmit="return confirm('Delete property?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                <button class="btn btn-outline" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <tr id="edit-<?php echo (int)$p['id']; ?>" class="edit-form">
                        <td colspan="7">
                            <?php
                            $imgs = [];
                            try {
                                $s2 = $conn->prepare('SELECT image_url FROM property_images WHERE property_id=:pid ORDER BY is_primary DESC, id ASC');
                                $s2->execute([':pid'=>$p['id']]);
                                $imgs = $s2->fetchAll(PDO::FETCH_COLUMN);
                            } catch (Exception $e) {}
                            ?>
                            <form method="post">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                <div class="form-grid">
                                    <div><input name="title" value="<?php echo htmlspecialchars($p['title']); ?>" required></div>
                                    <div><input name="price" type="number" step="0.01" value="<?php echo htmlspecialchars($p['price']); ?>" required></div>
                                    <div>
                                        <select name="property_type" required>
                                            <?php foreach (['apartment','house','villa','townhouse','penthouse','commercial'] as $opt): ?>
                                                <option value="<?php echo $opt; ?>" <?php echo $p['property_type']===$opt?'selected':''; ?>><?php echo ucfirst($opt); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div><input name="location" value="<?php echo htmlspecialchars($p['location']); ?>" required></div>
                                    <div><input name="address" value="<?php echo htmlspecialchars($p['address']); ?>" required></div>
                                    <div><input name="bedrooms" type="number" value="<?php echo (int)$p['bedrooms']; ?>" required></div>
                                    <div><input name="bathrooms" type="number" value="<?php echo (int)$p['bathrooms']; ?>" required></div>
                                    <div><input name="area_sqft" type="number" value="<?php echo (int)$p['area_sqft']; ?>" required></div>
                                    <div><input name="latitude" type="number" step="0.00000001" value="<?php echo htmlspecialchars($p['latitude']); ?>"></div>
                                    <div><input name="longitude" type="number" step="0.00000001" value="<?php echo htmlspecialchars($p['longitude']); ?>"></div>
                                    <div class="full"><textarea name="features"><?php echo htmlspecialchars($p['features']); ?></textarea></div>
                                    <div class="full"><textarea name="description"><?php echo htmlspecialchars($p['description']); ?></textarea></div>
                                    <div class="full"><textarea name="images" placeholder="Image URLs, one per line"><?php echo htmlspecialchars(implode("\n", $imgs)); ?></textarea></div>
                                    <div>
                                        <select name="status">
                                            <?php foreach (['available','sold','rented','pending'] as $st): ?>
                                                <option value="<?php echo $st; ?>" <?php echo $p['status']===$st?'selected':''; ?>><?php echo ucfirst($st); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div><button class="btn btn-primary" type="submit">Save</button></div>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function toggleEdit(id){
    var row=document.getElementById('edit-'+id);
    if(!row)return;row.style.display=row.style.display==='table-row'?'none':'table-row';
}
</script>
</body>
</html>
