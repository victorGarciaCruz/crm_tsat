<div class="hidden overflow-x-hidden overflow-y-auto fixed inset-0 z-50 outline-none focus:outline-none justify-center items-center" id="editar-equipo">
  <div class="relative w-auto my-6 mx-auto max-w-3xl">
    <!--content-->
    <div class="border-0 rounded-lg shadow-lg relative flex flex-col w-full bg-white outline-none focus:outline-none">
      <!--header-->
      <div class="flex items-start justify-between p-3 border-b border-solid border-blueGray-200 rounded-t">
        <h3 class="text-xl font-semibold" id="tituloModalEquipo"></h3>
        <button class="p-1 ml-auto bg-transparent border-0 text-black opacity-50 float-right text-3xl leading-none font-semibold outline-none focus:outline-none cerrarModalEditEquipo" >
          <span class="bg-transparent text-black opacity-1 h-6 w-6 text-2xl block outline-none focus:outline-none">
            ×
          </span>
        </button>
      </div>
      <input type="hidden" name="idEquipo" id="idEquipo">
      <!--body viene del controlador-->
      <div id="bodyModalEditarEquipo" class="relative p-3 flex-auto" >
        
      </div>
   

    </div>
  </div>
</div>

<div class="hidden opacity-25 fixed inset-0 z-40 bg-black" id="editar-equipo-backdrop"></div>