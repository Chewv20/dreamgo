<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Helpers\Validator;
use Core\Auth;
use Core\Controller;
use Core\Paginator;

abstract class AdminController extends Controller
{
    protected function view(string $view, array $data = [], array $meta = [], string $layout = 'admin'): void
    {
        $data['adminNombre'] = Auth::nombre();
        parent::view($view, $data, $meta, $layout);
    }

    /**
     * Arma el Paginator a partir del query string (?pagina=) y el total de registros.
     * Evita repetir `new Paginator(Paginator::paginaDesde($this->request), $porPagina,
     * $total)` en cada controller con listado paginado.
     */
    protected function paginar(int $total, int $porPagina = 20): Paginator
    {
        return new Paginator(Paginator::paginaDesde($this->request), $porPagina, $total);
    }

    /**
     * Busca un registro por id o aborta con 404. Evita repetir
     * `$x = Modelo::find($id); if (!$x) { $this->abort(404); }` en cada controller.
     *
     * @param class-string<\Core\Model> $modelo
     */
    protected function encontrarO404(string $modelo, int|string $id): array
    {
        $registro = $modelo::find($id);
        if ($registro === false) {
            $this->abort(404);
        }

        return $registro;
    }

    protected function redirigirSiInvalido(Validator $validator, string $ruta): void
    {
        if (!$validator->pasa()) {
            Flash::set('error', 'Revisa los datos del formulario.');
            $this->redirect($ruta);
        }
    }
}
