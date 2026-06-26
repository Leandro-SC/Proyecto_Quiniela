<?php require __DIR__ . '/../partials/nav.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<script>
    $(document).ready(function() {
        $('#editorReglamento').summernote({
            placeholder: 'Escribe aquí el reglamento del juego...',
            tabsize: 2,
            height: 500, // Altura del editor
            lang: 'es-ES', // Idioma (si no carga español, usará inglés por defecto)
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']], // Quitamos imagen/video para hacerlo más simple
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });
</script>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>📜 Gestión de Reglamento</h3>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Reglamento guardado y publicado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
            <small class="text-muted">
                <i class="bi bi-info-circle"></i> Editor habilitado. Puedes escribir, poner negritas y listas fácilmente.
            </small>
        </div>
        <div class="card-body">
            <form action="/admin/regulations/update" method="POST">
                <div class="mb-3">
                    <textarea id="editorReglamento" name="content"><?= htmlspecialchars($content) ?></textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="/admin/dashboard" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">
                        <i class="bi bi-save me-2"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>