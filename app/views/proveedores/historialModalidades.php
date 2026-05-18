<div class="hidden overflow-x-hidden overflow-y-auto fixed inset-0 z-50 outline-none focus:outline-none justify-center items-center" id="historial-modalidad">
  <div class="relative w-auto my-6 mx-auto max-w-3xl">
    <!--content-->
    <div class="border-0 rounded-lg shadow-lg relative flex flex-col w-full bg-white outline-none focus:outline-none">
      <!--header-->
      <div class="flex items-start justify-between p-2 border-b border-solid border-blueGray-200 rounded-t">
        <h3 class="text-base font-semibold mr-2">
          Historial precio/mes
        </h3>
        <button class="ml-auto bg-transparent border-0 text-black opacity-50 float-right text-3xl leading-none font-semibold outline-none focus:outline-none cerrarHistorialModalidad" >
          <span class="bg-transparent text-black opacity-1 h-6 w-6 text-2xl block outline-none focus:outline-none">
            ×
          </span>
        </button>
      </div>
      <!--body-->
      <div class="relative px-6 py-1 flex-auto">      
        <span id="msgValidarModalidad" class="font-bold font-bold text-blue-600"></span>      
        <div class="flex flex-col space-y-5" id="bodyHistorialModalidad" style="height: 12rem;overflow-y:scroll;" >
          <table id="tablaHistorialModalidad" class="rounded-t-lg rounded-b-lg m-5 w-5/6 mx-auto bg-gradient-to-l text-xs md:text-sm">
            <thead>
              <tr class="text-center border-b-2 border-violeta-oscuri text-white bg-violeta-oscuro"><th class='p-1 text-center'>Mes</th><th class='p-1 text-center'>Año</th><th class='p-1 text-center'>Modalidad</th><th class='p-1'>Contratado</th></tr>
            </thead>
            <tbody></tbody>
          </table>
                  
        </div>


      </div>
      
      <!--footer-->
      <div class="flex items-center justify-end p-2 border-t border-solid border-blueGray-200 rounded-b">
        <button class="w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl font-medium text-xs md-text-sm text-white px-4 py-1 mr-2 cerrarHistorialModalidad">Cerrar</button>
      </div>

    </div>
  </div>
</div>
<div class="hidden opacity-25 fixed inset-0 z-40 bg-black" id="historial-modalidad-backdrop"></div>