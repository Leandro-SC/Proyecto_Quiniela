<?php
declare(strict_types=1);
require __DIR__ . '/../partials/nav.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Países</h1>
    <a href="/admin/countries/create" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo país
    </a>
</div>

<?php if (empty($countries)): ?>
    <div class="alert alert-info">No hay países registrados.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>ISO</th>
                    <th>Bandera</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($countries as $c): ?>
                    <tr>
                        <td><?= (int)$c['id'] ?></td>
                        <td><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge bg-dark"><?= htmlspecialchars((string)$c['iso_code'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <?php if (!empty($c['flag_path'])): ?>
                                <img src="<?= htmlspecialchars((string)$c['flag_path'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="Flag"
                                     style="width:24px;height:16px;object-fit:cover;border:1px solid #ccc;">
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                             <a href="/admin/countries/edit?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-pencil"></i>
</a>
                                <form action="/admin/countries/delete" method="POST" onsubmit="return confirm('¿Eliminar país?');">
                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>