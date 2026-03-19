<?php

class Modalidades extends Controlador {

    public function __construct() {
        session_start();
        $this->controlPermisos();
        $this->ModelModalidades = $this->modelo('ModeloModalidades');    
    }

    public function index() {
        $datos = [];

        $this->vista('modalidades/modalidades', $datos);
    }

    public function crearTablaModalidades()
    {

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $orden = $_POST['orden'];
            $tipoOrden = $_POST['tipoOrden'];
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
                    if ($key == 'Nombre') {
                        $cond .= "nombre" . $y;
                    }
                    if ($key == 'Apellidos') {
                        $cond .= "apellidos" . $y;
                    }
                    if ($key == 'Email') {
                        $cond .= "correo" . $y;
                    }
                    if ($key == 'Teléfono') {
                        $cond .= "telefono" . $y;
                    }                           
                   
                }                                    
    
            }

            

        }
            
            $modalidades = $this->ModelModalidades->obtenerModalidadesTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond);
       
        print(json_encode($modalidades));  
    }    

    public function totalRegistrosModalidades()
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
                    if ($key == 'Nombre') {
                        $cond .= "nombre" . $y;
                    }
                    if ($key == 'Apellidos') {
                        $cond .= "apellidos" . $y;
                    }
                    if ($key == 'Email') {
                        $cond .= "correo" . $y;
                    }
                    if ($key == 'Teléfono') {
                        $cond .= "telefono" . $y;
                    }     

                }                                    
    
            }

            $contador = $this->ModelModalidades->totalRegistrosModalidadesBuscar($cond);

        }else{
            $contador = $this->ModelModalidades->totalRegistrosModalidades();
        }

        $cont = $contador->contador;        
        print_r($cont);
    }  

    public function registrarModalidad()
    {         
       
        if ($_POST['modalidad'] && $_POST['modalidad'] !='') {               
            
            $datos = [
                'modalidad' => trim($_POST['modalidad'])
            ];
            
            $ins = $this->ModelModalidades->insertarNuevaModalidad($datos);
            
            if ($ins >0) {                
                $_SESSION['message'] = 'Se ha registrado la modalidad corréctamente.';
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se ha podido registrar la nueva modalidad.';
            }

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede registrar el técnico porque falta información.';
        }
        redireccionar('/Modalidades');

    
    }

  
    public function actualizarModalidad()
    {            
        
        if ($_POST['idModalidadEdit'] && $_POST['idModalidadEdit']>0 && $_POST['modalidadEdit'] !='') {
            
            $datos = [
                'id' => $_POST['idModalidadEdit'],
                'modalidad' => trim($_POST['modalidadEdit']),
            ];
            
            $upd = $this->ModelModalidades->actualizarDatosModalidad($datos);

            if ($upd && $upd >0) {                            
                    $_SESSION['message'] = 'Se ha actualizado el registro corréctamente.';
            }else{
                    $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro.';                
            }


        }else{            
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro porque falta completar datos en el formulario.';
        }
        redireccionar('/Modalidades');
        
    }

    public function eliminarModalidad()
    {      
        
        if(isset($_POST['idModalidadDel']) && $_POST['idModalidadDel'] >0){
            
            $del = $this->ModelModalidades->eliminarModalidad($_POST['idModalidadDel']);
            if ($del) {
                $_SESSION['message'] = 'Se ha eliminado el registro corréctamente.';
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
            }
        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
        }
        redireccionar('/Modalidades');
                
    }

    


   
}