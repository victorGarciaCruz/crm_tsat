<?php

//require_once 'Clientes.php';

class FacturasCliente extends Controlador {

    private $id;           
    private $fetch;
    private $datosFactura;
    private $numero;        

    public function __construct() {
             
        if (!isset($_SESSION)) {
            session_start();
        }
        $this->controlPermisos(); 
        $this->modeloBase = $this->modelo('ModeloBase');
        $this->ModelFacturasCliente = $this->modelo('ModeloFacturasCliente');
        $this->ModelFacturasDetalleCliente = $this->modelo('ModeloFacturasDetalleCliente');        
        $this->ModelClientes = $this->modelo('ModeloClientes');
        $this->ModelFormasDePago = $this->modelo('ModeloFormasDePago'); 
        $this->ModelProductos = $this->modelo('ModeloProductos');        
        $this->ModelTiposIva = $this->modelo('ModeloTiposIva');   
        $this->ModelIncidencias = $this->modelo('ModeloIncidencias');


        $this->ModelCuentasBancarias = $this->modelo('ModeloCuentasBancarias');
        $this->arrFieldsCreate = ['numero','numerointerno','idcliente','cliente','fecha','vencimiento','observaciones','estado','idcuentabancaria','idformacobro','diascobro'];
        $this->arrFieldsValidate = ['idcliente','cif','fecha','estado'];
        $this->tabla = 'facturasclientes';      
        $this->tablaRows = 'facturasdetclientes';
        $this->arrFieldsUpdate = ['idcliente','cliente','fecha','vencimiento','observaciones','estado','idcuentabancaria','idformacobro','diascobro'];
        $this->validaIdFactura();
        $this->arrFieldsRowsCreate = ['idproducto','descripcion','unidad','cantidad','precio','descuento','ivatipo','subtotal','idfactura'];
        $this->arrFieldsRowsUpdate = ['idproducto','descripcion','unidad','cantidad','precio','descuento','ivatipo','subtotal']; 
        
        $this->tablaRowsPrefactura = 'prefacturasdetclientes';
        $this->arrFieldsRowsCreatePrefactura = ['idproducto','descripcion','unidad','cantidad','precio','descuento','ivatipo','subtotal','idincidencia','estado'];
        $this->arrFieldsRowsUpdatePrefactura = ['idproducto','descripcion','unidad','cantidad','precio','descuento','ivatipo','subtotal']; 
        $this->arrFieldsEmailSent = ['iddoc','fecha','tipodoc','nomfichero','destinatarios','asunto','mensaje','correoremitente','nomremitente']; 

        if(file_get_contents("php://input")){
            $payload = file_get_contents("php://input");    
            $this->fetch = json_decode($payload, true);
        } 
    }

    private function validaIdFactura()
    {
        $id=0;
        if(isset($_POST['id']) && $_POST['id'] > 0)
        {
            $id = $_POST['id'];
        }
        if(isset($_SESSION['verFactura']) && $_SESSION['verFactura'] > 0){
            $id = $_SESSION['verFactura'];
        }
        if(isset($_SESSION['idFacturaEditFactura']) && $_SESSION['idFacturaEditFactura'] > 0){
            $id = $_SESSION['idFacturaEditFactura'];
        }

        if($id > 0 && $this->modeloBase->existIdInvoice($this->tabla, $id) > 0){
            $this->id = $id;
            $this->asignarPropiedadesFactura();                        
        }
                
  
    }

    private function asignarPropiedadesFactura(){
        
        $datosObjeto = $this->ModelFacturasCliente->obtenerDatosFactura($this->id);
        $this->datosFactura = $datosObjeto;
        if($datosObjeto){
            $this->numero = $datosObjeto->numero;
        }
    }

    public function index() {
        $datos = [];        
        $this->vista('facturasCliente/listadoFacturasAdmin',$datos);
    }

    public function crearFactura()
    {        

        $datos = [];      
    
        if ($_SESSION['nombrerol'] == 'admin' ) {        
            
            $datos = [
                //'clientes' => $this->ModelClientes->obtenerClientesSelect(),
                'formasdepago' => $this->ModelFormasDePago->obtenerFormasDePagoSelect(),
                //'ctasbancarias' => $this->ModelCuentasBancarias->obtenerCuentasBancariasSelect(),
                'html' => ''
            ];
        
            $this->vista('facturasCliente/crearNuevaFactura', $datos);
        }else{
            redireccionar('/FacturasCliente');
        }
            
    }
    
        
    public function registrarFactura()
    {                       
        $respuesta['error'] = true;
        $respuesta['mensaje'] = ERROR_GUARDADO;         

        if(isset($_POST['idcliente']) && $_POST['idcliente'] > 0 && isset($_POST['estado']) && $_POST['estado'] !='' && trim($_POST['cif']) !='' && $_POST['fecha'] != '' ){                              

            
                $_POST['rectificativa'] = (isset($_POST['rectificativa']))? 1: 0;

                
                /*echo"<br><br>post<br>";
                print_r($_POST);*/
                
                $numeracion = DocumentHelper::buildNumberDocument($this->modeloBase->maximoNumDocumentoAnio('numerointerno',$this->tabla, date("Y",strtotime($_POST['fecha'])), $_POST['rectificativa']), $_POST['fecha'], $_POST['rectificativa']);

                /* echo"<br><br>numeracion<br>";
                print_r($numeracion);
                die; */
        
                
                $_POST['numero'] = $numeracion['numero'];
                $_POST['numerointerno'] = $numeracion['numerointerno'];
                $_POST['cliente'] = $this->ModelClientes->obtenerNombreClientePorId($_POST['idcliente']);
                $_POST['idformacobro'] = (isset($_POST['idformacobro']) && $_POST['idformacobro'] > 0)? $_POST['idformacobro']:0;
                $_POST['idcuentabancaria'] = (isset($_POST['idcuentabancaria']) && $_POST['idcuentabancaria'] > 0)? $_POST['idcuentabancaria']:0;               
                $_POST['diascobro'] = (isset($_POST['diascobro']) && $_POST['diascobro'] > 0)? $_POST['diascobro']:0;
                $_POST['vencimiento'] = (isset($_POST['vencimiento']) && $_POST['vencimiento']!= '')? $_POST['vencimiento']:'0000-00-00';   

                $this->guardarCifCliente($_POST['idcliente'],trim($_POST['cif']),$_POST['cifguardar']);
               
                $ins = $this->ModelFacturasCliente->insertHeaderInvoice($_POST);
                if($ins){                        
                    $_SESSION['verFactura'] = $ins;
                    $respuesta['error'] = false;
                    $respuesta['mensaje'] = OK_CREACION;           
                    $respuesta['idfactura'] = $ins;         
                                            
                    if(isset($_POST['numeroOrden']) && count($_POST['numeroOrden']) > 0 ){
                        $insRows = $this->guardarFilasProductosDocumento($_POST, $ins);            
                        
                        if ($insRows) { // Solo si las filas se guardaron exitosamente
                            $this->actualizarTotalesFactura($ins);
                        }
                    } else {
                        $respuesta['mensaje'] = 'Se ha creado la factura sin filas';
                    }                                                                        
                }else{
                    $respuesta['mensaje'] = ERROR_CREACION;                        
                }

        }else{
            $fieldsValidate = UtilsHelper::validateRequiredFields($_POST, $this->arrFieldsValidate);
            $respuesta['mensaje'] = ERROR_FORM_INCOMPLETO;
            $respuesta['fieldsValidate'] = $fieldsValidate;
        } 
        echo json_encode($respuesta);

    }    

