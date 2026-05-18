<?php

class CuentasBancarias extends Controlador {

    private $id;
    private $numerocuenta;
    private $banco;
    private $estado;

    public function __construct() {
        
        if (!isset($_SESSION)) {
            session_start();
        }
        $this->controlPermisos();
        $this->ModelCuentasBancarias = $this->modelo('ModeloCuentasBancarias');        
        if(isset($_POST['id']) && $_POST['id'] > 0)
        {
            $this->id = $_POST['id'];
            $this->asignarPropiedadesCuentaBancaria();
        }    
    }

    private function asignarPropiedadesCuentaBancaria(){
        
        $datosIva = $this->ModelCuentasBancarias->obtenerDatosCuentaBancariaPorId($this->id);
        if($datosIva){
            $this->numerocuenta = $datosIva->numerocuenta;
            $this->banco = $datosIva->banco;
            $this->estado = $datosIva->estado;    
        }
    }

    public function index() {
        $datos = [];
        $this->vista('cuentasBancarias/cuentasBancarias', $datos);
    }

    private function mapearCampoOrdenCuentaBancaria($campoVisible) {
        $mapa = [
            'Nº'     => 'id',
            'IBAN'   => 'numerocuenta',
            'banco'  => 'banco',
            'estado' => 'estado'
        ];
        return $mapa[$campoVisible] ?? $campoVisible;
    }

    private function construirClausulaOrderByCuentasBancarias($ordenMultipleJson, $ordenSimple, $tipoSimple) {
        // 1. Si hay orden múltiple (JSON con array de criterios), se usa
        if (!empty($ordenMultipleJson)) {
            $ordenes = json_decode($ordenMultipleJson, true);
            if (is_array($ordenes) && count($ordenes) > 0) {
                $sentencias = [];
                foreach ($ordenes as $item) {
                    $campoVisible = $item['campo'];
                    $direccion = (strtoupper($item['dir']) === 'DESC') ? 'DESC' : 'ASC';
                    $campoSQL = $this->mapearCampoOrdenCuentaBancaria($campoVisible);
                    $sentencias[] = "$campoSQL $direccion";
                }
                return implode(", ", $sentencias);
            }
        }

        // 2. Si no hay orden múltiple válido, usar el orden simple (el tradicional)
        if (!empty($ordenSimple)) {
            return $ordenSimple;
        }

        // 3. Si todo está vacío, retornamos cadena vacía (luego se aplicará un orden por defecto)
        return "";
    }



