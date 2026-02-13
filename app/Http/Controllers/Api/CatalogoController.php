<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\UnidadMedida;
use App\Models\Tributo;
use App\Models\TributoCliente;
use App\Models\TipoDocumentoIdentidad;
use App\Models\TipoOrganizacion;
use App\Models\MetodoPago;
use App\Models\FormaPago;
use App\Models\CodigoEstandar;
use App\Models\TipoDocumentoFactura;
use App\Models\TipoOperacion;
use App\Models\RangoNumeracion;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    /**
     * Obtener municipios
     */
    public function municipios(Request $request)
    {
        $query = Municipio::query();

        if ($request->filled('departamento')) {
            $query->porDepartamento($request->departamento);
        }

        if ($request->filled('search')) {
            $query->buscar($request->search);
        }

        $municipios = $query->orderBy('nombre')->get();

        return response()->json($municipios);
    }

    /**
     * Obtener departamentos únicos
     */
    public function departamentos()
    {
        $departamentos = Municipio::select('departamento')
            ->distinct()
            ->orderBy('departamento')
            ->pluck('departamento');

        return response()->json($departamentos);
    }

    /**
     * Obtener unidades de medida
     */
    public function unidadesMedida(Request $request)
    {
        $query = UnidadMedida::query();

        if ($request->filled('search')) {
            $query->buscar($request->search);
        }

        $unidades = $query->orderBy('nombre')->get();

        return response()->json($unidades);
    }

    /**
     * Obtener tributos (productos)
     */
    public function tributos(Request $request)
    {
        $query = Tributo::query();

        if ($request->filled('search')) {
            $query->buscar($request->search);
        }

        $tributos = $query->orderBy('nombre')->get();

        return response()->json($tributos);
    }

    /**
     * Obtener tributos de clientes
     */
    public function tributosCliente()
    {
        $tributos = TributoCliente::orderBy('nombre')->get();

        return response()->json($tributos);
    }

    /**
     * Obtener tipos de documento de identidad
     */
    public function tiposDocumentoIdentidad()
    {
        $tipos = TipoDocumentoIdentidad::orderBy('nombre')->get();

        return response()->json($tipos);
    }

    /**
     * Obtener tipos de organización
     */
    public function tiposOrganizacion()
    {
        $tipos = TipoOrganizacion::all();

        return response()->json($tipos);
    }

    /**
     * Obtener métodos de pago
     */
    public function metodosPago()
    {
        $metodos = MetodoPago::orderBy('nombre')->get();

        return response()->json($metodos);
    }

    /**
     * Obtener formas de pago
     */
    public function formasPago()
    {
        $formas = FormaPago::all();

        return response()->json($formas);
    }

    /**
     * Obtener códigos estándar
     */
    public function codigosEstandar()
    {
        $codigos = CodigoEstandar::all();

        return response()->json($codigos);
    }

    /**
     * Obtener tipos de documento para factura
     */
    public function tiposDocumentoFactura()
    {
        $tipos = TipoDocumentoFactura::all();

        return response()->json($tipos);
    }

    /**
     * Obtener tipos de operación
     */
    public function tiposOperacion()
    {
        $tipos = TipoOperacion::all();

        return response()->json($tipos);
    }

    /**
     * Obtener rangos de numeración activos
     */
    public function rangosNumeracion()
    {
        $rangos = RangoNumeracion::disponibles()
            ->orderBy('document')
            ->get();

        return response()->json($rangos);
    }

    /**
     * Obtener categorías de productos
     */
    public function categorias()
    {
        $categorias = Categoria::activas()
            ->orderBy('nombre')
            ->get();

        return response()->json($categorias);
    }

    /**
     * Obtener todos los catálogos de una vez
     */
    public function todos()
    {
        return response()->json([
            'tipos_documento_identidad' => TipoDocumentoIdentidad::all(),
            'tipos_organizacion' => TipoOrganizacion::all(),
            'tributos_cliente' => TributoCliente::all(),
            'metodos_pago' => MetodoPago::all(),
            'formas_pago' => FormaPago::all(),
            'codigos_estandar' => CodigoEstandar::all(),
            'tipos_documento_factura' => TipoDocumentoFactura::all(),
            'tipos_operacion' => TipoOperacion::all(),
            'categorias' => Categoria::activas()->get(),
            'departamentos' => Municipio::select('departamento')
                ->distinct()
                ->orderBy('departamento')
                ->pluck('departamento'),
        ]);
    }
}