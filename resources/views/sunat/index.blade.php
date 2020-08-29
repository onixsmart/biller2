@extends('layouts.app')
@section('title', __( 'SUNAT'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>Certificado digital
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Configuración de Clave SOL')])
       

{!! Form::open(['url' => route('sunat.store'), 'method' => 'post','enctype'=>'multipart/form-data']) !!}

        <div class="pos-tab-content">
            <div class="row">
            
                <div class="col-sm-7">
                    <div class="form-group">
                        {{ Form::label("RUC de la empresa: ", null) }}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('ruc', $business_sunat->ruc, ['class' => 'form-control','placeholder' => 'RUC']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {{ Form::label("Usuario clave sol: ", null) }}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('user_sol', $business_sunat->user_sol, ['class' => 'form-control','placeholder' => 'Usuario sol']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                    {{ Form::label("Contraseña clave sol: ", null) }}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('pass_sol', $business_sunat->pass_sol, ['class' => 'form-control','placeholder' => 'Password SOL']); !!}
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                
                <div class="col-sm-4">
                    <div class="form-group">
                            {{ Form::label("Cargar certificado: ", null) }}
                        <span class="input-group-addon">
                            <i class="fa fa-file-invoice"></i>
                        </span>
                          {{ Form::file('file') }}
                         
                    </div>
                </div>
                <div class="col-sm-8">
                    <div class="form-group">
                        <div class="checkbox">
                        <br>
                        <label>
                        {{ Form::checkbox('state_sunat', $business_sunat->state_sunat, $business_sunat->state_sunat, array('disabled')) }}{{ __( 'Utilizar certificado de Biller') }}
                        </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <button class="btn btn-info pull-right" type="submit">Guardar Cambios</button>
                </div>
            </div>
        </div>
{!! Form::close() !!}

        @endcomponent
        <div class="modal fade resumen_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

</section>

@endsection