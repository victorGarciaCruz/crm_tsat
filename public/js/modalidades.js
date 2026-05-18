if(window.location.pathname.includes('/Modalidades')){   
    
    function toggleModal(modal_id) {
            
        document.getElementById(modal_id).classList.toggle("hidden");
        document.getElementById(modal_id + "-backdrop").classList.toggle("hidden");
        document.getElementById(modal_id).classList.toggle("flex");
        document.getElementById(modal_id + "-backdrop").classList.toggle("flex");

    }

    $(document).on('click', '#nuevaModalidad', function (e) {
        e.preventDefault();
        toggleModal('crear-modalidad');        
    });

    $(document).on('click', '.cerrarModalCrearModalidad', function (e) {
        e.preventDefault();
        $('#modalidad').val('');
        toggleModal('crear-modalidad');
    });    

    $(document).on('click', '.editar', function (e) {
        e.preventDefault();
        var filaModalidad = $(this).closest('tr');        
        id = parseInt(filaModalidad.find('td:eq(0)').text()); 
        modalidad = filaModalidad.find('td:eq(1)').text(); 

        $('#idModalidadEdit').val(id);
        $('#modalidadEdit').val(modalidad);
        toggleModal('editar-modalidad');
    });

    $(document).on('click', '.cerrarModalEditarModalidad', function (e) {
        e.preventDefault();
        $('#idModalidadEdit').val('');
        $('#modalidadEdit').val('');
        toggleModal('editar-modalidad');
    });



    $(document).on('click', '.eliminar', function (e) {
        e.preventDefault();
        var filaModalidad = $(this).closest('tr');        
        id = parseInt(filaModalidad.find('td:eq(0)').text()); 
        modalidad = filaModalidad.find('td:eq(1)').text(); 

        $('#idModalidadDel').val(id);
        $('#preguntaEliminar').html('¿Está seguro(a) de eliminar la modalidad '+ modalidad +' ?');
        toggleModal('eliminar-modalidad');
    });

    $(document).on('click', '.cerrarModalEliminarrModalidad', function (e) {     
        e.preventDefault();  
        $('#idModalidadDel').val('');
        $('#preguntaEliminar').html('');
        toggleModal('eliminar-modalidad');
    });







    $(document).on('click', '.butonCerrarAlerta', function (event) {
        cerrarAlerta(event);
    })



   

    function cerrarAlerta(event){
        let element = event.target;
        while(element.nodeName !== "BUTTON"){
          element = element.parentNode;
        }
        element.parentNode.parentNode.removeChild(element.parentNode);
    }



}


