<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;
use Throwable;

/**
 * Controlador público de testimonios.
 *
 * Muestra testimonios activos cargados desde el panel administrativo.
 */
class TestimonialsController extends BaseController
{
    /**
     * Muestra la página pública de testimonios.
     *
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
     * @return void
     */
    public function index(Request $request, Response $response): void
    {
        $testimonials = [];

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->query('
                SELECT
                    id,
                    photo_path,
                    name,
                    country,
                    comment,
                    rating,
                    status,
                    display_order,
                    created_at
                FROM testimonials
                WHERE status = "ACTIVE"
                ORDER BY display_order ASC, id DESC
            ');

            $testimonials = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            error_log('Error cargando testimonios públicos: ' . $e->getMessage());
        }

        $this->render('testimonials/index', [
            'pageTitle' => 'Testimonios de jugadores | Quinielas deportivas',
            'metaDescription' => 'Conoce experiencias reales de jugadores que participan en nuestras quinielas deportivas de fútbol latino en Estados Unidos.',
            'testimonials' => $testimonials,
        ]);
    }
}