<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
// 👇 ESTA LÍNEA ES VITAL. SIN ELLA, ERROR 500.
use App\Models\RegulationModel; 

class HomeController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $countryCode  = (string) Session::get('geo.country_code', 'US');
        $countryName  = (string) Session::get('geo.country_name', 'United States');
        $currencyCode = (string) Session::get('geo.currency_code', $countryCode === 'MX' ? 'MXN' : 'USD');

        if ($currencyCode === 'MXN') {
            $ticketCost = 200.00;
        } else {
            $ticketCost = 10.00;
        }

        $symbol = '$';
        $ticketCostLabel = $symbol . number_format($ticketCost, 2) . ' ' . $currencyCode;

        $this->render('home/index', [
            'pageTitle'       => 'Villa Quiniela - Quiniela Liga MX y Champions',
            'geoCountryCode'  => $countryCode,
            'geoCountryName'  => $countryName,
            'geoCurrencyCode' => $currencyCode,
            'ticketCost'      => $ticketCost,
            'ticketCostLabel' => $ticketCostLabel,
        ]);
    }

    /**
     * Mostrar la página de reglamento.
     */
public function rules(Request $request, Response $response): void
{
    $model = new RegulationModel();
    $reglamento = $model->getCurrent();
    
    // Si no hay reglas, mostramos un texto por defecto
    $texto = $reglamento ? $reglamento['content'] : '<p>Reglamento pendiente de publicación.</p>';

    $this->render('home/rules', [
        'pageTitle' => 'Reglamento',
        'rulesContent' => $texto // <--- Esta variable es la que usa tu vista home/rules.php
    ]);
}

}