   public function crearTablaCuentasBancarias()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $ordenMultiple = isset($_POST['ordenMultiple']) ? urldecode($_POST['ordenMultiple']) : '';
            $ordenSimple   = isset($_POST['orden']) ? $_POST['orden'] : '';
            $tipoSimple    = isset($_POST['tipoOrden']) ? $_POST['tipoOrden'] : 'ASC';
        }

        $cond = '';
        $filaspagina = $filas * $pagina;

        // Determinar parámetros de orden según si hay orden múltiple
        if (!empty($ordenMultiple)) {
            // Caso orden múltiple: construir cláusula completa y tipo vacío
            $clausulaOrder = $this->construirClausulaOrderByCuentasBancarias($ordenMultiple, $ordenSimple, $tipoSimple);
            if (empty($clausulaOrder)) {
                $clausulaOrder = "id DESC"; // orden por defecto
            }
            $ordenFinal = $clausulaOrder;
            $tipoFinal = '';
        } else {
            // Caso orden simple: usar los valores originales
            $ordenFinal = $ordenSimple;
            $tipoFinal = $tipoSimple;
        }

        // Lógica de búsqueda ORIGINAL (sin cambios)
        if ($buscar != "") {
            $datos = json_decode($buscar);
            $tamanio = count((array) $datos);
            if ($tamanio > 0) {
                $cont = 0;
                $cond .= " AND  (";
                foreach ($datos as $key => $value) {
                    $cont++;
                    $y = ($cont < $tamanio) ? " LIKE '%$value%' AND " : " LIKE '%$value%' ) ";
                    if ($key == 'Nº') {
                        $cond .= "id" . $y;
                    }
                    if ($key == 'IBAN') {
                        $cond .= "numerocuenta" . $y;
                    }
                    if ($key == 'banco') {
                        $cond .= "banco" . $y;
                    }
                    if ($key == 'estado') {
                        $cond .= "estado" . $y;
                    }
                }
            }
        }

        $ctasBancarias = $this->ModelCuentasBancarias->obtenerCuentasBancariasTablaClassBuscar($filas,$ordenFinal,$filaspagina,$tipoFinal,$cond);
        print(json_encode($ctasBancarias));
    }    

    public function totalRegistrosCuentasBancarias()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
        }
        
        $cond = '';        
    
        if ($buscar != "") {               
            
            $datos = json_decode($buscar);
            
            $tamanio = count((array) $datos);
            if ($tamanio > 0) {
                                
                $cont = 0;
                $cond .= " AND  (";
                foreach ($datos as $key => $value) {
    
                    $cont++;                   
                    
                    if ($cont < ($tamanio) ) {                    
                        $y =  " LIKE " . "'%$value%'" . " AND ";
                    } else {                    
                        $y =  " LIKE " . "'%$value%'" . ") ";
                    }
                    if ($key == 'Nº') {
                        $cond .= "id" . $y;
                    }
                    if ($key == 'IBAN') {
                        $cond .= "numerocuenta" . $y;
                    }     
                    if ($key == 'banco') {
                        $cond .= "banco" . $y;
                    }  
                    if ($key == 'estado') {
                        $cond .= "estado" . $y;
                    }                                   
                } 
            }

            $contador = $this->ModelCuentasBancarias->totalRegistrosCuentasBancariasBuscar($cond);

        }else{
            $contador = $this->ModelCuentasBancarias->totalRegistrosCuentasBancarias();
        }

        $cont = $contador->contador;        
        print_r($cont);
    }      

    public function registrarCuentasBancarias()
    {         
       
        if ($_POST['numerocuenta'] && trim($_POST['numerocuenta']) !='') {               
            
            $numerocuenta = trim($_POST['numerocuenta']);

            $datos = [
                'numerocuenta' => trim($numerocuenta),
                'banco' => (trim($_POST['banco'])!= '')? trim($_POST['banco']): ''
            ];
            if(!$this->ModelCuentasBancarias->obtenerCuentaBancariaPorNumeroCuenta($numerocuenta)){
                $ins = $this->ModelCuentasBancarias->insertarNuevaCuentaBancaria($datos);
            
                if ($ins >0) {                
                    $_SESSION['message'] = 'Se ha registrado la cuenta bancaria corréctamente.';
                }else{
                    $_SESSION['message'] = 'Ha ocurrido un error. No se ha podido registrar la cuenta bancaria.';
                }
    
            }else{
                $_SESSION['message'] = 'Error. la cuenta bancaria '.$numerocuenta.' ya existe.';
            }

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede registrar la cuenta bancaria porque falta información.';
        }
        redireccionar('/CuentasBancarias');

    
    }

    private function permiteActualizarEliminarCuentaBancaria()
    {
        //falta verificar en factuas si dicha cuenta está siendo utilizada x id de cuenta
        $cuenta = true;
        if($cuenta){            
            $cont_facturas = 0; //FALTA CONSTRUIR CUANDO ESTÉ HECHO EL MODELO FACTURAS            
            if($cont_facturas==0){
                return true;
            }
        }
        return false;
       
    }
  
    public function actualizarCuentasBancarias()
    {                
        if ($this->id && $this->id>0 && trim($_POST['numerocuenta']) !='') {
            
            if($this->permiteActualizarEliminarCuentaBancaria()){

                $numerocuenta = trim($_POST['numerocuenta']);

             

                $datos = [
                    'id' => $this->id,
                    'numerocuenta' => trim($numerocuenta),
                    'banco' => (trim($_POST['banco'])!= '')? trim($_POST['banco']): '',                    
                    'estado' => (isset($_POST['activo']) &&  $_POST['activo']==1)? 'activo':'inactivo' 
                ];

              

                $upd = $this->ModelCuentasBancarias->actualizarDatosCuentaBancaria($datos);

                if ($upd && $upd >0) {                            
                        $_SESSION['message'] = 'Se ha actualizado el registro corréctamente.';
                }else{
                        $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro.';
                }
    
            }else{             
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar la cuenta bancaria '.$this->tipoiva.' porque ya está siendo utilizado en una transacción.';
            }

        }else{            
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro porque falta completar datos en el formulario.';
        }
        redireccionar('/CuentasBancarias');
        
    }

    public function eliminarCuentasBancarias()
    {              
        if(isset($this->id) && $this->id >0){

            if($this->permiteActualizarEliminarCuentaBancaria()){
                        
                $del = $this->ModelCuentasBancarias->eliminarCuentaBancaria($this->id);
                if ($del) {
                    $_SESSION['message'] = 'Se ha eliminado el registro corréctamente.';
                }else{
                    $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
                }

            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar la cuenta bancaria '.$this->tipoiva.' porque ya está siendo utilizado en una transacción.';
            }                

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
        }
        redireccionar('/CuentasBancarias');
                
    }

    


   
}