<?php
declare(strict_types=1);
require __DIR__ . '/../partials/nav.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Clubes</h1>
    <a href="/admin/clubs/create" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo club
    </a>
</div>

<?php if (empty($clubs)): ?>
    <div class="alert alert-info">No hay clubes registrados.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Club</th>
                    <th>País</th>
                    <th>Escudo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clubs as $club): ?>
                    <tr>
                        <td><?= (int)$club['id'] ?></td>
                        <td><?= htmlspecialchars((string)$club['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if(!empty($club['country_name'])): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars((string)$club['country_name']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">Sin país</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($club['badge_path'])): ?>
                                <img src="<?= htmlspecialchars((string)$club['badge_path'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="Escudo"
                                     style="width:30px;height:30px;object-fit:contain;">
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                             <a href="/admin/clubs/edit?id=<?= (int)$club['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
    <i class="bi bi-pencil"></i>
</a>
                                <form action="/admin/clubs/delete" method="POST" onsubmit="return confirm('¿Eliminar club?');">
                                    <input type="hidden" name="id" value="<?= (int)$club['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
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