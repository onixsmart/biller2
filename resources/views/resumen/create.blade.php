<div class="modal-dialog" role="document">
    <div class="modal-content">
    @php
        $form_id = 'resumen_add_form';
        
    @endphp
        {!! Form::open(['url' => action('ResumenPagosController@store'), 'method' => 'post']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">Realizando resumen diario(Boletas electrónicas)</h4>
        </div>

        <div class="modal-body">
            <div class="row">
            <!-- <div class="col-md-3">
            
            </div>     -->
                <div class="col-md-6" align ="center">   
                    Seleccionar Fecha:</br>
                    <!-- <input type="date" value="<?php echo date("Y-m-d"); ?>"> -->
                    {!! Form::date('fecha', date("Y-m-d"), ['class' => 'form-control','placeholder' => 'Fecha', 'required']); !!}
                </div>

                <div class="col-md-6">
                Nota: Todas las boletas generadas en la fecha seleccionada, serán enviadas a la SUNAT en este resumen diario.
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}
    
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->