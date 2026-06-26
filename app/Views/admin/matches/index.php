<?php

declare(strict_types=1);
/** @var array<string,mixed> $round */
/** @var array<string,mixed>|null $league */
/** @var array<int,array<string,mixed>> $matches */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1">
            Partidos de la jornada:
            <?= htmlspecialchars((string)$round['name'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <div class="small text-muted">
            Liga:
            <?= htmlspecialchars((string)($league['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($round['open_at']) && !empty($round['close_at'])): ?>
                · Del
                <?= htmlspecialchars((string)$round['open_at'], ENT_QUOTES, 'UTF-8') ?>
                al
                <?= htmlspecialchars((string)$round['close_at'], ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </div>
    </div>
    <a href="/admin/rounds/matches/create?round_id=<?= (int)$round['id'] ?>" class="btn btn-sm btn-success">
        Nuevo partido
    </a>
</div>

<?php if (empty($matches)): ?>
    <div class="alert alert-info">
        Aún no hay partidos configurados para esta jornada.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Local</th>
                    <th></th>
                    <th>Visitante</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $m): ?>
                    <tr>
                        <td><?= (int)$m['id'] ?></td>
                        <td class="text-start">
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($m['home_team_logo'])): ?>
                                    <img src="<?= htmlspecialchars((string)$m['home_team_logo'], ENT_QUOTES, 'UTF-8') ?>"
                                        alt="Escudo local"
                                        style="width:24px;height:24px;object-fit:contain;border-radius:50%;">
                                <?php endif; ?>
                                <span class="small fw-semibold text-uppercase">
                                    <?= htmlspecialchars((string)$m['home_team_name'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </td>
                        <td class="text-center">vs</td>
                        <td class="text-start">
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($m['away_team_logo'])): ?>
                                    <img src="<?= htmlspecialchars((string)$m['away_team_logo'], ENT_QUOTES, 'UTF-8') ?>"
                                        alt="Escudo visitante"
                                        style="width:24px;height:24px;object-fit:contain;border-radius:50%;">
                                <?php endif; ?>
                                <span class="small fw-semibold text-uppercase">
                                    <?= htmlspecialchars((string)$m['away_team_name'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?= ($m['status'] === 'SCHEDULED' ? 'warning' : ($m['status'] === 'FINISHED' ? 'success' : 'secondary')) ?>">
                                <?= htmlspecialchars((string)$m['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="text-end">
                       <a href="/admin/rounds/matches/edit?round_id=<?= (int)$round['id'] ?>&match_id=<?= (int)$m['id'] ?>"
    class="btn btn-sm btn-warning">
    Editar
</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<a href="/admin/rounds" class="btn btn-link mt-3">Volver a jornadas</a>