    private function guardarCifCliente($idCliente, $cif, $cifguardar)
    {
        if($cifguardar==1){
            $this->ModelClientes->actualizarCifCliente($idCliente, strtoupper($cif));
        }
    }

    private function actualizarTotalesFactura($idFactura)
    {
        $totales = $this->ModelFacturasDetalleCliente->obtenerTotalesFactura($idFactura);      

        $arrFieldsValues['baseimponible'] = $totales->suma_base_imponible;      
        //$arrFieldsValues['descuentoimporte'] = $totales->suma_descuento;
        $arrFieldsValues['ivatotal'] = $totales->suma_iva;
        $arrFieldsValues['total'] = $totales->total_final;
        $fieldsValuesString = UtilsHelper::buildStringsFieldsUpdateQuery($arrFieldsValues);
       
        $arrWhere['id'] = $idFactura;
        $whereString = UtilsHelper::buildStringsWhereQueryOnly($arrWhere);   
        $upd = $this->modeloBase->updateRow($this->tabla, $fieldsValuesString, $whereString);     
    }

    private function guardarFilasProductosDocumento($post, $idFactura)
    {

        $retorno = false;
        $cont = 0;   
        $contValid = 0;
                 
        foreach ($post['numeroOrden'] as $key => $value) {

            if(isset($post['idArticulo'][$key])){
                
                $contValid++;

                $tmp = [];              
                $tmp['idproducto'] = $post['idArticulo'][$key];
                $tmp['descripcion'] = $this->ModelProductos->obtenerNombreProductoByNumero($post['idArticulo'][$key]);
                if(isset($post['tipoDescripcion'][$key]) && $post['tipoDescripcion'][$key] == 'texto'){
                    $tmp['idproducto'] = 0;
                    $descripcion = $post['idArticulo'][$key];  
                    $descripcion = str_replace('–', '-', $descripcion);  // Reemplaza guión largo con guión corto                    
                    $tmp['descripcion'] = $descripcion;
                }
                $descuento = (isset($post['descuentoArticulo'][$key]) && $post['descuentoArticulo'][$key]!= '' && $post['descuentoArticulo'][$key] > 0)? str_replace(",", ".", $post['descuentoArticulo'][$key]):0;
                $tmp['descuento'] = $descuento;

                $unidad = '';
                if(isset($post['tipoDescripcion'][$key]) && $post['tipoDescripcion'][$key] == 'codigo'){
                    $unidad = $this->ModelProductos->obtenerUnidadProducto($post['idArticulo'][$key]);
                }                
                $tmp['unidad'] = $unidad;

                $cantidad = ($post['cantidadArticulo'][$key] != '')? str_replace(",", ".", $post['cantidadArticulo'][$key]): 0;
                $tmp['cantidad'] = $cantidad;
                $precio = ($post['precioArticulo'][$key] != '')? str_replace(",", ".", $post['precioArticulo'][$key]): 0;
                $tmp['precio'] = $precio;
                $tmp['ivatipo'] = (isset($post['iva'][$key]) && $post['iva'][$key] != '')? $post['iva'][$key]: 0;
                $subTotalAntesDscto = $cantidad * $precio;
                $valorDscto = round($subTotalAntesDscto * $descuento/100,2);
                $subTotal = $subTotalAntesDscto - $valorDscto;
                $tmp['subtotal'] = $subTotal;                     
                $tmp['idfactura'] = $idFactura;
                $_POST['observaciones'] = str_replace('–', '-', $_POST['observaciones']);
                                                                
                if(isset($post['idFila'][$key])){
                    
                

                    $tmp['id'] = $post['idFila'][$key]; 
                    $insRow= $this->ModelFacturasDetalleCliente->actualizarFilaFactura($tmp);
                    if($insRow){             
                        $cont++;          
                    }
                    
                }else{
                    
                    
                    $insRow= $this->ModelFacturasDetalleCliente->insertarNuevaFilaFactura($tmp);
                    if($insRow){             
                        $cont++;          
                    }
                    
                }                                                  
            }
            
        }

        if($cont == $contValid){            
            $retorno = true;
        }
        return $retorno;
       
    }    

 
    private function construirBodyTablaGrilla($idFactura){

        $rows = $this->ModelFacturasDetalleCliente->obtenerFilasFactura($idFactura);     

        $datos = [                        
            'productos' => $this->ModelProductos->buscarProdutosActivos(),
            'tiposIva' => $this->ModelTiposIva->obtenerTipoIvaActivos()
        ]; 

        $html = TemplateHelperDocumento::buildGridRows($rows, $datos, 'factura_cliente');
        return $html;
    }    
    
    public function verFactura()
    {                
        unset($_SESSION['verFactura']);
        unset($_SESSION['idFacturaEditFactura']);
        
        if (isset($this->id) && $this->id > 0 ) {

            
            if($this->modeloBase->existIdInvoice($this->tabla, $this->id) > 0){
            
                $documento = $this->ModelFacturasCliente->obtenerDatosFactura($this->id);                            
                $cab = $this->construirCabeceraDocumento();
                $cabecera = (array) $cab;
                
                $detalle['html'] = $this->construirBodyTablaGrilla($this->id);
                                 
    
                $tmp = [
                    'idFactura' => $this->id,                    
                    'documento' => $this->datosFactura,                    
                    'formasdepago' => $this->ModelFormasDePago->obtenerFormasDePagoSelect(),
                    'descuento' => $this->calcularImporteTotalDescuento($this->id),
                    'baseimponiblesindscto' => $this->calcularBaseImponibleAntesDeDscto($this->id)
                ];
                
                $datos = array_merge($tmp, $cabecera, $detalle);          
               
                $this->vista('facturasCliente/verFactura', $datos);

            }else{        
                redireccionar('/FacturasCliente');
            }
            

        }else{        
            redireccionar('/FacturasCliente');
        }                 

    }    
    
    private function calcularImporteTotalDescuento($idFactura)
    {
        $tot = 0;
        $rows = $this->ModelFacturasDetalleCliente->obtenerFilasFactura($idFactura);     
        if(!empty($rows)){
            foreach ($rows as $row) {
                 $cantidad = (!empty($row->cantidad))? $row->cantidad: 0;
                $precio = (!empty($row->precio))? $row->precio: 0;
                $descuento = (!empty($row->descuento))? $row->descuento: 0;

                $dscto =  $cantidad * $precio * $descuento / 100;
                $tot += $dscto; 
               
                
            }
        }
        return $tot;
    }

    private function calcularBaseImponibleAntesDeDscto($idFactura)
    {
        $tot = 0;
        $rows = $this->ModelFacturasDetalleCliente->obtenerFilasFactura($idFactura);     
        if(!empty($rows)){
            foreach ($rows as $row) {
                $cantidad = (!empty($row->cantidad))? $row->cantidad: 0;
                $precio = (!empty($row->precio))? $row->precio: 0;                
                $tot += $cantidad * $precio;                               
            }
        }
        return $tot;
    }

