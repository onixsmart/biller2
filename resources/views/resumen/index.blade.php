@extends('layouts.app')
@section('title', __( 'Resumen diario'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>Resumen Diario
    <small>Boletas electrónicas</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Todos los resumenes')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal" 
                data-href="{{action('ResumenPagosController@create')}}" data-container=".resumen_modal">
                <i class="fa fa-plus"></i> Crear Resumen</button>
            </div>
        @endslot

            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="resumen_table">
                    <thead>
                        <tr>
                            <th>id</th>
                            <th>Nombre</th>
                            <th>Fecha Emisión</th>
                            <th>Fecha Resumen</th>
                            <th>Estado sunat</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    @foreach($resumenes as $resumen)
                    <!--<tbody>
                    <td>{{ $resumen->id }}</td>
                    <td>{{ $resumen->nombre }}</td>
                    <td>{{ $resumen->fecha_emision }}</td>
                    <td>{{ $resumen->fecha_resumen }}</td>
                    <td>{{ $resumen->estado_sunat }}</td>
                    <td>


                        <div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                data-toggle="dropdown" aria-expanded="false">
                                @lang("messages.actions")
                                <span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                            <li><a href="#" class="send-facture" data-href="' . route('ResumenPagosController.enviar', [$row->id]) . '"><i class="fa fa-send" aria-hidden="true"></i> Enviar</a></li>
                            </ul>
                        </div>
                    </td>
                    </tbody>
                    @endforeach
                    <!-- <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td id="footer_payment_status_count"></td>
                            <td><span class="display_currency" id="footer_sale_total" data-currency_symbol ="true"></span></td>
                            <td><span class="display_currency" id="footer_total_paid" data-currency_symbol ="true"></span></td>
                            <td class="text-left"><small>@lang('lang_v1.sell_due') - <span class="display_currency" id="footer_total_remaining" data-currency_symbol ="true"></span><br>@lang('lang_v1.sell_return_due') - <span class="display_currency" id="footer_total_sell_return_due" data-currency_symbol ="true"></span></small></td>
                            <td></td>
                        </tr>
                    </tfoot> -->
                </table>
            </div>
            @endcomponent
        <div class="modal fade resumen_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel"></div>

</section>

@endsection