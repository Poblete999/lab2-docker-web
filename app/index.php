<?php
// ============================================
// LAB CRUD - Refugio de Mascotas
// ============================================

$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . htmlspecialchars($e->getMessage()));
}

$action = $_GET['action'] ?? 'list';

// === PROCESAR CREATE / UPDATE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $especie     = $_POST['especie'] ?? 'perro';
    $raza        = trim($_POST['raza'] ?? '');
    $edad        = (int)($_POST['edad'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado      = $_POST['estado'] ?? 'disponible';

    if ($action === 'create') {
        $stmt = $pdo->prepare(
            "INSERT INTO mascotas (nombre, especie, raza, edad, descripcion, estado)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$nombre, $especie, $raza, $edad, $descripcion, $estado]);
        header('Location: index.php?msg=created');
        exit;
    }

    if ($action === 'update' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare(
            "UPDATE mascotas
             SET nombre=?, especie=?, raza=?, edad=?, descripcion=?, estado=?
             WHERE id=?"
        );
        $stmt->execute([$nombre, $especie, $raza, $edad, $descripcion, $estado, $id]);
        header('Location: index.php?msg=updated');
        exit;
    }
}

// === PROCESAR DELETE ===
if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM mascotas WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    header('Location: index.php?msg=deleted');
    exit;
}

// === MENSAJES FLASH ===
$mensaje = ''; $tipo_mensaje = '';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'created': $mensaje = "Mascota registrada correctamente."; $tipo_mensaje = 'success'; break;
        case 'updated': $mensaje = "Datos de la mascota actualizados."; $tipo_mensaje = 'success'; break;
        case 'deleted': $mensaje = "Mascota eliminada del refugio.";    $tipo_mensaje = 'warning'; break;
    }
}

// === CARGAR DATOS PARA LAS VISTAS ===
$mascotas = []; $mascota_editar = null;

if ($action === 'list') {
    $mascotas = $pdo->query("SELECT * FROM mascotas ORDER BY id DESC")
                    ->fetchAll(PDO::FETCH_ASSOC);
}

if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM mascotas WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $mascota_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mascota_editar) { header('Location: index.php'); exit; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>🐾 Refugio de Mascotas</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
               background: #f5f5f7; margin: 0; color: #1d1d1f; }
        header { background: #2c3e50; color: white; padding: 1rem 2rem;
                 display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 1.5rem; }
        nav a { color: white; text-decoration: none; margin-left: 1rem;
                padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.1); border-radius: 4px; }
        nav a:hover { background: rgba(255,255,255,0.2); }
        main { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .alert { padding: 0.8rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-warning { background: #fff3cd; color: #856404; }
        .card { background: white; padding: 1.5rem 2rem; border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #fafafa; }
        .badge { display: inline-block; padding: 0.2rem 0.6rem;
                 border-radius: 12px; font-size: 0.85rem; font-weight: 500; }
        .badge-disponible { background: #d1ecf1; color: #0c5460; }
        .badge-en_proceso { background: #fff3cd; color: #856404; }
        .badge-adoptado   { background: #d4edda; color: #155724; }
        .btn { display: inline-block; padding: 0.4rem 0.8rem; border-radius: 4px;
               border: none; cursor: pointer; text-decoration: none;
               font-size: 0.9rem; margin-right: 0.3rem; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger  { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.85; }
        form .form-group { margin-bottom: 1rem; }
        form label { display: block; font-weight: 500; margin-bottom: 0.3rem; }
        form input, form select, form textarea {
            width: 100%; padding: 0.5rem; border: 1px solid #ccc;
            border-radius: 4px; font-size: 1rem; font-family: inherit;
        }
        form textarea { min-height: 80px; resize: vertical; }
        .actions { white-space: nowrap; }
        h2 { margin-top: 0; }
    </style>
</head>
<body>

<header>
    <h1>🐾 Refugio de Mascotas</h1>
    <nav>
        <a href="index.php">📋 Listado</a>
        <a href="index.php?action=create">➕ Nueva Mascota</a>
    </nav>
</header>

<main>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_mensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <h2>Mascotas registradas</h2>
        <?php if (count($mascotas) === 0): ?>
            <p>Aún no hay mascotas. <a href="index.php?action=create">Registrar la primera</a>.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Especie</th><th>Raza</th>
                        <th>Edad</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                <?php foreach ($mascotas as $m): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= htmlspecialchars($m['nombre']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($m['especie'])) ?></td>
                        <td><?= htmlspecialchars($m['raza'] ?? '-') ?></td>
                        <td><?= (int)$m['edad'] ?> año<?= $m['edad'] != 1 ? 's' : '' ?></td>
                        <td>
                            <span class="badge badge-<?= $m['estado'] ?>">
                                <?= htmlspecialchars(str_replace('_', ' ', ucfirst($m['estado']))) ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a class="btn btn-warning" href="index.php?action=edit&id=<?= $m['id'] ?>">Editar</a>
                            <a class="btn btn-danger"
                               href="index.php?action=delete&id=<?= $m['id'] ?>"
                               onclick="return confirm('¿Eliminar a <?= htmlspecialchars($m['nombre'], ENT_QUOTES) ?> del refugio?');">
                                Borrar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'create' || ($action === 'edit' && $mascota_editar)): ?>
    <?php $editing = ($action === 'edit'); $m = $editing ? $mascota_editar : []; ?>
    <div class="card">
        <h2><?= $editing ? 'Editar mascota' : 'Registrar nueva mascota' ?></h2>
        <form method="POST" action="index.php?action=<?= $editing ? 'update' : 'create' ?>">
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" required value="<?= htmlspecialchars($m['nombre'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Especie *</label>
                <select name="especie" required>
                    <?php foreach (['perro','gato','otro'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($m['especie'] ?? '') === $opt ? 'selected' : '' ?>>
                            <?= ucfirst($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Raza</label>
                <input type="text" name="raza" value="<?= htmlspecialchars($m['raza'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Edad (años) *</label>
                <input type="number" name="edad" required min="0" max="40" value="<?= (int)($m['edad'] ?? 0) ?>">
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion"><?= htmlspecialchars($m['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Estado *</label>
                <select name="estado" required>
                    <?php foreach (['disponible','en_proceso','adoptado'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($m['estado'] ?? 'disponible') === $opt ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $opt)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-success">
                <?= $editing ? 'Guardar cambios' : 'Registrar' ?>
            </button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
<?php endif; ?>

</main>

</body>
</html>
