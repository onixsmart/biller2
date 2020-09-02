<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Account;
use App\AccountTransaction;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\Media;
use App\Product;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\User;

use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;

use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Yajra\DataTables\Facades\DataTables;


use App\UtilFactura;
//use DB;
use Greenter\See;
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

class FacturaSunatController extends Controller
{
   protected $contactUtil;
   protected $productUtil;
   protected $businessUtil;
   protected $transactionUtil;
   protected $cashRegisterUtil;
   protected $moduleUtil;
   protected $notificationUtil;

   /**
    * Constructor
    *
    * @param ProductUtils $product
    * @return void
    */
   public function __construct(
      ContactUtil $contactUtil,
      ProductUtil $productUtil,
      BusinessUtil $businessUtil,
      TransactionUtil $transactionUtil,
      CashRegisterUtil $cashRegisterUtil,
      ModuleUtil $moduleUtil,
      NotificationUtil $notificationUtil
   ) {
      $this->contactUtil = $contactUtil;
      $this->productUtil = $productUtil;
      $this->businessUtil = $businessUtil;
      $this->transactionUtil = $transactionUtil;
      $this->cashRegisterUtil = $cashRegisterUtil;
      $this->moduleUtil = $moduleUtil;
      $this->notificationUtil = $notificationUtil;

      $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
      'is_return' => 0, 'transaction_no' => ''];
   }
   /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */

   public function index()
   {
      if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
      }

      $business_id = request()->session()->get('user.business_id');

      $business_locations = BusinessLocation::forDropdown($business_id, false);
      $customers = Contact::customersDropdown($business_id, false);
      
      return view('sale_pos.factura_sunat')->with(compact('business_locations', 'customers'));
   }
   
   /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */

    
   Public function facturaSunat($transaction_id){
    
      $output = [];
      try
      {
         //consultamos los datos de la venta
         $datos = DB::table('transactions')->where('id', $transaction_id)->where('invoice_no','like','F%')->first();
         $business_id = request()->session()->get('user.business_id');
         //consultamos los datos de la empresa y cliente
         $empresa_id= $datos->business_id;
         $empresa = DB::table('business')->where('id', $empresa_id)->first();
         $ubicacion_empresa = DB::table('business_locations')->where('business_id', $datos->location_id)->first();
         $cliente = DB::table('contacts')->where('id', $datos->contact_id)->first();
         $sunat = DB::table('estado_sunat')->where('business_id', $business_id)->first();
        \Debugbar::info($sunat);
         $util = UtilFactura::getInstance();

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

         //Definimos el cliente
         $client = new Client();
         $client->setTipoDoc('6')
            ->setNumDoc($cliente->contact_id)
            ->setRznSocial($cliente->name)
            ->setAddress((new Address())
               ->setDireccion($cliente->landmark));

         $serie=substr($datos->invoice_no,-10,4);
         $dato_comprobante=$datos->invoice_no;
         $numero_comprobante = "";
            for($i = strlen($dato_comprobante)-1;  $i >= 0; $i--){
               if($dato_comprobante[$i] === '-'){break;
               }else{
                  $numero_comprobante = $dato_comprobante[$i].$numero_comprobante;
               }
            }
         $correlativo=$numero_comprobante;
         
         //Creamos la factura
         $invoice = new Invoice();
         $invoice ->setUblVersion('2.1')
            ->setFecVencimiento(new \DateTime())
            ->setTipoOperacion('0101')
            ->setTipoDoc('01') //codigo de factura
            ->setSerie($serie)
            ->setCorrelativo($correlativo)
            ->setFechaEmision(new \DateTime())
            ->setTipoMoneda('PEN')
            ->setClient($client)
            ->setMtoOperGravadas($datos->total_before_tax)
            ->setMtoIGV($datos->tax_amount)
            ->setTotalImpuestos($datos->tax_amount)
            ->setValorVenta($datos->total_before_tax)
            ->setMtoImpVenta($datos->final_total)
            ->setCompany($company);
         
         //consultamos los detalles de la venta
         
         $detalles = DB::table('transaction_sell_lines')->where('transaction_sell_lines.transaction_id', $transaction_id)
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->select('transaction_sell_lines.*', 'products.name')
            ->get();
         
         $i=1;
         //listamos el detalle de la venta
         foreach ($detalles as $detalle){ 
            $cantidad_producto = (int)($detalle->quantity);
            $punitario_sinigv = $detalle->unit_price;
            $total_sinigv = $cantidad_producto*$punitario_sinigv;
            $total_sinigv = round($total_sinigv * 100) / 100;
            $item_igv = ($total_sinigv*1.18)-$total_sinigv;
            $item_igv = round($item_igv * 100) / 100;
            $punitario_conigv = $punitario_sinigv*1.18;
            $punitario_conigv = round($punitario_conigv * 100) / 100;
         
            ${"item".$i} = new SaleDetail();
            ${"item".$i}->setCodProducto('0')
               ->setUnidad('NIU')
               ->setDescripcion($detalle->name)
               ->setCantidad($cantidad_producto)
               ->setMtoValorUnitario($punitario_sinigv)
               ->setMtoValorVenta($total_sinigv)
               ->setMtoBaseIgv($total_sinigv)
               ->setPorcentajeIgv(18)
               ->setIgv($item_igv)
               ->setTipAfeIgv('10')
               ->setTotalImpuestos($item_igv)
               ->setMtoPrecioUnitario($punitario_conigv);

            $detalle_resumen[]= ${"item".$i};  
            $i++;
         }
         $sonletras="SON ".strtoupper($this->num2letras($datos->final_total));
         
         $invoice->setDetails($detalle_resumen)
            ->setLegends([
               (new Legend())
                  ->setCode('1000')
                  ->setValue($sonletras)
            ]);

         //Difinir datos SUNAT
         $datossunat = new See();
         //$see->setService($endpoint);
         
         $datossunat->setCredentials($sunat->ruc.$sunat->user_sol, $sunat->pass_sol);
         $datossunat->setCachePath(__DIR__ . '/../../../cache');

         // Envio a SUNAT.
         if($sunat->state_sunat == 0){
            if($sunat->certificado_file==null){
            $datossunat->setCertificate(file_get_contents(__DIR__.'/../../../resources/utilfactura/cert.pem'));
            }else{
               $datossunat->setCertificate(file_get_contents(__DIR__.'/../../../resources/'.$sunat->certificado_file));
            }
            $datossunat->setService(SunatEndpoints::FE_BETA);
            //$see = $util->getSee(SunatEndpoints::FE_BETA);
            $beta=' (modo prueba)';}
         else{
            $datossunat->setCertificate(file_get_contents(__DIR__.'/../../../resources/'.$sunat->certificado_file));
            $datossunat->setService(SunatEndpoints::FE_PRODUCCION);
            //$see = $util->getSee(SunatEndpoints::FE_PRODUCCION);
            $beta='';
         }

         /** Si solo desea enviar un XML ya generado utilice esta función**/
         $res = $datossunat->send($invoice);
         $util->writeXml($invoice, $datossunat->getFactory()->getLastXml());

         if ($res->isSuccess()) {
            
            $cdr = $res->getCdrResponse();
            $util->writeCdr($invoice, $res->getCdrZip());
            $mensaje=$res->getCdrResponse()->getDescription();

            //Cambiar estado a venta emitida a sunat
            $this->cambiar_estado_venta($transaction_id,'4');

            $output = ['success' => 1,
            'msg' => $mensaje.$beta];
            //echo $mensaje;
         } else {
            $mensaje= $res->getError()->getMessage();
            $this->cambiar_estado_venta($transaction_id,'3');
            $output = ['success' => 0,
            'msg' => $mensaje];
         }
      
      } catch (Exception $e) {
         $mensaje = 'Error en el servicio de SUNAT, intente más tarde.';
         $output = ['success' => 0,
         'msg' => $mensaje
         ];
      }
      return $output;
   }

   public function emitir_resumen($fecha){
      $output = [];
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
            ->where('transactions.invoice_no', 'like', 'B'.'%')
            ->where('transactions.estado_sunat', '=', '1')
            ->orWhere('transactions.estado_sunat', '=', '3')//revisar este id para boletas anuladas
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
      return $output;
   }

   public function baja_comprobante($idventa, $motivo){

      $output = [];
      try
      {
         $util = UtilFactura::getInstance();
         //consultamos los datos de la empresa y cliente
         $business_id = request()->session()->get('user.business_id');
         $empresa = DB::table('business')->where('id', $business_id)->first();
         $ubicacion_empresa = DB::table('business_locations')->where('business_id', $business_id)->first();//falta encontrar la locacion exacta
         
         $eliminacion = $this->destroy($idventa);
         //print_r($eliminacion);
         if($eliminacion["success"]  == true)
         {
            $Comprobante = DB::table('transactions')
            ->where('id', '=', $idventa)
            ->select('invoice_no','transaction_date')
            ->first();
         
            $fecha_resumen=date("Y-m-d");
            $fecha_generado_texto=substr($fecha_resumen, 0, 4).substr($fecha_resumen, 5, 2).substr($fecha_resumen, 8, 2);

            $fecha_baja=date("Y-m-d");
            //$fecha_generado_texto=substr($fecha_baja, 0, 4).substr($fecha_baja, 5, 2).substr($fecha_baja, 8, 2);
            $dato_comprobante = $Comprobante->invoice_no;
            $numero_comprobante = "";
            for($i = strlen($dato_comprobante)-1;  $i >= 0; $i--){
               if($dato_comprobante[$i] === '-'){break;
               }else{
                  $numero_comprobante = $dato_comprobante[$i].$numero_comprobante;
               }
            }

            $tipo_comprobante = substr($Comprobante->invoice_no, 0, 1);
            $fecha_venta = substr($Comprobante->transaction_date,0,-9);
            $serie =substr($Comprobante->invoice_no, 0, 4);
            

            $ruc_empresa=$empresa->doc_empresa;
            //echo $ruc_empresa;
            $file_bajas ='../files/'.$ruc_empresa.'-RA-'.$fecha_generado_texto.'-001.xml';
            $correlativo='001';
            $c = 1;

            while (is_file($file_bajas)=='false'){
               $file_bajas ='../files/'.$ruc_empresa.'-RA-'.$fecha_generado_texto.'-00'.$c.'.xml';
               $correlativo=sprintf("%03d",($c+1));
               $c++;
               //echo "correlativo: ".$correlativo;
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

            //ANULACION DE FACTURA
            if ($tipo_comprobante=="F") {
               $detalle = new VoidedDetail();
               $detalle->setTipoDoc('01')
                  ->setSerie($serie)
                  ->setCorrelativo($numero_comprobante)
                  ->setDesMotivoBaja($motivo);

               $voided = new Voided();
               $voided->setCorrelativo($correlativo)
                  ->setFecGeneracion(new \DateTime($fecha_venta))
                  ->setFecComunicacion(new \DateTime($fecha_baja))
                  ->setCompany($company)
                  ->setDetails([$detalle]);

               // Envio a SUNAT.
               $see = $util->getSee(SunatEndpoints::FE_BETA);

               $res = $see->send($voided);
               $util->writeXml($voided, $see->getFactory()->getLastXml());

               $ticket = $res->getTicket();
               //echo 'Ticket :<strong>' . $ticket .'</strong>';
               $res = $see->getStatus($ticket);
               if (!$res->isSuccess()) {
                  echo $util->getErrorResponse($res->getError());
                  return;
               }
               $cdr = $res->getCdrResponse();
               $util->writeCdr($voided, $res->getCdrZip());

               //RECUPERAMOS EL MENSAJE DE SUNAT
               $mensaje=$res->getCdrResponse()->getDescription();
            }else{
            //ANULACION DE BOLETAS
               $this->cambiar_estado_venta($idventa,'3');
               $mensaje="La anulación de la Boleta Nro. ".$numero_comprobante." ha sido solicitada. Recuerde generar de nuevo el resumen diario con la fecha de la boleta anulada.";
            }
            DB::table('transactions')
            ->where('id', $idventa)
            ->update(['additional_notes' => "Anulado: ".$motivo]);
            $output['success'] = true;
            $output['msg'] = $mensaje;
         }else{
            $mensaje="La anulación no se ha completado. Consulte al soporte de Biller.pe";

            $output['success'] = true;
            $output['msg'] = $mensaje;
         }
         return $output;

      } catch (Exception $e) {
         $mensaje = 'Error de Ejecucion, verifique conexión';
         $output['success'] = true;
         $output['msg'] = $mensaje;
         return $output;
      }
   }


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
      //
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
      if (!auth()->user()->can('sell.delete')) {
         abort(403, 'Unauthorized action.');
      }

      try {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('id', $id)
               ->where('business_id', $business_id)
               ->where('type', 'sell')
               ->with(['sell_lines'])
               ->first();

            //Begin transaction
            DB::beginTransaction();

            if (!empty($transaction)) {
               //If status is draft direct delete transaction
               if ($transaction->status == 'draft') {
                  $transaction->delete();
               } else {
                  $deleted_sell_lines = $transaction->sell_lines;
                  $deleted_sell_lines_ids = $deleted_sell_lines->pluck('id')->toArray();
                  $this->transactionUtil->deleteSellLines(
                        $deleted_sell_lines_ids,
                        $transaction->location_id
                  );

                  $transaction->status = 'draft';
                  $business = ['id' => $business_id,
                           'accounting_method' => request()->session()->get('business.accounting_method'),
                           'location_id' => $transaction->location_id
                        ];

                  $this->transactionUtil->adjustMappingPurchaseSell('final', $transaction, $business, $deleted_sell_lines_ids);

                  $this->cambiar_estado_venta($id,"0");
               }
            }

            AccountTransaction::where('transaction_id', $transaction->id)->delete();

            DB::commit();
            $output = [
               'success' => true,
               'msg' => __('lang_v1.sale_delete_success')
            ];
      } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = trans("messages.something_went_wrong");
      }

      return $output;
        
   }

   public function cambiar_estado_venta($idventa, $idestado)
   {        
      try
      {
         $fecha_actual=date("Y-m-d H:i:s");
         DB::table('transactions')
            ->where('id', $idventa)
            ->update(['estado_sunat' => $idestado, 'updated_at' => $fecha_actual]);

      } catch (Exception $e) {
         echo "no se realizó el cambio de estado";
      }
   }

   function num2letras($num, $fem = false, $dec = true){ 
      $matuni[2]  = "dos"; 
      $matuni[3]  = "tres"; 
      $matuni[4]  = "cuatro"; 
      $matuni[5]  = "cinco"; 
      $matuni[6]  = "seis"; 
      $matuni[7]  = "siete"; 
      $matuni[8]  = "ocho"; 
      $matuni[9]  = "nueve"; 
      $matuni[10] = "diez"; 
      $matuni[11] = "once"; 
      $matuni[12] = "doce"; 
      $matuni[13] = "trece"; 
      $matuni[14] = "catorce"; 
      $matuni[15] = "quince"; 
      $matuni[16] = "dieciseis"; 
      $matuni[17] = "diecisiete"; 
      $matuni[18] = "dieciocho"; 
      $matuni[19] = "diecinueve"; 
      $matuni[20] = "veinte"; 
      $matunisub[2] = "dos"; 
      $matunisub[3] = "tres"; 
      $matunisub[4] = "cuatro"; 
      $matunisub[5] = "quin"; 
      $matunisub[6] = "seis"; 
      $matunisub[7] = "sete"; 
      $matunisub[8] = "ocho"; 
      $matunisub[9] = "nove"; 
   
      $matdec[2] = "veint"; 
      $matdec[3] = "treinta"; 
      $matdec[4] = "cuarenta"; 
      $matdec[5] = "cincuenta"; 
      $matdec[6] = "sesenta"; 
      $matdec[7] = "setenta"; 
      $matdec[8] = "ochenta"; 
      $matdec[9] = "noventa"; 
      $matsub[3]  = 'mill'; 
      $matsub[5]  = 'bill'; 
      $matsub[7]  = 'mill'; 
      $matsub[9]  = 'trill'; 
      $matsub[11] = 'mill'; 
      $matsub[13] = 'bill'; 
      $matsub[15] = 'mill'; 
      $matmil[4]  = 'millones'; 
      $matmil[6]  = 'billones'; 
      $matmil[7]  = 'de billones'; 
      $matmil[8]  = 'millones de billones'; 
      $matmil[10] = 'trillones'; 
      $matmil[11] = 'de trillones'; 
      $matmil[12] = 'millones de trillones'; 
      $matmil[13] = 'de trillones'; 
      $matmil[14] = 'billones de trillones'; 
      $matmil[15] = 'de billones de trillones'; 
      $matmil[16] = 'millones de billones de trillones'; 
      
      //Zi hack
      $float=explode('.',$num);
      $num=$float[0];
   
      $num = trim((string)@$num); 
      if ($num[0] == '-') { 
         $neg = 'menos '; 
         $num = substr($num, 1); 
      }else 
         $neg = ''; 
      while ($num[0] == '0') $num = substr($num, 1); 
      if ($num[0] < '1' or $num[0] > 9) $num = '0' . $num; 
      $zeros = true; 
      $punt = false; 
      $ent = ''; 
      $fra = ''; 
      for ($c = 0; $c < strlen($num); $c++) { 
         $n = $num[$c]; 
         if (! (strpos(".,'''", $n) === false)) { 
            if ($punt) break; 
            else{ 
               $punt = true; 
               continue; 
            } 
   
         }elseif (! (strpos('0123456789', $n) === false)) { 
            if ($punt) { 
               if ($n != '0') $zeros = false; 
               $fra .= $n; 
            }else 
   
               $ent .= $n; 
         }else 
   
            break; 
   
      } 
      $ent = '     ' . $ent; 
      if ($dec and $fra and ! $zeros) { 
         $fin = ' coma'; 
         for ($n = 0; $n < strlen($fra); $n++) { 
            if (($s = $fra[$n]) == '0') 
               $fin .= ' cero'; 
            elseif ($s == '1') 
               $fin .= $fem ? ' una' : ' un'; 
            else 
               $fin .= ' ' . $matuni[$s]; 
         } 
      }else 
         $fin = ''; 
      if ((int)$ent === 0) return 'Cero ' . $fin; 
      $tex = ''; 
      $sub = 0; 
      $mils = 0; 
      $neutro = false; 
      while ( ($num = substr($ent, -3)) != '   ') { 
         $ent = substr($ent, 0, -3); 
         if (++$sub < 3 and $fem) { 
            $matuni[1] = 'una'; 
            $subcent = 'as'; 
         }else{ 
            $matuni[1] = $neutro ? 'un' : 'uno'; 
            $subcent = 'os'; 
         } 
         $t = ''; 
         $n2 = substr($num, 1); 
         if ($n2 == '00') { 
         }elseif ($n2 < 21) 
            $t = ' ' . $matuni[(int)$n2]; 
         elseif ($n2 < 30) { 
            $n3 = $num[2]; 
            if ($n3 != 0) $t = 'i' . $matuni[$n3]; 
            $n2 = $num[1]; 
            $t = ' ' . $matdec[$n2] . $t; 
         }else{ 
            $n3 = $num[2]; 
            if ($n3 != 0) $t = ' y ' . $matuni[$n3]; 
            $n2 = $num[1]; 
            $t = ' ' . $matdec[$n2] . $t; 
         } 
         $n = $num[0]; 
         if ($n == 1) { 
            $t = ' ciento' . $t; 
         }elseif ($n == 5){ 
            $t = ' ' . $matunisub[$n] . 'ient' . $subcent . $t; 
         }elseif ($n != 0){ 
            $t = ' ' . $matunisub[$n] . 'cient' . $subcent . $t; 
         } 
         if ($sub == 1) { 
         }elseif (! isset($matsub[$sub])) { 
            if ($num == 1) { 
               $t = ' mil'; 
            }elseif ($num > 1){ 
               $t .= ' mil'; 
            } 
         }elseif ($num == 1) { 
            $t .= ' ' . $matsub[$sub] . '?n'; 
         }elseif ($num > 1){ 
            $t .= ' ' . $matsub[$sub] . 'ones'; 
         }   
         if ($num == '000') $mils ++; 
         elseif ($mils != 0) { 
            if (isset($matmil[$sub])) $t .= ' ' . $matmil[$sub]; 
            $mils = 0; 
         } 
         $neutro = true; 
         $tex = $t . $tex; 
      } 
      $tex = $neg . substr($tex, 1) . $fin; 
      //Zi hack --> return ucfirst($tex);
      $end_num=ucfirst($tex).' con '.$float[1].'/100 soles';
      return $end_num; 
   } 
}
