<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstadoSunat extends Model
{
    
    protected $table = 'estado_sunat';
    protected $fillable = ['business_id', 'ruc', 'user_sol', 'pass_sol', 'certificado_file', 'state_sunat'];
    
    public static function create_estado_sunat($details)
    {
        $estado_sunat = EstadoSunat::create([
            'business_id' => $details['business_id'],
            'ruc' => $details['ruc'],
            'user_sol' => $details['user_sol'],
            'pass_sol' => bcrypt($details['pass_sol']),
            'certificado_file' => $details['certificado_file'],
            'state_sunat' => $details['state_sunat'],
        ]);
        return $estado_sunat;
    }
    
}