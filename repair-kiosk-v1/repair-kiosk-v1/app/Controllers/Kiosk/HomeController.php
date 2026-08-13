<?php
declare(strict_types=1);

namespace App\Controllers\Kiosk;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;

final class HomeController extends Controller
{
    public function redirectToKiosk(Request $request): void
    {
        $this->redirect('/kiosk');
    }

    public function index(Request $request): void
    {
        Session::wizardClear('dropoff');
        Session::wizardClear('collect');
        $this->view('kiosk/home', [
            'workshopName' => config('app.name'),
        ]);
    }
}
