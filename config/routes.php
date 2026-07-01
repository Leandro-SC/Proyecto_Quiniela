<?php

declare(strict_types=1);

/*
 * Mapa de rutas de la aplicación.
 */

return [
    'GET' => [
        // ============================
        // PÚBLICO
        // ============================
        '/' => [
            'controller' => 'QuinielaController',
            'action'     => 'index',
        ],
        '/quiniela' => [
            'controller' => 'QuinielaController',
            'action'     => 'index',
        ],
        '/quiniela/anterior' => [
            'controller' => 'QuinielaController',
            'action'     => 'previous',
        ],
        '/quinielas-anteriores' => [
            'controller' => 'QuinielaController',
            'action'     => 'previous',
        ],
        '/ranking' => [
            'controller' => 'QuinielaController',
            'action'     => 'ranking',
        ],
        '/reglamento' => [
            'controller' => 'HomeController',
            'action'     => 'rules',
        ],
        '/verificador' => [
            'controller' => 'VerifierController',
            'action'     => 'index',
        ],
        '/testimonios' => [
            'controller' => 'TestimonialsController',
            'action'     => 'index',
        ],

        // ============================
        // API · PÚBLICA
        // ============================
        '/api/quiniela/current' => [
            'controller' => 'Api\\QuinielaController',
            'action'     => 'current',
        ],

        // ============================
        // ADMIN · AUTENTICACIÓN
        // ============================
        '/admin/login' => [
            'controller' => 'Admin\\AdminAuthController',
            'action'     => 'showLoginForm',
        ],
        '/admin/logout' => [
            'controller' => 'Admin\\AdminAuthController',
            'action'     => 'logout',
        ],

        // ============================
        // ADMIN · DASHBOARD
        // ============================
        '/admin' => [
            'controller' => 'Admin\\DashboardController',
            'action'     => 'index',
        ],
        '/admin/dashboard' => [
            'controller' => 'Admin\\DashboardController',
            'action'     => 'index',
        ],

        // ============================
        // ADMIN · LIGAS
        // ============================
        '/admin/leagues' => [
            'controller' => 'Admin\\LeagueAdminController',
            'action'     => 'index',
        ],
        '/admin/leagues/create' => [
            'controller' => 'Admin\\LeagueAdminController',
            'action'     => 'create',
        ],
        '/admin/leagues/edit' => [
            'controller' => 'Admin\\LeagueAdminController',
            'action'     => 'edit',
        ],

        // ============================
        // ADMIN · JORNADAS / ROUNDS
        // ============================
        '/admin/rounds' => [
            'controller' => 'Admin\\RoundAdminController',
            'action'     => 'index',
        ],
        '/admin/rounds/create' => [
            'controller' => 'Admin\\RoundAdminController',
            'action'     => 'create',
        ],
        '/admin/rounds/edit' => [
            'controller' => 'Admin\\RoundAdminController',
            'action'     => 'edit',
        ],

        // ============================
        // ADMIN · PARTIDOS
        // ============================
        '/admin/rounds/matches' => [
            'controller' => 'Admin\\RoundMatchesAdminController',
            'action'     => 'index',
        ],
        '/admin/rounds/matches/create' => [
            'controller' => 'Admin\\RoundMatchesAdminController',
            'action'     => 'create',
        ],
        '/admin/rounds/matches/edit' => [
            'controller' => 'Admin\\RoundMatchesAdminController',
            'action'     => 'edit',
        ],

        // Alias por compatibilidad con código anterior
        '/admin/matches/manage' => [
            'controller' => 'Admin\\MatchAdminController',
            'action'     => 'manage',
        ],

        // ============================
        // ADMIN · TICKETS / PAGOS
        // ============================
        '/admin/tickets' => [
            'controller' => 'Admin\\TicketAdminController',
            'action'     => 'index',
        ],
        '/admin/tickets/show' => [
            'controller' => 'Admin\\TicketAdminController',
            'action'     => 'show',
        ],

        // ============================
        // ADMIN · RANKING
        // ============================
        '/admin/ranking' => [
            'controller' => 'Admin\\RankingAdminController',
            'action'     => 'index',
        ],

        // ============================
        // ADMIN · HISTORIAL
        // ============================
        '/admin/history' => [
            'controller' => 'Admin\\RoundHistoryController',
            'action'     => 'index',
        ],

        // ============================
        // ADMIN · PROMOCIONES
        // ============================
        '/admin/promotions' => [
            'controller' => 'Admin\\PromotionAdminController',
            'action'     => 'index',
        ],
        '/admin/promotions/create' => [
            'controller' => 'Admin\\PromotionAdminController',
            'action'     => 'create',
        ],
        '/admin/promotions/edit' => [
            'controller' => 'Admin\\PromotionAdminController',
            'action'     => 'edit',
        ],

        // ============================
        // ADMIN · PAÍSES
        // ============================
        '/admin/countries' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'index',
        ],
        '/admin/countries/create' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'create',
        ],
        '/admin/countries/edit' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'edit',
        ],

        // ============================
        // ADMIN · CLUBES / EQUIPOS
        // ============================
        '/admin/clubs' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'index',
        ],
        '/admin/clubs/create' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'create',
        ],
        '/admin/clubs/edit' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'edit',
        ],
        '/admin/clubs/search' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'searchAjax',
        ],

        // ============================
        // ADMIN · CONFIGURACIÓN
        // ============================
        '/admin/settings' => [
            'controller' => 'Admin\\SettingsAdminController',
            'action'     => 'index',
        ],

        // ============================
        // ADMIN · REGLAMENTO
        // ============================
        '/admin/regulations' => [
            'controller' => 'Admin\\RegulationAdminController',
            'action'     => 'index',
        ],

        // ============================
        // ADMIN · TESTIMONIOS
        // ============================
        '/admin/testimonials' => [
            'controller' => 'Admin\\TestimonialAdminController',
            'action'     => 'index',
        ],
        '/admin/testimonials/create' => [
            'controller' => 'Admin\\TestimonialAdminController',
            'action'     => 'create',
        ],
        '/admin/testimonials/edit' => [
            'controller' => 'Admin\\TestimonialAdminController',
            'action'     => 'edit',
        ],
    ],

    'POST' => [
        // ============================
        // API · CLIENTE
        // ============================
        '/api/tickets/create' => [
            'controller' => 'Api\\TicketController',
            'action'     => 'create',
        ],

        // ============================
        // ADMIN · LOGIN
        // ============================
        '/admin/login' => [
            'controller' => 'Admin\\AdminAuthController',
            'action'     => 'login',
        ],

        // ============================
        // ADMIN · LIGAS
        // ============================
        '/admin/leagues/store' => [
            'controller' => 'Admin\\LeagueAdminController',
            'action'     => 'store',
        ],
        '/admin/leagues/update' => [
            'controller' => 'Admin\\LeagueAdminController',
            'action'     => 'update',
        ],
        '/admin/leagues/delete' => [
            'controller' => 'Admin\\LeagueAdminController',
            'action'     => 'delete',
        ],

        // Aliases por compatibilidad con formularios viejos
        '/admin/leagues/create' => [
            'controller' => 'Admin\\LeagueAdminController',
            'action'     => 'store',
        ],
        '/admin/leagues/edit' => [
            'controller' => 'Admin\\LeagueAdminController',
            'action'     => 'update',
        ],

        // ============================
        // ADMIN · JORNADAS / ROUNDS
        // ============================
        '/admin/rounds/store' => [
            'controller' => 'Admin\\RoundAdminController',
            'action'     => 'store',
        ],
        '/admin/rounds/update' => [
            'controller' => 'Admin\\RoundAdminController',
            'action'     => 'update',
        ],
        '/admin/rounds/delete' => [
            'controller' => 'Admin\\RoundAdminController',
            'action'     => 'delete',
        ],

        // Aliases por compatibilidad
        '/admin/rounds/create' => [
            'controller' => 'Admin\\RoundAdminController',
            'action'     => 'store',
        ],
        '/admin/rounds/edit' => [
            'controller' => 'Admin\\RoundAdminController',
            'action'     => 'update',
        ],

        // ============================
        // ADMIN · PARTIDOS
        // ============================
        '/admin/rounds/matches/create' => [
            'controller' => 'Admin\\RoundMatchesAdminController',
            'action'     => 'create',
        ],
        '/admin/rounds/matches/edit' => [
            'controller' => 'Admin\\RoundMatchesAdminController',
            'action'     => 'edit',
        ],
        '/admin/rounds/matches/delete' => [
            'controller' => 'Admin\\RoundMatchesAdminController',
            'action'     => 'delete',
        ],

        // Alias por compatibilidad con MatchAdminController
        '/admin/matches/store' => [
            'controller' => 'Admin\\MatchAdminController',
            'action'     => 'store',
        ],
        '/admin/matches/update' => [
            'controller' => 'Admin\\MatchAdminController',
            'action'     => 'update',
        ],
        '/admin/matches/delete' => [
            'controller' => 'Admin\\MatchAdminController',
            'action'     => 'delete',
        ],

        // ============================
        // ADMIN · TICKETS / PAGOS
        // ============================
        '/admin/tickets/update-status' => [
            'controller' => 'Admin\\TicketAdminController',
            'action'     => 'updateStatus',
        ],
        '/admin/tickets/delete' => [
            'controller' => 'Admin\\TicketAdminController',
            'action'     => 'delete',
        ],

        // ============================
        // ADMIN · PROMOCIONES
        // ============================
        '/admin/promotions/store' => [
            'controller' => 'Admin\\PromotionAdminController',
            'action'     => 'store',
        ],
        '/admin/promotions/update' => [
            'controller' => 'Admin\\PromotionAdminController',
            'action'     => 'update',
        ],
        '/admin/promotions/delete' => [
            'controller' => 'Admin\\PromotionAdminController',
            'action'     => 'delete',
        ],

        // Aliases por compatibilidad
        '/admin/promotions/create' => [
            'controller' => 'Admin\\PromotionAdminController',
            'action'     => 'store',
        ],
        '/admin/promotions/edit' => [
            'controller' => 'Admin\\PromotionAdminController',
            'action'     => 'update',
        ],

        // ============================
        // ADMIN · PAÍSES
        // ============================
        '/admin/countries/store' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'store',
        ],
        '/admin/countries/update' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'update',
        ],
        '/admin/countries/delete' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'delete',
        ],

        // Aliases por compatibilidad
        '/admin/countries/create' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'store',
        ],
        '/admin/countries/edit' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'update',
        ],

        // ============================
        // ADMIN · CLUBES / EQUIPOS
        // ============================
        '/admin/clubs/store' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'store',
        ],
        '/admin/clubs/update' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'update',
        ],
        '/admin/clubs/delete' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'delete',
        ],

        // Aliases por compatibilidad
        '/admin/clubs/create' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'store',
        ],
        '/admin/clubs/edit' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'update',
        ],

        // ============================
        // ADMIN · CONFIGURACIÓN
        // ============================
        '/admin/settings/update' => [
            'controller' => 'Admin\\SettingsAdminController',
            'action'     => 'update',
        ],
        '/admin/settings' => [
            'controller' => 'Admin\\SettingsAdminController',
            'action'     => 'update',
        ],

        // ============================
        // ADMIN · REGLAMENTO
        // ============================
        '/admin/regulations/update' => [
            'controller' => 'Admin\\RegulationAdminController',
            'action'     => 'update',
        ],
        '/admin/regulations' => [
            'controller' => 'Admin\\RegulationAdminController',
            'action'     => 'update',
        ],

        // ============================
        // ADMIN · TESTIMONIOS
        // ============================
        '/admin/testimonials/store' => [
            'controller' => 'Admin\\TestimonialAdminController',
            'action'     => 'store',
        ],
        '/admin/testimonials/update' => [
            'controller' => 'Admin\\TestimonialAdminController',
            'action'     => 'update',
        ],
        '/admin/testimonials/delete' => [
            'controller' => 'Admin\\TestimonialAdminController',
            'action'     => 'delete',
        ],

        // Aliases por compatibilidad
        '/admin/testimonials/create' => [
            'controller' => 'Admin\\TestimonialAdminController',
            'action'     => 'store',
        ],
        '/admin/testimonials/edit' => [
            'controller' => 'Admin\\TestimonialAdminController',
            'action'     => 'update',
        ],
    ],
];
