<?php

class FormasDePago extends Controlador {

    private $id;
    private $formadepago;    
    private $estado;

    public function __construct() {
        
        if (!isset($_SESSION)) {
            session_start();
        }
        $this->controlPermisos();
        $this->ModelFormasDePago = $this->modelo('ModeloFormasDePago');        
        if(isset($_POST['id']) && $_POST['id'] > 0)
        {
            $this->id = $_POST['id'];
            $this->asignarPropiedadesFormaDePago();
        }    
    }

    private function asignarPropiedadesFormaDePago(){
        
        $datosClase = $this->ModelFormasDePago->obtenerDatosFormaDePagoPorId($this->id);
        if($datosClase){
            $this->formadepago = $datosClase->formadepago;            
            $this->estado = $datosClase->estado;    
        }
    }

    public function index() {
        $datos = [];
        $this->vista('formasDePago/formasDePago', $datos);
    }

    public function crearTablaFormasDePago()
    {

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $orden = $_POST['orden'];
            $tipoOrden = 'ASC';
        }

       
        
        $cond = '';
        $filaspagina = $filas * $pagina;
    
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
                    if ($key == 'Forma de pago') {
                        $cond .= "formadepago" . $y;
                    }                         
                    if ($key == 'estado') {
                        $cond .= "estado" . $y;
                    }                                   
                }                                    
            }            
        }            
        $datosRetorno = $this->ModelFormasDePago->obtenerFormasDePagoTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond);
       
        print(json_encode($datosRetorno));  
    }    

    public function totalRegistrosFormasDePago()
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
                    if ($key == 'Forma de pago') {
                        $cond .= "formadepago" . $y;
                    }     
                    if ($key == 'estado') {
                        $cond .= "estado" . $y;
                    }                                   
                } 
            }

            $contador = $this->ModelFormasDePago->totalRegistrosFormasDePagoBuscar($cond);

        }else{
            $contador = $this->ModelFormasDePago->totalRegistrosFormasDePago();
        }

        $cont = $contador->contador;        
        print_r($cont);
    }      

    public function registrarFormasDePago()
    {                
        if ($_POST['formadepago'] && trim($_POST['formadepago']) !='') {               
            
            $formadepago = trim($_POST['formadepago']);

            $datos = [
                'formadepago' => trim($formadepago)                
            ];
            if(!$this->ModelFormasDePago->obtenerFormaDePagoPorFormaDePago($formadepago)){
                $ins = $this->ModelFormasDePago->insertarNuevaFormaDePago($datos);
            
                if ($ins >0) {                
                    $_SESSION['message'] = 'Se ha registrado la forma de pago corréctamente.';
                }else{
                    $_SESSION['message'] = 'Ha ocurrido un error. No se ha podido registrar la forma de pago.';
                }
    
            }else{
                $_SESSION['message'] = 'Error. la forma de pago '.$formadepago.' ya existe.';
            }

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede registrar la forma de pago porque falta información.';
        }
        redireccionar('/FormasDePago');

    
    }

    private function permiteActualizarEliminarFormaDePago()
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
  
    public function actualizarFormasDePago()
    {                
        if ($this->id && $this->id>0 && trim($_POST['formadepago']) !='') {
            
            if($this->permiteActualizarEliminarFormaDePago()){

                $formadepago = trim($_POST['formadepago']);
             
                $datos = [
                    'id' => $this->id,
                    'formadepago' => trim($formadepago),                                      
                    'estado' => (isset($_POST['activo']) &&  $_POST['activo']==1)? 'activo':'inactivo' 
                ];              

                $upd = $this->ModelFormasDePago->actualizarDatosFormaDePago($datos);

                if ($upd && $upd >0) {                            
                        $_SESSION['message'] = 'Se ha actualizado el registro corréctamente.';
                }else{
                        $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro.';
                }
    
            }else{             
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar la forma de pago '.$this->formadepago.' porque ya está siendo utilizado en una transacción.';
            }

        }else{            
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro porque falta completar datos en el formulario.';
        }
        redireccionar('/FormasDePago');
        
    }

    public function eliminarFormasDePago()
    {              
        if(isset($this->id) && $this->id >0){

            if($this->permiteActualizarEliminarFormaDePago()){
                        
                $del = $this->ModelFormasDePago->eliminarFormaDePago($this->id);
                if ($del) {
                    $_SESSION['message'] = 'Se ha eliminado el registro corréctamente.';
                }else{
                    $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
                }

            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar la forma de pago '.$this->formadepago.' porque ya está siendo utilizado en una transacción.';
            }                

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
        }
        redireccionar('/FormasDePago');
                
    }

    


   
}