    private function construirCabeceraDocumento()
    {
        $datos = $this->ModelFacturasCliente->obtenerDatosFactura($this->id);
        return $datos;
    }

    public function obtenerCliente()
    {  
        $respuesta['error'] = true;
        $respuesta['mensaje'] = ERROR_DOESNT_EXIST;   
                    
        if(isset($this->fetch) && $this->fetch['id'] > 0) {
            $idCliente = $this->fetch['id'];            
            $respuesta['error'] = false;
            $respuesta['mensaje'] = '';
            $respuesta['datos'] = $this->ModelClientes->obtenerDatosClientePorId($idCliente);            
        }                         
        print_r(json_encode($respuesta));
    }    


    public function actualizarFactura()
    {        
        $respuesta['error'] = true;
        $respuesta['mensaje'] = ERROR_ACTUALIZACION;            

        if(isset($this->id) && $this->id>0 && isset($_POST['idcliente']) && $_POST['idcliente'] > 0 && isset($_POST['estado']) && $_POST['estado'] !='' && trim($_POST['cif']) !='' && $_POST['fecha'] != ''){                              
                                
                $_POST['cliente'] = $this->ModelClientes->obtenerNombreClientePorId($_POST['idcliente']);                                                                
                $_POST['idformacobro'] = (isset($_POST['idformacobro']) && $_POST['idformacobro'] > 0)? $_POST['idformacobro']:0;
                $_POST['idcuentabancaria'] = (isset($_POST['idcuentabancaria']) && $_POST['idcuentabancaria'] > 0)? $_POST['idcuentabancaria']:0;               
                $_POST['diascobro'] = (isset($_POST['diascobro']) && $_POST['diascobro'] > 0)? $_POST['diascobro']:0;
                $_POST['vencimiento'] = (isset($_POST['vencimiento']) && $_POST['vencimiento']!= '')? $_POST['vencimiento']:'0000-00-00';

                $_POST['observaciones'] = str_replace('–', '-', $_POST['observaciones']);                

                $this->guardarCifCliente($_POST['idcliente'],trim($_POST['cif']),$_POST['cifguardar']);              
               
                $_POST['id'] = $this->id; 
                $upd = $this->ModelFacturasCliente->updateHeaderInvoice($_POST);
    
                if($upd){                        
                        $respuesta['error'] = false;
                        $respuesta['mensaje'] = OK_ACTUALIZACION;   
                        $_SESSION['verFactura'] = $this->id;

                        if(isset($_POST['numeroOrden']) && count($_POST['numeroOrden']) > 0 ){
                            $insRows = $this->guardarFilasProductosDocumento($_POST,$this->id);                      
                        }  
                        $this->actualizarTotalesFactura($this->id);                          
                }else{
                    $respuesta['mensaje'] = ERROR_ACTUALIZACION;                        
                }                        

        }else{
            $fieldsValidate = UtilsHelper::validateRequiredFields($_POST, $this->arrFieldsValidate);
            $respuesta['mensaje'] = ERROR_FORM_INCOMPLETO;
            $respuesta['fieldsValidate'] = $fieldsValidate;
        } 
        echo json_encode($respuesta);

    }    


    public function construirCondicionesBuscar($datos)
    {
    
        $tamanio = count((array) $datos);
        $cond = '';
        if ($tamanio > 0) {                                
            $cont = 0;
            $cond = " AND  (";
            foreach ($datos as $key => $value) {

                $cont++;                   
                
                if ($cont < ($tamanio) ) {                    
                    $y =  " LIKE " . "'%$value%'" . " AND ";
                } else {                    
                    $y =  " LIKE " . "'%$value%'" . ") ";
                }
                if ($key == 'Nº') {
                    $cond .= "fac.id" . $y;
                }
                if ($key == 'Numero factura') {
                    $cond .= "fac.numero" . $y;
                }
                /* if ($key == 'Numero factura') {
                    $numeroCond = "(fac.numero LIKE '%$value%' 
                                    OR (fac.rectificativa = 1 AND CONCAT(fac.numero, 'R') LIKE '%$value%'))";
                    if ($cont < $tamanio) {
                        $cond .= $numeroCond . " AND ";
                    } else {
                        $cond .= $numeroCond . ") ";
                    }
                } */

                if ($key == 'cliente') {
                    $cond .= "fac.cliente" . $y;
                }
                if ($key == 'cif') {
                    $cond .= "cli.cif" . $y;
                }
                if ($key == 'fecha') {
                        
                    $fechaEstandar = " DATE_FORMAT( fac.fecha, '%d/%m/%Y' ) LIKE '%".$value."%' ";
                    
                    if ($cont < ($tamanio) ) {                    
                        $m =  " AND ";
                    } else {                    
                        $m =  " ) ";
                    }

                    $cond .= $fechaEstandar . $m;
                }
                if ($key == 'vencimiento') {
                        
                    $vencimientoEstandar = " DATE_FORMAT( fac.vencimiento, '%d/%m/%Y' ) LIKE '%".$value."%' ";
                    
                    if ($cont < ($tamanio) ) {                    
                        $v =  " AND ";
                    } else {                    
                        $v =  " ) ";
                    }

                    $cond .= $vencimientoEstandar . $v;
                }        
                if ($key == 'estado') {
                    $cond .= "fac.estado" . $y;
                }                     
            }                                        
        }

        return $cond;
    }    

