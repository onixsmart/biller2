
{!! Form::hidden('language', request()->lang); !!}

<fieldset>
<br/>
<div class="col-md-8">
    <div class="form-group">
        {!! Form::label('name', __('business.business_name') . ':' ) !!}
        <div class="input-group col-md-12">
            <span class="fa fa-suitcase">
            </span>
            {!! Form::text('name', null, ['placeholder' => __('business.business_name'), 'required']); !!}
        </div>
    </div>
</div>
        

<div class="col-md-4">
    <div class="form-group">
    {!! Form::label('mobile', __('lang_v1.business_telephone') . ':') !!}
    <div class="input-group col-md-12">
        <span class="fa fa-phone">
        </span>
        {!! Form::text('mobile', null, ['placeholder' => __('lang_v1.business_telephone'), 'required']); !!}
    </div>
    </div>
</div>


<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('first_name', __('business.first_name') . ':') !!}
        <div class="input-group col-md-12">
            <span class="fa fa-info">
            </span>
            {!! Form::text('first_name', null, ['placeholder' => __('business.first_name'), 'required']); !!}
        </div>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('last_name', __('business.last_name') . ':') !!}
        <div class="input-group col-md-12">
            <span class="fa fa-info">
            </span>
            {!! Form::text('last_name', null, ['placeholder' =>  __('business.last_name')]); !!}
        </div>
    </div>
</div>
<div class="clearfix"></div>
<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('username', __('business.username') . ':') !!}
        <div class="input-group col-md-12">
            <span class="fa fa-user">
            </span>
            {!! Form::text('username', null, ['placeholder' => __('business.username'), 'required']); !!}
        </div>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('email', __('business.email') . ':') !!}
        <div class="input-group col-md-12">
            <span class="fa fa-envelope">
            </span>
            {!! Form::text('email', null, ['placeholder' => __('business.email')]); !!}
        </div>
    </div>
</div>
<div class="clearfix"></div>
<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('password', __('business.password') . ':') !!}
        <div class="input-group col-md-12">
            <span class="fa fa-lock">
            </span>
            {!! Form::password('password', ['placeholder' => __('business.password'), 'required']); !!}
        </div>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('confirm_password', __('business.confirm_password') . ':') !!}
        <div class="input-group col-md-12">
            <span class="fa fa-lock">
            </span>
            {!! Form::password('confirm_password', ['placeholder' => __('business.confirm_password'), 'required']); !!}
        </div>
    </div>
</div>
<div class="clearfix"></div>
<div class="col-md-6">
    @if(!empty($system_settings['superadmin_enable_register_tc']))
        <div class="checkbox">
            {!! Form::checkbox('accept_tc', 0, false, ['required']); !!}
            <label>
                <a class="terms_condition" data-toggle="modal" data-target="#tc_modal">
                    @lang('lang_v1.accept_terms_and_conditions')
                </a>
            </label>
        </div>
        @include('business.partials.terms_conditions')
    @endif
</div>

<div class="clearfix"></div>
<div class="col-md-12"><input class="btn btn-danger btn-block btn-login" type="submit" value="Registrar"></div>
</fieldset>