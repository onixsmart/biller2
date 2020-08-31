<?php

namespace App\Http\Controllers;

use App\ResumenesDiarios;
use Illuminate\Http\Request;
use Datatables;
use Redirect,Response,DB,Config;
use App\Product;

use App\UtilFactura;
//use DB;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;
use Greenter\Ws\Services\SunatEndpoints;

use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;

use Greenter\Model\Sale\Document;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryDetail;
use Greenter\Model\Summary\SummaryPerception;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;



class ResumenPagosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('resumen.resumenes');
    }
    public function getResumen(){
        $business_id = request()->session()->get('user.business_id');
        // $resumenes =  ResumenesDiarios::all();
        // $resumenes =  ResumenesDiarios::where('business_id', $business_id)->get();
        $resumenes = \DB::table("resumen_diario")
                ->where('business_id', $business_id)
                ->get();
        // $resumenes = DB::table('resumen_diario')->select('*');
        return Datatables::of($resumenes)
         ->editColumn('estado_sunat',function($row){
                        if($row->estado_sunat === 0){
                            $html1 =  '<span class="label" style="background-color: #dc3545;"> '. __("Anulado") .' </span>';
                        }
                        else if($row->estado_sunat === 1){
                            $html1 =  '<span class="label" style="background-color: #ffc107;" > '. __("En proceso") .'</span>';
                        }
                        else if($row->estado_sunat === 2){
                            $html1 =  '<span class="label" style="background-color: #6c757d;" > '. __("Rechazado") .'</span>';
                        }
                        else if($row->estado_sunat === 3){
                            $html1 =  '<span class="label" style="background-color: #17a2b8;" > '. __("Con observaciones") .'</span>';
                        }
                        else if($row->estado_sunat === 4){
                            $html1 = '<span class="label" style="background-color: #28a745">Aceptado</span>';
                        }
                        else {
                            $html1 =  '<span class="label">Error</span>';
                        }
                        return $html1;
                    })
            
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                data-toggle="dropdown" aria-expanded="false">' .
                                __("messages.actions") .
                                '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">' ;
                                $html .= '<li><a href="#" data-href="' . action("ResumenPagosController@show", [$row->id]) . '" class="btn-modal" data-container=".view_modal_resumen"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                $html .= '</ul></div>';
                return $html;
            })
            ->rawColumns(['estado_sunat', 'action'])
            ->make(true);
        
    }
    public function create()
    {
        //
        // echo  ("se va a crear");
        return view('resumen.create');
        // return view('resumen.create');
            // ->with(compact('types', 'customer_groups'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
         $output = [];
         // $dato = $request->only(['fecha']);
         $fecha = $request->fecha;
         try
         {
            $util = UtilFactura::getInstance();
   
            $fecha = date('Y-m-d', strtotime($fecha));//cambio de formato fecha
            //consultamos los datos de la empresa y cliente
            $business_id = request()->session()->get('user.business_id');
            $empresa = DB::table('business')->where('id', $business_id)->first();
            $ubicacion_empresa = DB::table('business_locations')->where('business_id', $business_id)->first();
            
            //date_default_timezone_set('America/Lima');
            $fecha_resumen=date("Y-m-d");
   
            $listado = DB::table('transactions')
               ->where('transactions.business_id', $business_id)
               ->where('transactions.transaction_date', 'like', $fecha.'%')
               ->where('transactions.invoice_no', 'like', 'B%')
               ->whereIn('transactions.estado_sunat', [1, 3])
               ->join('contacts', 'contacts.id', '=', 'transactions.contact_id')
               ->select('transactions.id','transactions.estado_sunat','transactions.invoice_no','transactions.total_before_tax','transactions.tax_amount','transactions.final_total','transactions.discount_amount', 'contacts.contact_id')
               ->get();
            
            $i=1;
            //print_r($listado);
            $detalle_resumen=[];
            if (count($listado)>=1) {
              foreach ($listado as $detalle) {
                 $boleta_serie=$detalle->invoice_no;
                 $doc_cliente=$detalle->contact_id;
                 $estado=$detalle->estado_sunat;
                 $idscheme="1";
                 if(strlen($doc_cliente)<8){
                    $doc_cliente="-";
                    $idscheme="-";
                 }elseif (strlen($doc_cliente)==11) {
                    $idscheme="6";
                 }
                 ${"detail".$i} = new SummaryDetail();
                 ${"detail".$i}->setTipoDoc('03')
                    ->setSerieNro($boleta_serie)
                    ->setEstado($estado)
                    ->setClienteTipo($idscheme)
                    ->setClienteNro($doc_cliente)
                    ->setTotal($detalle->final_total)
                    ->setMtoOperGravadas($detalle->total_before_tax)
                    ->setMtoIGV($detalle->tax_amount);
                    /*if ($column['exento']>0) {
                          ${"detail".$i}->setMtoOperInafectas($column['exento']);
                    }
                    if ($column['retenido']>0) {
                          ${"detail".$i}->setMtoOperExoneradas($column['retenido']);
                    }*/
                 $detalle_resumen[]= ${"detail".$i};    
                 $i++;
              }
              
              //Definimos datos de la empresa
              $company = new Company();
              $company->setRuc($empresa->doc_empresa)
                 ->setNombreComercial($empresa->name)
                 ->setRazonSocial($empresa->name)
                 ->setAddress((new Address())
                    ->setUbigueo($ubicacion_empresa->zip_code)
                    ->setDistrito($ubicacion_empresa->district)
                    ->setProvincia($ubicacion_empresa->state)
                    ->setDepartamento($ubicacion_empresa->city)
                    ->setUrbanizacion('')
                    ->setCodLocal('0000')
                    ->setDireccion($ubicacion_empresa->name));
  
              //generamos el correlativo
              $cuantos = DB::table('resumen_diario')->where('business_id', $business_id)->where('fecha_emision','like',$fecha_resumen.'%')->count();
              $correlativo=sprintf("%03d",($cuantos+1));
           
              //se genera el resumen
              $sum = new Summary();
              $sum->setFecGeneracion(new \DateTime($fecha))
                 ->setFecResumen(new \DateTime($fecha_resumen))
                 ->setCorrelativo($correlativo)
                 ->setCompany($company)
                 ->setDetails($detalle_resumen);
  
              //EMITIR RESUMEN A SUNAT
              $see = $util->getSee(SunatEndpoints::FE_BETA);
  
              $res = $see->send($sum);
              $util->writeXml($sum, $see->getFactory()->getLastXml());
              $nombre_resumen = $sum->getName();
  
              //Begin transaction
              DB::beginTransaction();
  
              if ($res->isSuccess()) {
                 $fecha_actual=date("Y-m-d H:i:s");
                 DB::table('resumen_diario')->insert([
                    ['nombre' => $nombre_resumen, 'fecha_emision' => $fecha_actual, 'fecha_resumen' => $fecha, 'estado_sunat' => 4, 'business_id' => $business_id]
                 ]);
                 foreach ($listado as $detalle) {
                     //Cambiar estado de boleta
                    $id_boleta=$detalle->id;
                    if ($estado==1) {
                       $this->cambiar_estado_venta($id_boleta,'4'); 
                    }else{
                       $this->cambiar_estado_venta($id_boleta,'0');
                    }
                 }
                 $ticket = $res->getTicket();
                 //echo 'Ticket :<strong>' . $ticket .'</strong>';
  
                 $res = $see->getStatus($ticket);
                 
                 $cdr = $res->getCdrResponse();
                 $util->writeCdr($sum, $res->getCdrZip());
                 $mensaje=$res->getCdrResponse()->getDescription();
               }else{
                  $mensaje="Algo salió mal, el resumen recibe observaciones. Por favor comunicate con el equipo de soporte.";
               }
               DB::commit();
               $output = ['success' => 1,
               'msg' => $mensaje];
            }else{
               $mensaje="No hay boletas en la fecha seleccionada";
               $output = ['success' => 0,
               'msg' => $mensaje];
           }
  
        } catch (Exception $e) {
           DB::rollBack();
           $mensaje = 'Error de Ejecucion, verifique su conexión';
           $output = ['success' => 0,
           'msg' => $mensaje];      
        }
        // return $output;
        return redirect()->back()->with(['status' => $output]);
     }
   
    public function generar_resumen($fecha)
    {
      
        $output = ['success' => true,
            'msg' =>"Dato recibido: ".$fecha
        ];
        return $output;
    }

  
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $resumen_user = \DB::table("resumen_diario")
                ->select("*")
                ->where('business_id', $business_id)
                ->where('id', $id)
                ->first();
         $resumen_listado = DB::table('transactions')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.updated_at', 'like', substr($resumen_user->fecha_emision, 0, -2).'%')
                ->where('transactions.invoice_no', 'like', 'B%')
                ->whereIn('transactions.estado_sunat', [0, 4])
                ->join('contacts', 'contacts.id', '=', 'transactions.contact_id')
                ->select('transactions.id','transactions.estado_sunat','transactions.invoice_no','transactions.total_before_tax','transactions.tax_amount','transactions.final_total','transactions.discount_amount', 'contacts.contact_id')
                ->get();
         
        // return view("resumen.index", compact('resumenes'));
        return view('resumen.show',compact('resumen_user','resumen_listado'));
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
    public function cambiar_estado_venta($idventa, $idestado)
   {        
      try
      {
         $fecha_actual=date("Y-m-d H:i:s");
         //$fecha_actual="2019-09-27 14:25:02";
         DB::table('transactions')
            ->where('id', $idventa)
            ->update(['estado_sunat' => $idestado, 'updated_at' => $fecha_actual]);

      } catch (Exception $e) {
         echo "no se realizó el cambio de estado";
      }
   }
}