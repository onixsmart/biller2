@extends('layouts.app')
@section('title', __( 'sale.list_pos'))
@section('content')
<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>Resumen diario
    </h1>
</section>
<section class="content no-print">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Todos los resumenes')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal" 
                data-href="{{action('ResumenPagosController@create')}}" data-container=".resumen_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>
            </div>
        @endslot
         <div class="container">
               <!-- <h2>Lista de resumenes diarios: </h2> -->
            <table class="table table-bordered" id="laravel_datatable">
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
            </table>
         </div>
         @endcomponent
        <div class="modal fade resumen_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"> </div>
        <div class="modal fade view_modal_resumen" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"> </div>

</section>
@stop
@section('javascript')
   <script>
   $(document).ready( function () {
    $('#laravel_datatable').DataTable({
           order: [[ 0, "desc" ]],
           processing: true,
           serverSide: true,
           ajax: "{{ url('resumen-pagos') }}",
           columns: [
                    { data: 'id', name: 'id' },
                    { data: 'nombre', name: 'nombre' },
                    { data: 'fecha_emision', name: 'fecha_emision'},
                    { data: 'fecha_resumen', name: 'fecha_resumen'},
                    { data: 'estado_sunat', name: 'estado_sunat'},
                    { data: 'action', name: 'action'},
                 ]
        });
     });
     
  </script>
  @endsection
