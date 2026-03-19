<?php

class TiposIva extends Controlador {

    private $id;
    private $tipoiva;
    private $activo;

    public function __construct() {
        
        if (!isset($_SESSION)) {
            session_start();
        }
        $this->controlPermisos();
        $this->ModelTiposIva = $this->modelo('ModeloTiposIva');    
        $this->ModelProductos = $this->modelo('ModeloProductos');
        if(isset($_POST['id']) && $_POST['id'] > 0)
        {
            $this->id = $_POST['id'];
            $this->asignarPropiedadesTipoIva();
        }    
    }

    private function asignarPropiedadesTipoIva(){
        
        $datosIva = $this->ModelTiposIva->obtenerDatosTipoIvaPorId($this->id);
        if($datosIva){
            $this->tipoiva = $datosIva->tipoiva;
            $this->activo = $datosIva->activo;    
        }
    }

    public function index() {
        $datos = [];
        $this->vista('tiposIva/tiposIva', $datos);
    }

    public function crearTablaTiposIva()
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
                    if ($key == 'Tipo IVA (%)') {
                        $cond .= "tipoiva" . $y;
                    }      
                    if ($key == 'estado') {
                        $cond .= "activo" . $y;
                    }                                   
                }                                    
            }            
        }            
        $tiposIva = $this->ModelTiposIva->obtenerTiposIvaTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond);
       
        print(json_encode($tiposIva));  
    }    

    public function totalRegistrosTiposIva()
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
                    if ($key == 'Tipo IVA (%)') {
                        $cond .= "tipoiva" . $y;
                    }                
                    if ($key == 'estado') {
                        $cond .= "activo" . $y;
                    }                        
                }                                    
    
            }

            $contador = $this->ModelTiposIva->totalRegistrosTiposIvaBuscar($cond);

        }else{
            $contador = $this->ModelTiposIva->totalRegistrosTiposIva();
        }

        $cont = $contador->contador;        
        print_r($cont);
    }      

    public function registrarTiposIva()
    {         
       
        if ($_POST['tipoiva'] && trim($_POST['tipoiva']) !='') {               
            
            $tipoIva = trim($_POST['tipoiva']);

            $datos = [
                'tipoiva' => trim($tipoIva)
            ];
            if(!$this->ModelTiposIva->obtenerTipoIvaPorTipo($tipoIva)){
                $ins = $this->ModelTiposIva->insertarNuevaTipoIva($datos);
            
                if ($ins >0) {                
                    $_SESSION['message'] = 'Se ha registrado el tipo iva corréctamente.';
                }else{
                    $_SESSION['message'] = 'Ha ocurrido un error. No se ha podido registrar el nuevo tipo iva.';
                }
    
            }else{
                $_SESSION['message'] = 'Error. El tipo IVA '.$tipoIva.'% ya existe.';
            }

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede registrar el tipo de Iva porque falta información.';
        }
        redireccionar('/TiposIva');

    
    }

    private function permiteActualizarEliminartipoIva()
    {
        $tipoiva = $this->ModelTiposIva->obtenerTipoIvaPorId($this->id);
        if($tipoiva){
            //verificar en productos y facturas
            $cont_facturas = 0; //FALTA CONSTRUIR CUANDO ESTÉ HECHO EL MODELO FACTURAS
            $cont_productos = $this->ModelProductos->buscarProdutosPorTipoIva($tipoiva);
            if($cont_productos==0 && $cont_facturas==0){
                return true;
            }
        }
        return false;
       
    }
  
    public function actualizarTiposIva()
    {                
        if ($this->id && $this->id>0 && trim($_POST['tipoIvaEdit']) !='') {
            
            if($this->permiteActualizarEliminartipoIva()){

                $tipoIvaEdit = trim($_POST['tipoIvaEdit']);

                $datos = [
                    'id' => $this->id,
                    'tipoiva' => $tipoIvaEdit
                ];

                $upd = $this->ModelTiposIva->actualizarDatosTipoIva($datos);

                if ($upd && $upd >0) {                            
                        $_SESSION['message'] = 'Se ha actualizado el registro corréctamente.';
                }else{
                        $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro.';
                }
    
            }else{             
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el tipo IVA '.$this->tipoiva.'% porque ya está siendo utilizado en una transacción.';
            }

        }else{            
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro porque falta completar datos en el formulario.';
        }
        redireccionar('/TiposIva');
        
    }

    public function eliminarTiposIva()
    {              
        if(isset($this->id) && $this->id >0){

            if($this->permiteActualizarEliminartipoIva()){
                        
                $del = $this->ModelTiposIva->eliminarTipoIva($this->id);
                if ($del) {
                    $_SESSION['message'] = 'Se ha eliminado el registro corréctamente.';
                }else{
                    $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
                }

            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el tipo IVA '.$this->tipoiva.'% porque ya está siendo utilizado en una transacción.';
            }                

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
        }
        redireccionar('/TiposIva');
                
    }

    


   
}