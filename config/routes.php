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
        '/api/quiniela/current' => [
            'controller' => 'Api\\QuinielaController',
            'action'     => 'current',
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
        // ADMIN · JORNADAS (ROUNDS)
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
        // ADMIN · PARTIDOS (MATCHES)
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

        // ============================
        // ADMIN · TICKETS
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
        // CORREGIDO: Se quitó el /{id}
        '/admin/countries/edit' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'edit',
        ],

        // ============================
        // ADMIN · CLUBES
        // ============================
        '/admin/clubs' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'index',
        ],
        '/admin/clubs/create' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'create',
        ],
        // CORREGIDO: Se quitó el /{id}
        '/admin/clubs/edit' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'edit',
        ],
        '/admin/clubs/search' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'searchAjax',
        ],

        // ============================
        // ADMIN · HISTORIAL
        // ============================
        '/admin/history' => [
            'controller' => 'Admin\\RoundHistoryController',
            'action'     => 'index',
        ],
        
        '/admin/regulations' => [
            'controller' => 'Admin\\RegulationAdminController',
            'action'     => 'index',
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

        // ============================
        // ADMIN · JORNADAS
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
        
        '/admin/rounds/matches/create' => [
        'controller' => 'Admin\\RoundMatchesAdminController', // Asegúrate que este nombre coincida con tu archivo real
        'action'     => 'create',
    ],
    '/admin/rounds/matches/edit' => [
        'controller' => 'Admin\\RoundMatchesAdminController',
        'action'     => 'edit',
    ],
    // --- AGREGAR ESTO ---
    '/admin/rounds/matches/delete' => [
        'controller' => 'Admin\\RoundMatchesAdminController', // O MatchAdminController si usas ese
        'action'     => 'delete',
    ],

        // ============================
        // ADMIN · TICKETS
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
        
        '/admin/regulations/update' => [
            'controller' => 'Admin\\RegulationAdminController',
            'action'     => 'update',
        ],

        // ============================
        // ADMIN · PAÍSES (Faltaba Create y Edit)
        // ============================
        '/admin/countries/create' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'create',
        ],
        '/admin/countries/edit' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'edit',
        ],
        '/admin/countries/delete' => [
            'controller' => 'Admin\\CountryAdminController',
            'action'     => 'delete',
        ],

        // ============================
        // ADMIN · CLUBES (Faltaba Create y Edit)
        // ============================
        '/admin/clubs/create' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'create',
        ],
        '/admin/clubs/edit' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'edit',
        ],
        '/admin/clubs/delete' => [
            'controller' => 'Admin\\ClubAdminController',
            'action'     => 'delete',
        ],
    ],
];