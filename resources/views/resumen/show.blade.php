<div class="modal-dialog" role="document">
    <div class="modal-content">
    @php
        $form_id = 'resumen_add_form_view';
        
    @endphp
        
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">Resumen de {{ $resumen_user->nombre }}</h4>
        </div>
        <div class="modal-body">
            <div class="row" style="margin-top: 5px">
                <div class="col-md-5">    
                    Fecha de emisión: 
                </div>    
                <div class="col-md-5">
                    {{ $resumen_user->fecha_emision }}
                </div>
                
                <div class="col-md-5">    
                    Fecha de resumen: 
                </div>    
                <div class="col-md-5">
                    {{ $resumen_user->fecha_resumen }}
                </div>
                
                <div class="col-md-5">    
                    Estado de sunat: 
                </div>    
                <div class="col-md-5">
                    {{ $resumen_user->estado_sunat }}
                </div>
            </div>
            <div class="row" style="margin-top: 5px">
            <table class="table">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Comprobante - Cliente</th>
                        <th>Subtotal</th>
                        <th>Impuesto</th>
                        <th>Dscto</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen_listado as $detalle)
                    <tr>
                        <td>{{$detalle->id}}</td>
                        <td>{{$detalle->invoice_no}} - {{$detalle->contact_id}}</td>
                        <td><span class="display_currency" data-currency_symbol="true">{{$detalle->total_before_tax}}</span></td>
                        <td><span class="display_currency" data-currency_symbol="true">{{$detalle->tax_amount}}</span></td>
                        <td><span class="display_currency" data-currency_symbol="true">{{$detalle->discount_amount}}</span></td>
                        <td><span class="display_currency" data-currency_symbol="true">{{$detalle->final_total}}</span></td>
                        <td>{{$detalle->estado_sunat}}</td>
                    </tr>
                    @endforeach
                </tbody>
                
            </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

    
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->