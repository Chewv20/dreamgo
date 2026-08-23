<?php

namespace App\Controllers\Public;

use Core\Controller;

class NosotrosController extends Controller
{
    public function index(): void
    {
        $this->view('public/nosotros/index', [], [
            'title' => 'Nosotros | Dream Go Operadora Turistica',
            'description' => 'Conoce al equipo detras de Dream Go Operadora Turistica.',
        ]);
    }
}
