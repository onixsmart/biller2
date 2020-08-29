<?php

namespace App\Http\Controllers;
use App\EstadoSunat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use File;
class SunatController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $business_sunat = EstadoSunat::where('business_id', '=', $business_id)->first();

        if (empty($business_sunat)) {
            $business_sunat_t= new EstadoSunat;//then create new object
            $business_sunat_t['business_id'] = $business_id;
            $business_sunat_t['ruc'] = "";
            $business_sunat_t['user_sol'] = "";
            $business_sunat_t['pass_sol'] = "";
            $business_sunat_t['certificado_file'] = "";
            $business_sunat_t['state_sunat'] = "";
            $business_sunat_t->save();
            $business_sunat = EstadoSunat::where('business_id', '=', $business_id)->first();
                
        }
        return view('sunat.index', compact(
            'business_sunat'
        ));
       
    
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $output = [];
        if (!auth()->user()->can('business_settings.access')) {
                abort(403, 'Unauthorized action.');
        }
        try {
            $request->validate([
                'business_id' => '',
                'ruc' => '',
                'user_sol' => '',
                'pass_sol' => '',
                'file' => '',
                'state_sunat' => ''
            ]);
            $business_id_sunat = request()->session()->get('user.business_id');   
            $sunat_modify = EstadoSunat::where('business_id', $business_id_sunat)->first();
            $sunat_details =  $request->only(['business_id', 'ruc', 'user_sol', 'pass_sol', 'state_sunat']);

            //Actualiza campo ruc en table business
            DB::table('business')
            ->where('id', $business_id_sunat)
            ->update(['doc_empresa' => $sunat_details['ruc']]);
            \Debugbar::info('business id: '.$business_id_sunat.' RUC: '.$sunat_details['ruc']);

            if($request->state_sunat === null){
                $sunat_details['state_sunat'] = false;
            }
            else if($request->state_sunat != null){
                $sunat_details['state_sunat'] = true;
            }
            $filename = (string)$sunat_details['ruc'].".pem";
            if($request->hasFile('file')){
                $file = $request->file('file');
                $disk = Storage::disk('sunat_files')->putFileAs('utilfactura',$request->file , $filename);
                $sunat_details['certificado_file'] = $disk;
            }
            $sunat_modify->fill($sunat_details);
            $sunat_modify->save();
            
            $output = ['success' => 1,
                'msg' => __('Datos de Empresa registrados correctamente')
            ];
        }
            catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = ['success' => 0,
                                'msg' => __('messages.something_went_wrong')
                            ];
            }
            $business_sunat =  $sunat_details;
            
            //  return view('sunat.indexfile', compact(
            // 'business_sunat'
        // ));
        return redirect('sunat')->with('status', $output);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
