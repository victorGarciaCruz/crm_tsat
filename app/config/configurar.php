<?php

// configuracion acceso a base de datos producción





define('DB_HOST','localhost:3307');
define('DB_USUARIO','root');
define('DB_PASSWORD','');
define('DB_NOMBRE','crm_telesat');


// Ruta de la aplicacion
define('RUTA_APP', dirname(dirname(__FILE__)));

define('RUTA_URL','http://localhost:8080/crm_tsat');

// NOMBRE DEL SITIO
define('NOMBRE_SITIO', 'TELESAT');
// RUTA CONTROL PERMISOS
define("RUTA_PERMISOS","/crm_tsat");
//define("RUTA_PERMISOS","");
// COLOR DEL FONDO DEL NAV BAR
define('BG_NAVBAR', 'bg-violeta-oscuro');
// COLOR DEL FONDO DEL SIDE BAR
define('BG_SIDEBAR', 'bg-blue-500');
// COLOR DE FONDO SUBMENU HOVER
define('BG_SUBMENU_HOVER', 'bg-blue-800');
//Ruta para subida de ficheros:
define("DOCUMENTOS_PRIVADOS", RUTA_APP."/documentos/");

//Ruta para subir ficheros en public en local
define("DOCS_INCIDENCIAS", $_SERVER['DOCUMENT_ROOT'] . '/crm_tsat/public/documentos/Incidencias/');
//Ruta para subir ficheros equipos en public en local
define("DOCS_EQUIPOS", $_SERVER['DOCUMENT_ROOT'] . '/crm_tsat/public/documentos/Equipos/');
//Ruta para subir ficheros trabajos terminados en public en produccion
define("DOCS_TRABAJOS_TERMINADOS", $_SERVER['DOCUMENT_ROOT'] . '/crm_tsat/public/documentos/TrabajosTerminados/');

//slogan del logo
define("SLOGAN", "");

define("NOMBRE_FISCAL_INFOMALAGA", "TECNOLOGIAS APLICADAS TELESAT SL");
define("NIF_INFOMALAGA", "B93289254");
define("DIRECCION_INFOMALAGA", "Avenida Joan Miró, Nº 37");
define("CODIGO_POSTAL_INFOMALAGA", "29620");
define("LOCALIDAD_INFOMALAGA", "Torremolinos");
define("PROVINCIA_INFOMALAGA", "Màlaga ");
define("TELEFONO", "952388790 | 607766741");
define("CUENTA_BANCARIA", "ES63-0081-0255-12-0001586168");

define("TELEFONO_FIJO", "952388790");
define("TELEFONO_MOVIL", "607766741");


//cuenta correo para envío
define("CUENTA_CORREO", "automatizotunegocioinfo@gmail.com");

//cuenta correo administración infomálaga 1
define("CUENTA_CORREOADMINISTRACION1", "");

//cuenta correo administración infomálaga 2
define("CUENTA_CORREOADMINISTRACION2", "");

//password correo
define("PASSWORD_CORREO", "arfgqipefinaibxx");

//host correo
define("HOST_CORREO", "smtp.gmail.com");

//puerto config correo
define("PUERTO", 465); //test

//protocolo
define("PROTOCOLO", "ssl"); //test


/*
//cuenta correo para envío
define("CUENTA_CORREO", "info@instalacionestelesat.es");

//cuenta correo administración infomálaga 1
define("CUENTA_CORREOADMINISTRACION1", "");

//cuenta correo administración infomálaga 2
define("CUENTA_CORREOADMINISTRACION2", "");

//password correo
define("PASSWORD_CORREO", "Correo1@53521");

//host correo
define("HOST_CORREO", "instalacionestelesat-es.correoseguro.dinaserver.com");

//puerto config correo
define("PUERTO", 587); //produccion

//protocolo
define("PROTOCOLO", "tls"); //produccion
*/


define("TIPO_IVA_DEFAULT",21);



//Tipos de error
define("ERROR_CREACION", "Se ha producido un error y no se ha creado el registro.");
define("OK_CREACION", "Se ha creado el registro corréctamente.");
define("ERROR_ACTUALIZACION", "Se ha producido un error y no se ha guardado el registro.");
define("OK_ACTUALIZACION", "Se ha actualizado el registro corréctamente");
define("ERROR_ELIMINACION", "No se ha eliminado el registro.");
define("OK_ELIMINACION", "Se ha eliminado el registro.");
define("OK_GUARDADO", "Se han guardado los datos corréctamente.");
define("ERROR_GUARDADO", "Se ha producido un error y no se han guardado los datos.");
define("ERROR_FORM_INCOMPLETO", "No se puede guardar el registro porque faltan datos en el formulario.");
define("ERROR_DOESNT_EXIST", "No hay datos para la consulta");

define("EMPRESA", "TELESAT");

define("GARANTIA", "La Garantía de la instalación es de un mes y del material es de dos años, siempre y cuando no sea manipulada por personal ajeno a nuestra empresa. ESTA FACTURA NO INCLUYE MANO DE OBRAPOSTERIOR ASU INSTALACIÓN");

define("INSCRIPCION", "Tecnologías Aplicadas Telesat S.L. inscrita en el Registro Mercantil Tomo 5209, Libro 4116, Folio 98, Hoja MA-120725, Inscripción 1");