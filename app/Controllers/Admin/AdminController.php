<?php

namespace App\Controllers\Admin;

use Core\Auth;
use Core\Controller;

abstract class AdminController extends Controller
{
    protected function view(string $view, array $data = [], array $meta = [], string $layout = 'admin'): void
    {
        $data['adminNombre'] = Auth::nombre();
        parent::view($view, $data, $meta, $layout);
    }
}