    public function crearTablaFacturasAdmin()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $orden = $_POST['orden'];
            $tipoOrden = $_POST['tipoOrden'];                
        }

        
        $cond = '1';

        $filaspagina = $filas * $pagina;
    
        /*
        echo"<br>buscar<br>";
        print_r($buscar);
        */

        if ($buscar != "") {            
            $datos = json_decode($buscar);
            $cond .= $this->construirCondicionesBuscar($datos);     
        }
     
        /*
        echo"<br>cond<br>";
        print_r($cond);
        die;
        */

        $registros = $this->ModelFacturasCliente->registrosAdminTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond);      
        print(json_encode($registros));  
    }    

    public function totalRegistrosFacturasAdmin()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];            
        }

        $cond = '1';
    
        if ($buscar != "") {                           
            $datos = json_decode($buscar);            
            $cond .= $this->construirCondicionesBuscar($datos); 
        }
        $contador = $this->ModelFacturasCliente->totalRegistrosBuscar($cond);

        $cont = $contador->contador;        
        print_r($cont);
    }  

    public function obtenerDatosParaFilaNueva()
    {                          
        $retorno['error'] = true;
        $retorno['mensaje'] = 'No se pueden agregar una nueva fila.';
        
        if(isset($this->fetch) && isset($this->fetch['tipo_linea']) && $this->fetch['tipo_linea'] !='' && isset($this->fetch['tbody']) && $this->fetch['tbody'] !='' && isset($this->fetch['filaOrden']) && $this->fetch['filaOrden'] !='') {

            $articulos = $this->ModelProductos->buscarProdutosActivos();
            $ivas = $this->ModelTiposIva->obtenerTipoIvaActivos();

            $retorno = [        
                'error' => false,                
                'fila' => TemplateHelperDocumento::buildRowGridDocument($this->fetch, $articulos, $ivas)
            ];
    
        }              
        echo json_encode($retorno);  
    }    


    public function eliminarFilaDetalle()
    {
        $respuesta['error'] = true;
        $respuesta['mensaje'] = ERROR_DOESNT_EXIST;   

        if(isset($this->fetch['idFila']) && $this->fetch['idFila'] > 0 && $this->fetch['idFactura'] > 0) {           
           
                $idFilaFactura = $this->fetch['idFila'];                
                $where = " id = '$idFilaFactura' ";

                $delFila = $this->modeloBase->deleteRow($this->tablaRows, $where);

                if ($delFila) {
                                    
                    $this->actualizarTotalesFactura($this->fetch['idFactura']);

                    $this->ModelFacturasDetalleCliente->actualizarFilaPreFacturaByIdFactura(0,$idFilaFactura, 'sin Fact.');
                    $this->ModelFacturasDetalleCliente->actualizarIdFilaFactura($idFilaFactura);
                    
                    $respuesta['error'] = false;
                    $respuesta['mensaje'] = OK_ELIMINACION;       
                    $respuesta['datos'] = $this->ModelFacturasDetalleCliente->obtenerTotalesFacturaFormat($this->fetch['idFactura']);
    
                }else{
                    $respuesta['mensaje'] = ERROR_ELIMINACION;   
                }
        

        }
        print_r(json_encode($respuesta));   
    }    

    public function enviarIdFacturaGenerarPdf()
    {
        $respuesta['error'] = true;
        $respuesta['mensaje'] = 'No se puede generar el pdf de la factura.';   
        if(isset($this->fetch['id']) && $this->fetch['id'] > 0){
            $_SESSION['idFacturaSendPdf'] = $this->fetch['id'];
            $respuesta['error'] = false;
            $respuesta['mensaje'] = '';   
        }
        print_r(json_encode($respuesta));   
    }  

    public function exportarPdfFactura()
    {      
       
        if(isset($_SESSION['idFacturaSendPdf']) && $_SESSION['idFacturaSendPdf'] >0){

            $idFactura = $_SESSION['idFacturaSendPdf'];
                        
            $cabecera = $this->ModelFacturasCliente->obtenerDatosFacturaYCliente($idFactura);                   
            $datos['cabecera'] = $cabecera;
            $datos['detalle'] = $this->ModelFacturasDetalleCliente->obtenerFilasFactura($idFactura);            
            $datos['tipo'] = ($cabecera->rectificativa==1)? 'factura rectificativa': 'factura';
            $datos['tiporazonsocial'] = 'cliente';               
               
            $cuentasBancarias = $this->construirStringCuentasContables($cabecera->cuentas);
            $datos['cuentasBancarias'] = $cuentasBancarias;

            $descuento = $this->calcularImporteTotalDescuento($idFactura);
            $datos['descuento'] = $descuento;
            $datos['baseimponiblesindscto'] = $this->calcularBaseImponibleAntesDeDscto($idFactura);
                    
            generarPdf::documentoPDFExportar('P', 'A4', 'es', true, 'UTF-8', array(0, 0, 0, 0), true, 'documentos', 'factura.php', $datos);                    

        }else{
            echo"<br>error en la generación del pdf de la factura<br>";
            die;
        }

    }

 

    private function construirStringCuentasContables($cuentas)
    {
        
        $retorno = false;     
        
        if(isset($cuentas) && count(json_decode($cuentas)) > 0){

            $idsCuentas = json_decode($cuentas);                  

            $s = (count($idsCuentas) > 1)? 's':'';

            $retorno .= "";
            
            $totalCuentas = count($idsCuentas);

            $cont=0;

            foreach ($idsCuentas as $cuenta) {
                $cont++;
                $q = $this->ModelCuentasBancarias->obtenerDatosCuentaBancariaPorId($cuenta);
                $numeroCuenta = (isset($q->numerocuenta))? $q->numerocuenta: '';
                $retorno .= $numeroCuenta ." ";

                // Añadir '//' solo si no es el último elemento
                if ($cont < $totalCuentas) {
                    $retorno .= " - ";
                }
                
            }
        }
       
        return $retorno;
    }

    public function obtenerDatosParaFilaNuevaFacturaIncidencia()
    {                          
        $retorno['error'] = true;
        $retorno['mensaje'] = 'No se pueden agregar una nueva fila.';
        
        if(isset($this->fetch) && isset($this->fetch['tipo_linea']) && $this->fetch['tipo_linea'] !='' && isset($this->fetch['tbody']) && $this->fetch['tbody'] !='' && isset($this->fetch['filaOrden']) && $this->fetch['filaOrden'] !='') {

            $articulos = $this->ModelProductos->buscarProdutosActivos();
            $ivas = $this->ModelTiposIva->obtenerTipoIvaActivos();

            $retorno = [        
                'error' => false,                
                'fila' => TemplateHelperDocumento::buildRowGridRequest($this->fetch, $articulos, $ivas)
            ];
    
        }              
        echo json_encode($retorno);  
    }    


   
    public function enviarProductosGuardadosSinFacturar()
    {    
        $respuesta['error'] = true;
        $respuesta['mensaje'] = ERROR_GUARDADO;         

        if(isset($_POST['idIncidenciaVer']) && $_POST['idIncidenciaVer'] > 0 && isset($_POST['numeroOrden']) && count($_POST['numeroOrden']) > 0){
           
            $prefacturaRows = $this->guardarFilasProductosPreFactura($_POST);
            if($prefacturaRows){

                $respuesta['error'] = false;
                $respuesta['mensaje'] = OK_GUARDADO;
                
                $rows = $this->ModelFacturasDetalleCliente->obtenerFilasPrefactura($_POST['idIncidenciaVer']);

                $datos = [                        
                    'productos' => $this->ModelProductos->buscarProdutosActivos(),
                    'tiposIva' => $this->ModelTiposIva->obtenerTipoIvaActivos()
                ];                         
                $respuesta['htmlPrefacturaDetalle'] = TemplateHelperDocumento::buildRowGridRequestWithData($rows, $datos);
            }            
        }else{            
            $respuesta['mensaje'] = ERROR_FORM_INCOMPLETO;            
        } 
        echo json_encode($respuesta);
    }


    private function guardarFilasProductosPreFactura($post)
    {

        $retorno = false;
        $cont = 0;   
        $contValid = 0;
                 
        foreach ($post['numeroOrden'] as $key => $value) {

            if(isset($post['idArticulo'][$key])){
                
                $contValid++;

                $tmp = [];              
                $tmp['idproducto'] = $post['idArticulo'][$key];
                $tmp['descripcion'] = $this->ModelProductos->obtenerNombreProductoByNumero($post['idArticulo'][$key]);
                if(isset($post['tipoDescripcion'][$key]) && $post['tipoDescripcion'][$key] == 'texto'){
                    $tmp['idproducto'] = 0;
                    $tmp['descripcion'] = $post['idArticulo'][$key];    
                }
                $descuento = (isset($post['descuentoArticulo'][$key]) && $post['descuentoArticulo'][$key]!= '' && $post['descuentoArticulo'][$key] > 0)? str_replace(",", ".", $post['descuentoArticulo'][$key]):0;
                $tmp['descuento'] = $descuento;
                $tmp['unidad'] = $post['unidadArticulo'][$key] ?? '';
                $cantidad = ($post['cantidadArticulo'][$key] != '')? str_replace(",", ".", $post['cantidadArticulo'][$key]): 0;
                $tmp['cantidad'] = $cantidad;
                $precio = ($post['precioArticulo'][$key] != '')? str_replace(",", ".", $post['precioArticulo'][$key]): 0;
                $tmp['precio'] = $precio;
                $tmp['ivatipo'] = (isset($post['iva'][$key]) && $post['iva'][$key] != '')? $post['iva'][$key]: 0;
                $subTotalAntesDscto = $cantidad * $precio;
                $valorDscto = round($subTotalAntesDscto * $descuento/100,2);
                $subTotal = $subTotalAntesDscto - $valorDscto;
                $tmp['subtotal'] = $subTotal;                     
                $tmp['idincidencia'] = $post['idIncidenciaVer'];
                $tmp['estado'] = 'sin Fact.'; 

                
    
                $arrWhere = [];
                if(isset($post['idFila'][$key])){

                    ////////////
                    $filaFacturada = $this->ModelFacturasDetalleCliente->verificarSiFilPrefacturaEstaFacturada($post['idFila'][$key]);
                    if($filaFacturada>0){
                        $cont++;
                    }else{

                            
                        $arraFields = $this->arrFieldsRowsUpdatePrefactura;
                        $arrWhere['id'] = $post['idFila'][$key]; 

                        $stringQueries = UtilsHelper::buildStringsUpdateQuery($tmp, $arraFields);
                        $ok = $stringQueries['ok'];                        
                            
                        $stringWhere = UtilsHelper::buildStringsWhereQuery($arrWhere);
                        $okw = $stringWhere['ok'];                                           
                                    
                        if($ok && $okw){
                            $strFieldsValues = $stringQueries['strFieldsValues'];
                            $strWhere = $stringWhere['strWhere'];                  
                                
                            $insRow = $this->modeloBase->updateRow($this->tablaRowsPrefactura, $strFieldsValues, $strWhere);
        
                            if($insRow){             
                                $cont++;          
                            }
                        }     
                        
                    }
                    ////////////
                                   
                }else{
                    
                    $arraFields = $this->arrFieldsRowsCreatePrefactura; 

                    $stringQueries = UtilsHelper::buildStringsInsertQueryNuevo2($tmp, $arraFields);
                                       
                    $ok = $stringQueries['ok'];
                    $strFields = $stringQueries['strFields'];
                    $strValues = $stringQueries['strValues'];
                    if($ok){
                        $insRow = $this->modeloBase->insertRow($this->tablaRowsPrefactura, $strFields, $strValues);
                        if($insRow){
                            $cont++;                                            
                        }
                    }                      
                }                                                  
            }
            
        }

        if($cont == $contValid){            
            $retorno = true;
        }
        return $retorno;
       
    }    

    private function guardarFilasProductosParaFactura($post, $idFactura)
    {

        $retorno = false;
        $cont = 0;   
        $contValid = 0;

        $arr = explode(",",$post['checkBoxesEnviar']);
                 
        foreach ($post['numeroOrden'] as $key => $value) {

            if(isset($post['idArticulo'][$key])){
                
                $contValid++;

                $tmp = [];  

                $tmp['facturar_linea'] = (isset($arr[$key]))? $arr[$key]:'no';

                $tmp['idproducto'] = $post['idArticulo'][$key];
                $tmp['descripcion'] = $this->ModelProductos->obtenerNombreProductoByNumero($post['idArticulo'][$key]);
                if(isset($post['tipoDescripcion'][$key]) && $post['tipoDescripcion'][$key] == 'texto'){
                    $tmp['idproducto'] = 0;
                    $tmp['descripcion'] = $post['idArticulo'][$key];    
                }
                $descuento = (isset($post['descuentoArticulo'][$key]) && $post['descuentoArticulo'][$key]!= '' && $post['descuentoArticulo'][$key] > 0)? str_replace(",", ".", $post['descuentoArticulo'][$key]):0;
                $tmp['descuento'] = $descuento;                
                $tmp['unidad'] = $post['unidadArticulo'][$key] ?? '';
                $cantidad = ($post['cantidadArticulo'][$key] != '')? str_replace(",", ".", $post['cantidadArticulo'][$key]): 0;
                $tmp['cantidad'] = $cantidad;
                $precio = ($post['precioArticulo'][$key] != '')? str_replace(",", ".", $post['precioArticulo'][$key]): 0;
                $tmp['precio'] = $precio;
                $tmp['ivatipo'] = ($post['iva'][$key] != '')? $post['iva'][$key]: 0;
                $subTotalAntesDscto = $cantidad * $precio;
                $valorDscto = round($subTotalAntesDscto * $descuento/100,2);
                $subTotal = $subTotalAntesDscto - $valorDscto;
                $tmp['subtotal'] = $subTotal;                     
                $tmp['idincidencia'] = $post['idIncidenciaVer'];
                $tmp['estado'] = 'sin Fact.'; 
                $tmp['idfactura'] = $idFactura;                
                  

                $arrWhere = [];
                if(isset($post['idFila'][$key])){
                    
                    $arraFields = $this->arrFieldsRowsUpdatePrefactura;
                    $arrWhere['id'] = $post['idFila'][$key]; 

                    $stringQueries = UtilsHelper::buildStringsUpdateQuery($tmp, $arraFields);
                    $ok = $stringQueries['ok'];                        
                        
                    $stringWhere = UtilsHelper::buildStringsWhereQuery($arrWhere);
                    $okw = $stringWhere['ok'];
                                
                    if($ok && $okw){
                        $strFieldsValues = $stringQueries['strFieldsValues'];
                        $strWhere = $stringWhere['strWhere'];                  
                             
                        $insRow = $this->modeloBase->updateRow($this->tablaRowsPrefactura, $strFieldsValues, $strWhere);
    
                        if($insRow){  
                            $cont++; 

                            if($tmp['facturar_linea'] == "si"){                                              

                                $idFilaFactura = $this->crearLineaFactura($tmp); 
                                if($idFilaFactura > 0){
                                  
                                    $this->ModelFacturasDetalleCliente->actualizarIdFilaFacturaEnPreFactura($idFactura, $idFilaFactura, $arrWhere['id'], 'facturado');

                                    $this->actualizarTotalesFactura($idFactura);
                                    
                                }
                            }                             
                                                                                                                
                        }
                    }

                }else{
                    
                    $arraFields = $this->arrFieldsRowsCreatePrefactura; 
                    $stringQueries = UtilsHelper::buildStringsInsertQueryNuevo2($tmp, $arraFields);
                                       
                    $ok = $stringQueries['ok'];
                    $strFields = $stringQueries['strFields'];
                    $strValues = $stringQueries['strValues'];
                    if($ok){
                        $insRow = $this->modeloBase->insertRow($this->tablaRowsPrefactura, $strFields, $strValues);
                        if($insRow){
                            $cont++;

                            if($tmp['facturar_linea'] == "si"){
                                $idFilaFactura = $this->crearLineaFactura($tmp);
                                if($idFilaFactura > 0){
                                  
                                    $this->ModelFacturasDetalleCliente->actualizarIdFilaFacturaEnPreFactura($idFactura, $idFilaFactura, $insRow, 'facturado');

                                    $this->actualizarTotalesFactura($idFactura);

                                }
                            } 
                            
                        }
                    }      

                }                                                  
            }
            
        }

        if($cont == $contValid){            
            $retorno = true;
        }
        return $retorno;
       
    }    

    private function crearLineaFactura($tmp)
    {   
        
        $insRow = false;
        $arraFields = $this->arrFieldsRowsCreate; 
    
        $stringQueries = UtilsHelper::buildStringsInsertQueryNuevo2($tmp, $arraFields);

        $ok = $stringQueries['ok'];
        $strFields = $stringQueries['strFields'];
        $strValues = $stringQueries['strValues'];
        if($ok){
            $insRow = $this->modeloBase->insertRow($this->tablaRows, $strFields, $strValues);            
        }    
        return $insRow;
    }

    public function enviarProductosGuardadosParaFacturar()
    {
        
        $respuesta['error'] = true;
        $respuesta['mensaje'] = ERROR_GUARDADO;         

        if(isset($_POST['idIncidenciaVer']) && $_POST['idIncidenciaVer'] > 0 && isset($_POST['numeroOrden']) && count($_POST['numeroOrden']) > 0){

            $facturaExiste = $this->ModelIncidencias->verificaSiIncidenciaTieneFacturaAsociada($_POST['idIncidenciaVer']);
                
            if($facturaExiste){
                $idFactura = $facturaExiste;                                       
            }else{

                if(isset($_POST['idFacturaEnviar']) && $_POST['idFacturaEnviar'] > 0){
                    $idFactura = $_POST['idFacturaEnviar'];
                }else{
                    $idFactura = $this->crearCabeceraFactura($_POST);
                }                    
            }                                   

            if($idFactura > 0){

                $prefacturaRows = $this->guardarFilasProductosParaFactura($_POST, $idFactura);

                if($prefacturaRows){

                    $this->ModelIncidencias->actualizarCampoIdFacturaEnIncidencia($_POST['idIncidenciaVer'], $idFactura);

                    $this->cambiarEstadoIncidencia($_POST['idIncidenciaVer']);

                    $respuesta['error'] = false;
                    $respuesta['mensaje'] = 'Se ha generado la factura corréctamente.';             
                    $respuesta['idfactura'] = $idFactura; 

                    $rows = $this->ModelFacturasDetalleCliente->obtenerFilasPrefactura($_POST['idIncidenciaVer']);    
                    $datos = [                  
                        'productos' => $this->ModelProductos->buscarProdutosActivos(),
                        'tiposIva' => $this->ModelTiposIva->obtenerTipoIvaActivos()
                    ];                   
                    $respuesta['htmlPrefacturaDetalle'] = TemplateHelperDocumento::buildRowGridRequestWithData($rows, $datos);
                    $numFactura = $this->ModelFacturasCliente->nuFacturaPorIdFactura($idFactura);
                    $respuesta['numfacturafields'] = TemplateHelperDocumento::buildHtmlLinkInvoice($numFactura, $idFactura);
                    $respuesta['botonesFacturaIncidencia'] = TemplateHelperDocumento::buildButtonsActionInvoicesFromRequest($idFactura);
                                    
                }               
                         
            }
       
            
            
            
        }else{            
            $respuesta['mensaje'] = ERROR_FORM_INCOMPLETO;            
        } 
        echo json_encode($respuesta);
    }

    private function cambiarEstadoIncidencia($idIncidencia)
    {
        $sinfacturar = $this->ModelFacturasDetalleCliente->contarLineasPreFacturaSinFacturar($idIncidencia);

        if($sinfacturar > 0){
            $this->ModelIncidencias->actualizarEstadoFacturaPresupuesto($idIncidencia, 7, 'FParc');
        }
    }

    private function crearCabeceraFactura($post)
    {
        $idFactura = 0;        

        $datosIncidencia = $this->ModelIncidencias->obtenerFatosIncidencia($post['idIncidenciaVer']);

        $post['cliente'] = $this->ModelClientes->obtenerNombreClientePorId($datosIncidencia->idcliente);

        $numeracion = DocumentHelper::buildNumberDocument($this->modeloBase->maximoNumDocumentoAnio('numerointerno',$this->tabla, date("Y",strtotime(date("Y-m-d"))), 0), date("Y-m-d"), 0);
        
        $post['idcliente'] = $datosIncidencia->idcliente;
        $post['fecha'] = date("Y-m-d");
        $post['numero'] = $numeracion['numero'];
        $post['numerointerno'] = $numeracion['numerointerno'];
        $post['idformacobro'] = 0;
        $post['idcuentabancaria'] = 0;               
        $post['diascobro'] = 0;
        $post['vencimiento'] = '0000-00-00';  
        $post['estado'] = 'impagado';    
        $post['observaciones'] = '';         
                                                                                                     
        $stringQueries = UtilsHelper::buildStringsInsertQueryNuevo2($post, $this->arrFieldsCreate);
        $ok = $stringQueries['ok'];
        $strFields = $stringQueries['strFields'];
        $strValues = $stringQueries['strValues'];            
                       
        if($ok){
            $idFactura = $this->modeloBase->insertRow($this->tabla, $strFields, $strValues);                                
        }                   
        return $idFactura;
    }

    private function crearLineasFacturaDesdeLineasPrefactura($post, $idFactura)
    {                                        
        
        $insRows = $this->facturarFilasProductosDocumento($post, $idFactura);
        if($insRows){
            $this->actualizarTotalesFactura($idFactura);
        }                            
        return $insRows;
          
    }

    private function facturarFilasProductosDocumento($post, $idFactura)
    {    
        $retorno = false;
        $cont = 0;   
        $contValid = 0;

        $arr = explode(",",$post['checkBoxesEnviar']); 
                      
        if(count($arr) > 0){
                 
            foreach ($post['numeroOrden'] as $key => $value) {

                if(isset($post['idArticulo'][$key]) && $arr[$key] == "si"){
                    
                    $contValid++;
    
                    $tmp = [];              
                    $tmp['idproducto'] = $post['idArticulo'][$key];
                    $tmp['descripcion'] = $this->ModelProductos->obtenerNombreProductoByNumero($post['idArticulo'][$key]);
                    if(isset($post['tipoDescripcion'][$key]) && $post['tipoDescripcion'][$key] == 'texto'){
                        $tmp['idproducto'] = 0;
                        $tmp['descripcion'] = $post['idArticulo'][$key];    
                    }
                    $descuento = (isset($post['descuentoArticulo'][$key]) && $post['descuentoArticulo'][$key]!= '' && $post['descuentoArticulo'][$key] > 0)? str_replace(",", ".", $post['descuentoArticulo'][$key]):0;
                    $tmp['descuento'] = $descuento;                    
                    $tmp['unidad'] = $post['unidadArticulo'][$key] ?? '';

                    $cantidad = ($post['cantidadArticulo'][$key] != '')? str_replace(",", ".", $post['cantidadArticulo'][$key]): 0;
                    $tmp['cantidad'] = $cantidad;
                    $precio = ($post['precioArticulo'][$key] != '')? str_replace(",", ".", $post['precioArticulo'][$key]): 0;
                    $tmp['precio'] = $precio;
                    $tmp['ivatipo'] = ($post['iva'][$key] != '')? $post['iva'][$key]: 0;
                    $subTotalAntesDscto = $cantidad * $precio;
                    $valorDscto = round($subTotalAntesDscto * $descuento/100,2);
                    $subTotal = $subTotalAntesDscto - $valorDscto;
                    $tmp['subtotal'] = $subTotal;                     
                    $tmp['idfactura'] = $idFactura;
                                                
        
                    $arrWhere = [];
                    
                        
                    $arraFields = $this->arrFieldsRowsCreate; 
    
                    $stringQueries = UtilsHelper::buildStringsInsertQueryNuevo2($tmp, $arraFields);

                    $ok = $stringQueries['ok'];
                    $strFields = $stringQueries['strFields'];
                    $strValues = $stringQueries['strValues'];
                    if($ok){
                        $insRow = $this->modeloBase->insertRow($this->tablaRows, $strFields, $strValues);
                        if($insRow){
                            $this->ModelFacturasDetalleCliente->actualizarEstadoIdFacturaEnPrefactura($idFactura,'facturado',$post['idFila'][$key]);
                            $cont++;
                        }
                    }                      
                                                              
                }
                
            }
    
            if($cont == $contValid){            
                $retorno = true;
            }

        }               
        return $retorno;       
    }    

    public function enviarIdFacturaParaEditar()
    {
        $respuesta['error'] = true;
        $respuesta['mensaje'] = 'No se puede encontrar la factura.';   
        if(isset($this->fetch['id']) && $this->fetch['id'] > 0){
            $_SESSION['idFacturaEditFactura'] = $this->fetch['id'];
            $respuesta['error'] = false;
            $respuesta['mensaje'] = '';   
        }
        print_r(json_encode($respuesta));   
    }


    public function traerFacturasClienteRelacionadas()
    {  
        $respuesta['error'] = true;
        $respuesta['mensaje'] = ERROR_DOESNT_EXIST;   
                    
        if(isset($this->fetch) && $this->fetch['id'] > 0) {            

            $idCliente = $this->ModelIncidencias->idClientePorIncidencia($this->fetch['id']);

            $facturas = $this->ModelFacturasCliente->obtenerFacturasPorIdCliente($idCliente);

            
            $select = TemplateHelperDocumento::buildSelectOptionsFacturasCliente($facturas);
            
            $respuesta['error'] = false;
            $respuesta['mensaje'] = '';
            $respuesta['facturas'] = $select;       
        }                         
        print_r(json_encode($respuesta));
    }    

    public function enviarEmailFactura1() 
    {        
        $respuesta['error'] = true;
        $respuesta['mensaje'] = 'No se puede ha podido enviar la factura.';   
        if(isset($this->fetch['id']) && $this->fetch['id'] > 0){            
            
            $respuesta['error'] = false;
            $respuesta['mensaje'] = '';   

                //$idIncidencia,$datos

                $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");
                
                $nombreRemitente = 'InfoMalaga';
                $emailRemitente = CUENTA_CORREO;
                $asunto = "Factura de servicios";
            
                    $usuariosAsignadosAlEquipo = $this->ModelIncidencias->obtenerCorreosUsuariosAsignadosNoSupervisor($datos['idequipo']);
                    $emailsDestino = [];
                    if (count($usuariosAsignadosAlEquipo)>0) {
                        
                        foreach ($usuariosAsignadosAlEquipo as $key) {
                            if (isset($key->correo) && $key->correo!='' && $key->recibemails ) {
                                $emailsDestino[] = $key->correo;

                                $fecha = date('d-m-Y H:i:s',strtotime($datos['creacion']));
                                $enlace = 'Haz click en el enlace para ver la solicitud.';
                                $contenido = 'Hemos registrado tu solicitud de servicio con fecha '.$fecha.'. El número de solicitud es '.$idIncidencia.'.';
                                $info = $idIncidencia.'/'.$key->id;
            
                                $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
                                $cambio = [$enlace, $contenido, $info];
                                $mensaje = str_replace($cambiar,$cambio,$plantilla);
                                
                                $message = html_entity_decode($mensaje);
                            
                                $tipoDoc = '';
                                $attachment = '';
                            
                                enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);
                                
                            }
        
                        }
                    }

        }
        print_r(json_encode($respuesta));  
        
    }

    public function enviarEmailFactura() 
    {

        $respuesta['error'] = true;
        $respuesta['mensaje'] = "Se ha producido un error y no se ha enviado el email.";

        if(trim($_POST['emailAsunto']) != '' && trim($_POST['emailMensaje']) != '' && isset($_POST['inputEmailSelected']) && count($_POST['inputEmailSelected']) > 0 && $_POST['idFacturaEnviar'] > 0){
                    
            $enviar = $this->enviarEmailDocumentoPdf($_POST);
            if($enviar){
                $respuesta['error'] = false;
                $respuesta['mensaje'] = 'Correo enviado';
                
                $envios = $this->modeloBase->getAllFieldsTablaByFieldsFilters(
                    'emails_clientes_facturas', 
                    ['iddoc' => $_POST['idFacturaEnviar'], 'tipodoc' => 'factura'], 
                    'fecha', 
                    'DESC'
                );

                $respuesta['html'] = TemplateHelperDocumento::buildHTMLListSentEmailsDocumento($envios);
            }

        }else{
            $respuesta['error'] = true;
            $respuesta['mensaje'] = "Faltan datos para enviar el email";
        }

        echo json_encode($respuesta);

    }


    private function enviarEmailDocumentoPdf($post)
    {
        $retorno = false;
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaEmail.php");     

        $nombreRemitente = 'Telesat';
        $emailRemitente = CUENTA_CORREO;
        $asunto = $post['emailAsunto'];
    

        $destinatarios = $post['inputEmailSelected'];
        $idFactura = $post['idFacturaEnviar'];
       
        $attachment = $this->generarPdfFacturaParaEmail($idFactura);
        
        $nombreFichero = "Factura_".$this->ModelFacturasCliente->nuFacturaPorIdFactura($idFactura)."_".strtotime("now").".pdf";
        $tmp['documento'] = $attachment;
        $tmp['nombreDocumento'] = $nombreFichero;
        $attachmentArray[] = $tmp;
                          
        $contenido = $post['emailMensaje'];
    
        $cambiar = ['{CONTENIDO}'];
        $cambio = [$contenido];
        $mensaje = str_replace($cambiar,$cambio,$plantilla);
        
        $message = html_entity_decode($mensaje);
            
        $tipoDoc = '';
            
        $envio = enviarEmail::enviarEmailDocumento($nombreRemitente, $emailRemitente, $destinatarios, $asunto, $message, $attachmentArray, $tipoDoc, $datos='');

        if ($envio) {
            $retorno = true;
            $tipoDocumento = 'factura';
            $this->guardarDatosEnvioFactura($idFactura, $tipoDocumento, $nombreFichero, $destinatarios, $asunto, $contenido, $nombreRemitente, $emailRemitente);
        }   

        return $retorno;    

    }    
    
    private function guardarDatosEnvioFactura($idDocumento, $tipoDocumento, $nombreFichero, $emailsDestino, $asunto, $message, $nombreRemitente, $emailRemitente)
    {
        date_default_timezone_set('Europe/Madrid');
        $arrValues['iddoc'] = $idDocumento;        
        $arrValues['fecha'] = date("Y-m-d H:i:s");
        $arrValues['tipodoc'] = $tipoDocumento;
        $arrValues['nomfichero'] = $nombreFichero;
        $arrValues['destinatarios'] = json_encode($emailsDestino);
        $arrValues['asunto'] = $asunto;
        $arrValues['mensaje'] = $message;
        $arrValues['correoremitente'] = $emailRemitente;
        $arrValues['nomremitente'] = $nombreRemitente;        

        $stringQueries = UtilsHelper::buildStringsInsertQuery($arrValues, $this->arrFieldsEmailSent);
            $ok = $stringQueries['ok'];
            $strFields = $stringQueries['strFields'];
            $strValues = $stringQueries['strValues'];
                       
            if($ok){
                $this->modeloBase->insertRow('emails_clientes_facturas', $strFields, $strValues);
            }    
    }

    public function obtenerListadoEnviosFactura()
    {
        $respuesta['error'] = true;
        $respuesta['mensaje'] = '<p>No se ha enviado esta factura por email.</p>';   
        if(isset($this->fetch['id']) && $this->fetch['id'] > 0){            

            $idFactura = $this->fetch['id'];            
            $envios = $this->modeloBase->getAllFieldsTablaByFieldsFilters(
                'emails_clientes_facturas', 
                ['iddoc' => $idFactura, 'tipodoc' => 'factura'], 
                'fecha', 
                'DESC'
            );

            if(isset($envios) && count($envios) > 0){
                $respuesta['error'] = false;
                $respuesta['mensaje'] = 'Aquí tiene los envíos de la factura.'; 
                $respuesta['html'] = TemplateHelperDocumento::buildHTMLListSentEmailsDocumento($envios);                
            }            
              
        }
        print_r(json_encode($respuesta));  
    }

    private function generarPdfFacturaParaEmail($idFactura)
    {                             
        $cabecera = $this->ModelFacturasCliente->obtenerDatosFacturaYCliente($idFactura);                  

        $datos['cabecera'] = $cabecera;
        $datos['detalle'] = $this->ModelFacturasDetalleCliente->obtenerFilasFactura($idFactura);        
        $datos['tipo'] = 'factura';
        $datos['tiporazonsocial'] = 'cliente';      
        
        $cuentasBancarias = $this->construirStringCuentasContables($cabecera->cuentas);
        $datos['cuentasBancarias'] = $cuentasBancarias;

        $pdf = generarPdf::documentoPDFParaEmail('P', 'A4', 'es', true, 'UTF-8', array(0, 0, 0, 0), true, 'documentos', 'factura.php', $datos);
        return $pdf;
    }


    public function calcularFechaVencimientoFacturaCliente()
    {
        $respuesta['error'] = true;
        $respuesta['mensaje'] = 'No es posible calcular la fecha de vencimiento.';

        if(isset($this->fetch) && isset($this->fetch['dias']) && $this->fetch['fecha'] != '') {
            $dias_cobro = (trim($this->fetch['dias']) != '')? trim($this->fetch['dias']): 0;
            $respuesta['error'] = false;
            $respuesta['mensaje'] = '';
            $respuesta['fechaVecimiento'] = DateTimeHelper::calcularFechaFin($this->fetch['fecha'], $dias_cobro);
        }                
        echo json_encode($respuesta);
    }
  
    public function calcularDiasCobroFacturaCliente()
    {   
        $respuesta['error'] = true;
        $respuesta['mensaje'] = 'No se pueden calcular los días de cobro.';

        if(isset($this->fetch) && isset($this->fetch['vencimiento']) && isset($this->fetch['fecha_factura_cliente'])) {            
            $respuesta['error'] = false;
            $respuesta['mensaje'] = '';
            $respuesta['dias_albaran_cliente'] = DateTimeHelper::calcularDiasEntreFechas($this->fetch['fecha_factura_cliente'], $this->fetch['vencimiento']);
        }                
        echo json_encode($respuesta);
    }

    public function consultarEstadoPagoFactura()
    {
        $respuesta['error'] = true;        
        $estadopago = false;
        if(isset($this->fetch) && isset($this->fetch['id']) ) {            
            $respuesta['error'] = false;
            $estado = $this->ModelFacturasCliente->estadoPagoFactura($this->fetch['id']);
            if($estado=='impagado'){
                $estadopago = 'pagado';
            }else if($estado=='pagado'){
                $estadopago = 'impagado';
            }            
            $respuesta['estadopago'] = $estadopago;
        }                
        echo json_encode($respuesta);
    }

    public function cambiarEstadoPagoFactura()
    {
        $respuesta['error'] = true;        
        $respuesta['mensaje'] = 'No se puede cambiar el estado de pago de esta factura.';
        if(isset($this->fetch) && isset($this->fetch['id']) && isset($this->fetch['estadopago']) && $this->fetch['estadopago'] != '' ) {            
            
            $upd = $this->ModelFacturasCliente->actualizarEstadoPagoFactura($this->fetch['id'], $this->fetch['estadopago']);
            if($upd){
                $respuesta['error'] = false;
                $respuesta['mensaje'] = 'Se ha actualizado el estado de pago de la factura corréctamente';
            }            
        }                
        echo json_encode($respuesta);
    }

    public function eliminarFactura() 
    {

        $respuesta['error'] = true;
        $respuesta['mensaje'] = "Se ha producido un error y no se ha eliminado la factura";

        if($_POST['id'] > 0){
            // Primero obtenemos los datos de la factura que se quiere eliminar
            $facturaActual = $this->ModelFacturasCliente->obtenerDatosFactura($_POST['id']);
            
            if($facturaActual) {
                // Obtenemos el año de la factura
                $anioFactura = date('Y', strtotime($facturaActual->fecha));

                $existenPosteriores = $this->ModelFacturasCliente->verificarSiExistenFacturasPosteriores($_POST['id'], $anioFactura, $facturaActual->numerointerno,  $facturaActual->rectificativa);              

                if($existenPosteriores > 0) {
                    $respuesta['error'] = true;
                    $respuesta['mensaje'] = "No se puede eliminar esta factura porque existen facturas posteriores en el mismo año";
                }else{

                    $cont=0;

                    $lineas = $this->ModelFacturasDetalleCliente->obtenerFilasFactura($_POST['id']);
                    if(!empty($lineas)){
                        foreach ($lineas as $l) {
                            if($this->ModelFacturasDetalleCliente->eliminarFilaFactura($l->id)){
                                $cont++;
                            }
                        }                
                    }
                    if($cont==count($lineas)){
                        if($this->ModelFacturasCliente->eliminarCabeceraFactura($_POST['id'])){
                            $respuesta['error'] = false;
                            $respuesta['mensaje'] = "Se eliminado la factura";
                        }                
                    }
                }

            }
        }
        echo json_encode($respuesta);

        /* if($_POST['id'] > 0){
            $cont=0;

            $lineas = $this->ModelFacturasDetalleCliente->obtenerFilasFactura($_POST['id']);
            if(!empty($lineas)){
                foreach ($lineas as $l) {
                    if($this->ModelFacturasDetalleCliente->eliminarFilaFactura($l->id)){
                        $cont++;
                    }
                }                
            }
            if($cont==count($lineas)){
                if($this->ModelFacturasCliente->eliminarCabeceraFactura($_POST['id'])){
                    $respuesta['error'] = false;
                    $respuesta['mensaje'] = "Se eliminado la factura";
                }                
            }
        }
        echo json_encode($respuesta); */
    }

}
