export class creartabla {
    ruta = "";
    destino = "";
    filas = 30;
    pagina = "";
    campoOrden = "";
    tipoOrden = "";
    campoOrdenInicial = "";
    tipoOrdenInicial = "";
    rutaTotalRegistros = "";
    destinobuscador = "";
    busqueda = "";
    segmento = [];
    contador = 0;
    clasesTabla = "";
    destinoPaginador = "";
    boton = [];
    rutaApp = "";
    id = "";
    anio = "";

    datosActuales = []; // Guardar los 30 registros actuales para ordenar en JS
    // Criterios de orden (array para soportar orden multiple)
    sortCriteria = []; // [{campo: 'Nº', dir: 'ASC'}]
    // Guardar estado de sortCriteria antes de empezar a ordenar una columna (para restaurar al volver a DEFAULT)
    sortStateBeforeSort = {}; // {campo: [{campos ordenados antes}]}

    // ======= NUEVO: MAPEO DE COLUMNAS PARA ORDENAMIENTO (si necesita alias SQL)
    mapaColumnasSQL = {
        "Nº": "inc.id",
        "Creación": "inc.creacion",
        "Usuario": "usu.nombre",
        "Cliente": "cli.nombre",
        "Sucursal": "suc.nombre",
        "Equipo": "equ.nombre",
        "Estado": "inc.estado",
        "Técnicos": "inc.nombrestecnicos",
        "Agendado": "inc.fechahora",
        "Fact/Ppto": "inc.nomestadofactppto",
        "Atención": "inc.play",
        "USUARIO": "usu.nombre",
        "CLIENTE": "cli.nombre",
        "SUCURSAL": "suc.nombre",
        "EQUIPO": "equ.nombre",
        "CREACIÓN": "inc.creacion",
        "ESTADO": "inc.estado",
        "TÉCNICOS": "inc.nombrestecnicos"
    };

    constructor(ruta, destino, orden, tipoOrden, pagina,
        destinobuscador, rutaTotalRegistros, segmento,
        clasesTabla, destinoPaginador, boton, rutaApp, id, anio) {

        this.ruta = ruta;
        this.rutaApp = rutaApp;
        this.id = id;
        this.anio = anio;
        this.destino = destino;
        this.campoOrden = orden;
        this.tipoOrden = tipoOrden;
        // NUEVO: Guardar valores iniciales para reiniciar al cambiar página
        this.campoOrdenInicial = orden;
        this.tipoOrdenInicial = tipoOrden;
        this.pagina = pagina;
        this.rutaTotalRegistros = rutaTotalRegistros;
        this.destinobuscador = destinobuscador;
        this.segmento = segmento;
        this.clasesTabla = clasesTabla;
        this.destinoPaginador = destinoPaginador;
        this.boton = boton;

        this.buscador(this.destinobuscador, this.segmento);

        this.tabla(
            this.ruta, this.destino, this.busqueda,
            this.campoOrden, this.tipoOrden,
            this.filas, this.pagina,
            this.clasesTabla, this.boton,
            this.rutaApp, this.id, this.anio
        );

        this.paginador(
            this.rutaTotalRegistros,
            this.filas,
            this.pagina,
            this.destinoPaginador,
            this.busqueda,
            this.id,
            this.anio
        );
    }

    rendertabla(busqueda = "", filas = 30, pagina = 0,
        campoOrdenar = this.campoOrden,
        tipoOrdenParam = this.tipoOrden,
        clases = this.clasesTabla,
        destinopaginador = this.destinoPaginador,
        boton = this.boton) {

        // CORRECCIÓN: Actualizar las propiedades de la clase para mantener sincronizado el estado
        this.campoOrden = campoOrdenar;
        this.tipoOrden = tipoOrdenParam;

        this.tabla(
            this.ruta, this.destino, busqueda,
            campoOrdenar, tipoOrdenParam,
            filas, pagina, clases,
            boton, this.rutaApp, this.id, this.anio
        );

        this.paginador(
            this.rutaTotalRegistros,
            filas, pagina,
            destinopaginador,
            busqueda,
            this.id, this.anio
        );
    }

    // Establecer criterio de orden (mantiene orden multiple)
    setSortCriterion(campo, dir) {
        // dir: 'ASC' | 'DESC' | 'DEFAULT'
        if (!campo) return;
        // remover si DEFAULT
        if (dir === 'DEFAULT') {
            this.sortCriteria = this.sortCriteria.filter(c => c.campo !== campo);
        } else {
            // si ya existe actualizar, si no añadir al final
            const idx = this.sortCriteria.findIndex(c => c.campo === campo);
            if (idx >= 0) {
                this.sortCriteria[idx].dir = dir;
            } else {
                this.sortCriteria.push({ campo, dir });
            }
        }
        // actualizar campoOrden y tipoOrden para compatibilidad con código previo
        if (this.sortCriteria.length > 0) {
            this.campoOrden = this.sortCriteria[0].campo;
            this.tipoOrden = this.sortCriteria[0].dir;
        } else {
            this.campoOrden = this.campoOrdenInicial || this.campoOrden;
            this.tipoOrden = this.tipoOrdenInicial || this.tipoOrden;
        }
    }

    // Devuelve un valor comparable para un campo (soporta Nº y Creación como ejemplos)
    _valorComparable(campo, valor) {
        if (valor === null || valor === undefined) return '';
        if (campo === 'Nº') {
            return parseInt(String(valor).replace(/\D/g, '')) || 0;
        }
        if (campo === 'Creación' || campo === 'CREACIÓN') {
            // intentar parsear formato d/m/Y o Y-m-d
            const s = String(valor).trim();
            if (s.indexOf('/') !== -1) {
                const parts = s.split('/');
                if (parts.length === 3) {
                    // d/m/Y -> Y-m-d
                    return new Date(parts[2] + '-' + parts[1] + '-' + parts[0]).getTime() || 0;
                }
            }
            const d = new Date(s);
            return d.getTime() || 0;
        }
        // Texto
        return String(valor).toLowerCase();
    }

    // Ordenar por criterios múltiples en memoria
    ordenarDatosEnMemoria() {
        if (!this.datosActuales || this.datosActuales.length === 0) return;
        const criterios = this.sortCriteria.slice(); // copia
        if (criterios.length === 0) return; // nada que ordenar

        this.datosActuales.sort((a, b) => {
            for (let i = 0; i < criterios.length; i++) {
                const { campo, dir } = criterios[i];
                const valA = this._valorComparable(campo, a[campo]);
                const valB = this._valorComparable(campo, b[campo]);

                if (typeof valA === 'number' && typeof valB === 'number') {
                    if (valA < valB) return dir === 'ASC' ? -1 : 1;
                    if (valA > valB) return dir === 'ASC' ? 1 : -1;
                } else {
                    if (valA < valB) return dir === 'ASC' ? -1 : 1;
                    if (valA > valB) return dir === 'ASC' ? 1 : -1;
                }
                // si igual, seguir al siguiente criterio
            }
            return 0;
        });
    }

    // Redibujar tbody con los datos actuales (usado al ordenar en memoria)
    redibujarTabla(datos, destino, clases, boton, rutaApp) {
        let contenido = "";

        for (var i = 0; i < datos.length; i++) {

            var titulos = Object.keys(datos[i]);

            contenido += `\n                    <tr class="rows">`;

            for (var j in titulos) {

                //estilos cuando se manejan estado
                if (titulos[j] == 'Estado') {
                    var claseTd = "";

                    var nombreEstado = '';
                    if (datos[i][titulos[j]] == 'pendiente') {
                        nombreEstado = 'pendiente';
                        claseTd = 'font-bold text-white bg-red-600 text-center border rounded-lg p-1';
                    } else if (datos[i][titulos[j]] == 'en curso') {
                        nombreEstado = 'en curso';
                        claseTd = 'font-bold text-white bg-yellow-400 text-center border rounded-lg p-1';
                    } else if (datos[i][titulos[j]] == 'terminada') {
                        nombreEstado = 'terminada';
                        claseTd = 'font-bold text-white bg-green-600 text-center border rounded-lg p-1';
                    } else if (datos[i][titulos[j]] == 'terminadasinvalorar') {
                        nombreEstado = 'terminada';
                        claseTd = 'font-bold text-white bg-green-600 text-center border rounded-lg p-1';
                    }
                    contenido += `\n                        <td class="px-1 py-2 border-b border-gray-200 bg-white text-xs 3xl:text-sm"><div class="${claseTd}">${nombreEstado}</div></td>`;

                } else if (titulos[j] == 'Fact/Ppto') {
                    let claseTdFact = "";
                    let nombreEstado = datos[i][titulos[j]];
                    let colortexto = '';
                    if (datos[i][titulos[j]] == 'facturar' || datos[i][titulos[j]] == 'presupuestar' || datos[i][titulos[j]] == 'aceptado' || datos[i][titulos[j]] == 'presupuestado' || datos[i][titulos[j]] == 'facturado' || datos[i][titulos[j]] == 'rechazado' || datos[i][titulos[j]] == 'FParc') {
                        colortexto = 'text-white'
                        claseTdFact = ` ${datos[i][titulos[j]]} border rounded-lg p-1`;
                    } else if (datos[i][titulos[j]] == 'sin estado') {
                        nombreEstado = '';
                    }

                    contenido += `\n                        <td class="px-1 py-2 border-b border-gray-200 bg-white text-xs 3xl:text-sm">\n                            <div class="${claseTdFact} text-center clickestado font-bold ${colortexto}" data-tipo=${datos[i][titulos[j]]}>${nombreEstado}</div>\n                        </td>`;

                } else if (titulos[j] == 'Atención') {

                    contenido += `\n                        <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-center">${datos[i][titulos[j]]}</td>`;

                } else if (titulos[j] == 'verhorascliente') {

                    contenido += `\n                    <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-sm"></td>`;
                } else if (titulos[j] == 'idTecnico') {

                    contenido += `\n                    <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-sm">${datos[i][titulos[j]]}</td>`;
                } else if (titulos[j] == 'idusuario') {

                    contenido += `\n                    <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-sm">${datos[i][titulos[j]]}</td>`;

                } else if (titulos[j] == 'pktabla') {

                    contenido += `\n                    <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-sm">${datos[i][titulos[j]]}</td>`;

                } else {

                    contenido += `\n                    <td class="px-1 py-2 border-b border-gray-200 bg-white text-xs 3xl:text-sm text-center">${datos[i][titulos[j]]}</td>`;
                }

            }

            if (boton != "") {
                contenido += `\n                        <td class="botones px-2 py-2 border-b border-gray-200 bg-white text-sm"><div class="flex">`;

                //LISTADO DE BOTONES
                for (let x = 0; x < boton.length; x++) {

                    if (boton[x] == 'pdffacturafila') {
                        contenido += `<a class="mr-1 pdffila text-gray-500 cursor-pointer" title="PDF" data-index="${datos[i]['Nº']}"><i class="fa fa-file-pdf" style="font-size: 1.25rem;"></i></a>`;
                    }

                    if (boton[x] == 'editar') {
                        contenido += `<a href="" class="mr-1 editar" title="Editar"><i class="fas fa-edit mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></a>`;
                    }
                    if (boton[x] == 'ver') {
                        contenido += `\n                        <div class=""><form action="${rutaApp}" method="POST" title="ver">\n                        <input type="number" class="hidden" name="id" value="${datos[i]['Nº']}">\n                        <button type="submit" class="btnActualizar"><i class="fas fa-eye mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></button>\n                        </form></div>`;

                    }

                    if (boton[x] == 'cambiarestadofactura') {
                        contenido += `<a href="" class="cambiarestadofactura" title="Cambiar estado"><i class="fas fa-comment-dollar mr-1 fill-current text-blue-600 text-sm lg:text-xl "></i></a>`;
                    }

                    if (boton[x] == 'eliminar') {
                        contenido += `<a class="mr-1 eliminar cursor-pointer" title="Eliminar" data-eliminar="${datos[i]['Nº']}"><i class="fas fa-trash-alt mr-1 fill-current text-red-600 text-sm lg:text-xl"></i></a>`;
                    }
                    if (boton[x] == 'validar') {
                        var verValidar = 'none';
                        if (datos[i]['Estado'] == 'terminadasinvalorar') {
                            verValidar = 'block';
                        }
                        contenido += `<a href="" class="mr-1 validar" title="Validar"><i class="fas fa-thumbs-up mr-1 fill-current text-blue-600 text-sm lg:text-xl" style="display:${verValidar}"></i></a>`;
                    }
                    if (boton[x] == 'terminar') {
                        var verTerminar = 'block';
                        if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                            verTerminar = 'none';
                        }
                        contenido += `<a href="" class="mr-1 terminar" title="Terminar"><i class="fas fa-calendar-check mr-1 fill-current text-blue-600 text-sm lg:text-xl" style="display:${verTerminar}"></i></a>`;
                    }
                    if (boton[x] == 'comentario') {
                        contenido += `<a href="" class="mr-1 comentario" title="Comentarios"><i class="fas fa-comment-dots mr-1 fill-current text-red-600 text-sm lg:text-xl"></i></a>`;
                    }
                    if (boton[x] == 'modificar') {
                        contenido += `<a href="" class="mr-1 modificar" title="Modificar"><i class="fas fa-shopping-cart mr-1 fill-current text-red-700 text-sm lg:text-xl"></i></a>`;
                    }
                    if (boton[x] == 'modificarBolsa') {
                        contenido += `<a href="" class="mr-1 modificarBolsa" title="Modificar"><i class="fas fa-shopping-cart mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></a>`;
                    }
                    if (boton[x] == 'historial') {
                        contenido += `<a href="" class="mr-1 historial" title="ver historial"><i class="fas fa-history mr-1 fill-current text-gray-600 text-sm lg:text-xl"></i></a>`;
                    }
                    if (boton[x] == 'historialCliente') {
                        var verHorasCliente = 'none';
                        if (datos[i]['verhorascliente'] == 'horas') {
                            verHorasCliente = 'block';
                        }
                        contenido += `<a href="" style="display:${verHorasCliente}" class="mx-1 historial" title="ver historial"><i class="fas fa-history mr-1 fill-current text-gray-600 text-sm lg:text-xl"></i></a>`;
                    }
                    if (boton[x] == 'verEditar') {
                        contenido += `\n                        <div class="flex-1"><form action="${rutaApp}" method="POST" title="editar">\n                        <input type="number" class="hidden" name="id" value="${datos[i]['Nº']}">\n                        <button type="submit" class="btnActualizar"><i class="fas fa-user-edit mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></button>\n                        </form></div>`;
                    }
                    if (boton[x] == 'verUsuario') {
                        contenido += `\n                        <div class="flex-1"><form action="${rutaApp}" method="POST" title="ver">\n                        <input type="number" class="hidden" name="id" value="${datos[i]['idusuario']}">\n                        <button type="submit" class="btnActualizar"><i class="fas fa-user-edit mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></button>\n                        </form></div>`;
                    }

                    if (boton[x] == 'estadoatencion') {

                        var verPlayStop = 'block';
                        if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                            verPlayStop = 'none';
                        }

                        var iconAtencion = 'far fa-play-circle text-red-600';
                        var accion = 'iniciar';
                        if (datos[i]['Atención'] != '' && datos[i]['Atención'] > 0) {
                            iconAtencion = 'far fa-stop-circle text-green-600';
                            accion = 'detener';
                        }
                        contenido += `<a href="" class="mr-1 ${accion}" title="${accion} atención" data-atencion="${datos[i]['Atención']}" style="display:${verPlayStop}"><i class="${iconAtencion} mr-1 fill-current text-sm lg:text-xl"></i></a>`;
                    }

                    if (boton[x] == 'estadoatencioncliente') {

                        var verEstadoAtencion = 'block';
                        if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                            verEstadoAtencion = 'none';
                        }

                        var iconAtencionCliente = 'fas fa-user-clock text-red-600';
                        var accionCli = 'estamos trabajando';
                        if (datos[i]['Atención'] != '' && datos[i]['Atención'] > 0) {
                            iconAtencionCliente = 'far fa-stop-circle text-green-600';
                            accionCli = 'detenido';
                        }
                        contenido += `<a href="" class="mr-1 ${accionCli}" title="${accionCli}" style="display:${verEstadoAtencion}"><i class="${iconAtencionCliente} mr-1 fill-current text-sm lg:text-xl"></i></a>`;
                    }
                    if (boton[x] == 'reasignar') {
                        var verReasignar = 'block';
                        if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                            verReasignar = 'none';
                        }
                        contenido += `<a href="" class="mr-1 reasignar" title="reasignar técnico"><i class="fas fa-random mr-1 fill-current texto-violeta-oscuro text-sm lg:text-xl" style="display:${verReasignar}"></i></a>`;
                    }
                    if (boton[x] == 'reabrir') {
                        var verReabrir = 'none';
                        if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                            verReabrir = 'block';
                        }
                        contenido += `<a href="" class="mr-1 reabrir" title="reabrir"><i class="fas fa-folder-open mr-1 fill-current  text-green-600 text-sm lg:text-xl" style="display:${verReabrir}"></i></a>`;
                    }

                    if (boton[x] == 'rechazar') {
                        var verRechazar = 'none';
                        if (datos[i]['Estado'] == 'pendiente' || datos[i]['Estado'] == 'en curso') {
                            verRechazar = 'block';
                        }
                        contenido += `<a href="" class="mr-1 rechazarIncidencia" title="rechazar"><i class="far fa-times-circle mr-1 fill-current  text-pink-600 text-sm lg:text-xl" style="display:${verRechazar}"></i></a>`;
                    }
                }

                contenido += `</div></td>`;
            }

            contenido += `</tr>`;
        }

        // Redibujar tbody
        let tablaCont = document.getElementById(destino);
        if (tablaCont) {
            let tbody = tablaCont.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = contenido;
            }
        }

        // Actualizar iconos en encabezado
        this.actualizarIconosEncabezado();
    }

    actualizarIconosEncabezado() {
        const cont = document.getElementById(this.destino);
        if (!cont) return;
        const ths = cont.querySelectorAll('.titulos');
        ths.forEach(th => {
            const campo = th.getAttribute('data-campo');
            const btn = th.querySelector('.sort-toggle');
            if (!btn) return;
            // determinar estado
            const criterio = this.sortCriteria.find(c => c.campo === campo);
            const estado = criterio ? criterio.dir : 'DEFAULT';
            const iconMap = { 'DEFAULT': '<i class="fas fa-sort"></i>', 'ASC': '<i class="fas fa-sort-amount-up-alt"></i>', 'DESC': '<i class="fas fa-sort-amount-down-alt"></i>' };
            btn.setAttribute('data-state', estado);
            btn.innerHTML = iconMap[estado] || '<i class="fas fa-sort"></i>';
        });
    }

    tabla(ruta, destino, busqueda, orden, tipoOrden, filas, pagina, clases, boton, rutaApp, id, anio) {
        var xhr = new XMLHttpRequest();
        let self = this;
        xhr.onreadystatechange = function() {

            if (this.readyState == 4 && this.status == 200) {

                let datos = [];
                if (this.responseText != "") {
                    datos = JSON.parse(this.responseText);
                }

                // GUARDAR los datos actuales para ordenamiento en JS
                self.datosActuales = datos;

                // PARSEAR busqueda (si viene como JSON) para rellenar inputs de filtro
                let busquedaObj = {};
                try {
                    if (busqueda && typeof busqueda === 'string' && busqueda.trim() !== '') {
                        busquedaObj = JSON.parse(busqueda);
                    }
                } catch (e) {
                    busquedaObj = {};
                }

                //1- inicio de contrucción del contenido
                var contenido = "";

                for (var i = 0; i < datos.length; i++) {

                    var titulos = Object.keys(datos[i]);

                    contenido += `\n                                <tr class="rows">`;

                    for (var j in titulos) {


                        //estilos cuando se manejan estado

                        if (titulos[j] == 'Estado') {
                            var claseTd = "";

                            var nombreEstado =  '';
                            if (datos[i][titulos[j]] == 'pendiente') {
                                nombreEstado = 'pendiente';
                                claseTd = 'font-bold text-white bg-red-600 text-center border rounded-lg p-1';
                            } else if (datos[i][titulos[j]] == 'en curso') {
                                nombreEstado = 'en curso';
                                claseTd = 'font-bold text-white bg-yellow-400 text-center border rounded-lg p-1';
                            } else if (datos[i][titulos[j]] == 'terminada') {
                                nombreEstado = 'terminada';
                                claseTd = 'font-bold text-white bg-green-600 text-center border rounded-lg p-1';                                        
                            }   else if (datos[i][titulos[j]] == 'terminadasinvalorar') {
                                nombreEstado = 'terminada';
                                claseTd = 'font-bold text-white bg-green-600 text-center border rounded-lg p-1';                                        
                            }                                                                    
                            contenido += `\n                                        <td class="px-1 py-2 border-b border-gray-200 bg-white text-xs 3xl:text-sm"><div class="${claseTd}">${nombreEstado}</div></td>`;

                        }else if (titulos[j] == 'Fact/Ppto') {
                            let claseTdFact = "";           
                            let nombreEstado = datos[i][titulos[j]];
                            let colortexto = '';                                                    
                            if (datos[i][titulos[j]] == 'facturar' || datos[i][titulos[j]] == 'presupuestar' || datos[i][titulos[j]] == 'aceptado' || datos[i][titulos[j]] == 'presupuestado' || datos[i][titulos[j]] == 'facturado' || datos[i][titulos[j]] == 'rechazado' || datos[i][titulos[j]] == 'FParc') {       
                                colortexto = 'text-white'                                 
                                claseTdFact = ` ${datos[i][titulos[j]]} border rounded-lg p-1`;
                            }else if(datos[i][titulos[j]] == 'sin estado'){
                                nombreEstado = ''; 
                            }

                            contenido += `\n                                    <td class="px-1 py-2 border-b border-gray-200 bg-white text-xs 3xl:text-sm">\n                                        <div class="${claseTdFact} text-center clickestado font-bold ${colortexto}" data-tipo=${datos[i][titulos[j]]}>${nombreEstado}</div>\n                                    </td>`;

                        } else if (titulos[j] == 'Atención') {                                   

                            contenido += `\n                                        <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-center">${datos[i][titulos[j]]}</td>`;

                        }else if (titulos[j] == 'verhorascliente' ) {

                            contenido += `\n                                    <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-sm"></td>`;
                        }else if (titulos[j] == 'idTecnico') {

                            contenido += `\n                                    <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-sm">${datos[i][titulos[j]]}</td>`;
                        }else if (titulos[j] == 'idusuario') {

                            contenido += `\n                                    <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-sm">${datos[i][titulos[j]]}</td>`;

                        }else if (titulos[j] == 'pktabla') {

                            
                            contenido += `\n                                    <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-sm">${datos[i][titulos[j]]}</td>`;
                        
                        }else{

                            contenido += `\n                                    <td class="px-1 py-2 border-b border-gray-200 bg-white text-xs 3xl:text-sm text-center">${datos[i][titulos[j]]}</td>`;
                        }

                    }

                    if (boton != "") {
                        contenido += `\n                                    <td class="botones px-2 py-2 border-b border-gray-200 bg-white text-sm"><div class="flex">`;
                                                                                                    
                        //LISTADO DE BOTONES
                        for (let x = 0; x < boton.length; x++) {

                            
                            if (boton[x] == 'pdffacturafila') {
                                contenido += `<a class="mr-1 pdffila text-gray-500 cursor-pointer" title="PDF" data-index="${datos[i]['Nº']}"><i class="fa fa-file-pdf" style="font-size: 1.25rem;"></i></a>`;
                            }

                            if (boton[x] == 'editar') {
                                contenido += `<a href="" class="mr-1 editar" title="Editar"><i class="fas fa-edit mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></a>`;
                            }
                            if (boton[x] == 'ver') {
                                contenido += `\n                                    <div class=""><form action="${rutaApp}" method="POST" title="ver">\n                                    <input type="number" class="hidden" name="id" value="${datos[i]['Nº']}">\n                                    <button type="submit" class="btnActualizar"><i class="fas fa-eye mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></button>\n                                    </form></div>`;

                            }

                            if (boton[x] == 'cambiarestadofactura') {
                                contenido += `<a href="" class="cambiarestadofactura" title="Cambiar estado"><i class="fas fa-comment-dollar mr-1 fill-current text-blue-600 text-sm lg:text-xl "></i></a>`;
                            }
                        
                            if (boton[x] == 'eliminar') {
                                contenido += `<a class="mr-1 eliminar cursor-pointer" title="Eliminar" data-eliminar="${datos[i]['Nº']}"><i class="fas fa-trash-alt mr-1 fill-current text-red-600 text-sm lg:text-xl"></i></a>`;
                            }
                            if (boton[x] == 'validar') {
                                var verValidar = 'none';
                                if (datos[i]['Estado'] == 'terminadasinvalorar') {
                                    verValidar = 'block';
                                }
                                contenido += `<a href="" class="mr-1 validar" title="Validar"><i class="fas fa-thumbs-up mr-1 fill-current text-blue-600 text-sm lg:text-xl" style="display:${verValidar}"></i></a>`;
                            }
                            if (boton[x] == 'terminar') {
                                var verTerminar = 'block';
                                if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                                    verTerminar = 'none';
                                }
                                contenido += `<a href="" class="mr-1 terminar" title="Terminar"><i class="fas fa-calendar-check mr-1 fill-current text-blue-600 text-sm lg:text-xl" style="display:${verTerminar}"></i></a>`;
                            }
                            if (boton[x] == 'comentario') {
                                contenido += `<a href="" class="mr-1 comentario" title="Comentarios"><i class="fas fa-comment-dots mr-1 fill-current text-red-600 text-sm lg:text-xl"></i></a>`;
                            }
                            if (boton[x] == 'modificar') {
                                contenido += `<a href="" class="mr-1 modificar" title="Modificar"><i class="fas fa-shopping-cart mr-1 fill-current text-red-700 text-sm lg:text-xl"></i></a>`;
                            }
                            if (boton[x] == 'modificarBolsa') {
                                contenido += `<a href="" class="mr-1 modificarBolsa" title="Modificar"><i class="fas fa-shopping-cart mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></a>`;
                            }
                            if (boton[x] == 'historial') {
                                contenido += `<a href="" class="mr-1 historial" title="ver historial"><i class="fas fa-history mr-1 fill-current text-gray-600 text-sm lg:text-xl"></i></a>`;
                            }
                            if (boton[x] == 'historialCliente') {
                                var verHorasCliente = 'none';
                                if (datos[i]['verhorascliente'] == 'horas') {
                                    verHorasCliente = 'block';
                                }
                                contenido += `<a href="" style="display:${verHorasCliente}" class="mx-1 historial" title="ver historial"><i class="fas fa-history mr-1 fill-current text-gray-600 text-sm lg:text-xl"></i></a>`;
                            }
                            if (boton[x] == 'verEditar') {
                                contenido += `\n                                    <div class="flex-1"><form action="${rutaApp}" method="POST" title="editar">\n                                    <input type="number" class="hidden" name="id" value="${datos[i]['Nº']}">\n                                    <button type="submit" class="btnActualizar"><i class="fas fa-user-edit mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></button>\n                                    </form></div>`;
                            }
                            if (boton[x] == 'verUsuario') {
                                contenido += `\n                                    <div class="flex-1"><form action="${rutaApp}" method="POST" title="ver">\n                                    <input type="number" class="hidden" name="id" value="${datos[i]['idusuario']}">\n                                    <button type="submit" class="btnActualizar"><i class="fas fa-user-edit mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></button>\n                                    </form></div>`;
                            }

                            if (boton[x] == 'estadoatencion') {

                                var verPlayStop = 'block';
                                if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                                    verPlayStop = 'none';
                                }

                                var iconAtencion = 'far fa-play-circle text-red-600';
                                var accion = 'iniciar';
                                if (datos[i]['Atención'] != '' && datos[i]['Atención'] > 0) {
                                    iconAtencion = 'far fa-stop-circle text-green-600';
                                    accion = 'detener';
                                }
                                contenido += `<a href="" class="mr-1 ${accion}" title="${accion} atención" data-atencion="${datos[i]['Atención']}" style="display:${verPlayStop}"><i class="${iconAtencion} mr-1 fill-current text-sm lg:text-xl"></i></a>`;
                            }

                            if (boton[x] == 'estadoatencioncliente') {

                                var verEstadoAtencion = 'block';
                                if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                                    verEstadoAtencion = 'none';
                                }

                                var iconAtencionCliente = 'fas fa-user-clock text-red-600';
                                var accionCli = 'estamos trabajando';
                                if (datos[i]['Atención'] != '' && datos[i]['Atención'] > 0) {
                                    iconAtencionCliente = 'far fa-stop-circle text-green-600';
                                    accionCli = 'detenido';
                                }
                                contenido += `<a href="" class="mr-1 ${accionCli}" title="${accionCli}" style="display:${verEstadoAtencion}"><i class="${iconAtencionCliente} mr-1 fill-current text-sm lg:text-xl"></i></a>`;
                            }
                            if (boton[x] == 'reasignar') {
                                var verReasignar = 'block';
                                if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                                    verReasignar = 'none';
                                }
                                contenido += `<a href="" class="mr-1 reasignar" title="reasignar técnico"><i class="fas fa-random mr-1 fill-current texto-violeta-oscuro text-sm lg:text-xl" style="display:${verReasignar}"></i></a>`;
                            }
                            if (boton[x] == 'reabrir') {
                                var verReabrir = 'none';
                                if (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') {
                                    verReabrir = 'block';
                                }
                                contenido += `<a href="" class="mr-1 reabrir" title="reabrir"><i class="fas fa-folder-open mr-1 fill-current  text-green-600 text-sm lg:text-xl" style="display:${verReabrir}"></i></a>`;
                            }

                            if (boton[x] == 'rechazar') {
                                var verRechazar = 'none';
                                if (datos[i]['Estado'] == 'pendiente' || datos[i]['Estado'] == 'en curso') {
                                    verRechazar = 'block';
                                }
                                contenido += `<a href="" class="mr-1 rechazarIncidencia" title="rechazar"><i class="far fa-times-circle mr-1 fill-current  text-pink-600 text-sm lg:text-xl" style="display:${verRechazar}"></i></a>`;
                            }
                        }

                        contenido += `</div></td>`;
                    }

                    contenido += `</tr>`;
                }
                //fin de contrucción del contenido


                // Si la tabla ya existe en DOM -> solo actualizar tbody (evita re-crear headers y perder estado de los botones)
                const cont = document.getElementById(destino);
                const existingTable = cont ? cont.querySelector('table#tabla1') : null;

                if (existingTable && existingTable.querySelector('tbody')) {
                    // Actualizar tbody con nuevo contenido
                    const tbody = existingTable.querySelector('tbody');
                    tbody.innerHTML = contenido;
                    // actualizar iconos encabezado según criterios actuales
                    self.actualizarIconosEncabezado();
                } else {
                    // Construir cabeceras (solo la primera vez)
                    if (datos[0]) {
                        var titles = Object.keys(datos[0]);

                        var cabecera = "";
                        titles.forEach(function(element){ 
                            var displaycab1 = 'table-cell';
                            if (element == 'verhorascliente' || element == 'idTecnico' || element == 'idusuario' || element == 'Atención' || element == 'pktabla') {
                                displaycab1 = 'none';
                            }

                            // Control único de orden por columna (cicla DEFAULT -> ASC -> DESC -> DEFAULT)
                            let estadoInicial = 'DEFAULT';
                            try {
                                const criterioExist = self.sortCriteria.find(c => c.campo === element);
                                if (criterioExist) estadoInicial = criterioExist.dir;
                            } catch (e) { }
                            const iconMap = { 'DEFAULT': '<i class="fas fa-sort"></i>', 'ASC': '<i class="fas fa-sort-amount-up-alt"></i>', 'DESC': '<i class="fas fa-sort-amount-down-alt"></i>' };
                            let controls = `<button class="sort-toggle" data-campo="${element}" data-state="${estadoInicial}" title="Ordenar" style="margin-left:6px">${iconMap[estadoInicial] || '<i class="fas fa-sort"></i>'}</button>`;

                            cabecera += `<th class="titulos px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs md:text-sm font-semibold texto-violeta-oscuro uppercase tracking-wider text-center" style="display:${displaycab1}" data-campo="${element}">${element} ${controls}</th>`
                        });

                        if (boton != "") {
                            cabecera += `<th class="text-center px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-sm font-semibold texto-violeta-oscuro uppercase tracking-wider">Acciones</th>`;
                        }

                        // inputs filtro
                        var cabecera2 = "";
                        // reusar busquedaObj para poner valores
                        titles.forEach(function(element){                         
                            var displaycab2 = 'table-cell';
                            if (element == 'verhorascliente' || element == 'idTecnico' || element == 'idusuario' || element == 'Atención' || element == 'pktabla' ) {
                                displaycab2 = 'none';
                            }
                            let valorInput = busquedaObj[element] || '';
                            cabecera2 += `<th class="tituloInputSearch p-2" style="background-color:#ffff;display:${displaycab2}">\n\n                                <div class="flex border rounded p-1 bg-transparent">\n                                        <i class="fas fa-search my-1 text-gray-300" style="font-size: 0.7rem;"></i>\n                                        <input class="rounded bg-white w-full inputKeyup" data-nombre="${element}" value="${valorInput}">\n                                </div>\n\n                                            </th>`;

                        });

                        if (boton != "") {
                            cabecera2 += `<th class="tituloInputSearch" style="background-color:#ffff"></th>`;
                        }

                        cont.innerHTML = `\n                                    <table class="${clases}" id="tabla1">\n                                        <thead>\n                                        <tr  id="prueba">${cabecera2}</tr>\n                                        <tr>${cabecera}</tr></thead>\n                                        <tbody id="tabla1tbody">\n                                            ${contenido}\n                                        </tbody>\n                                    </table>\n                                    `;

                        // Después de insertar la tabla, actualizar iconos según criterios existentes
                        self.actualizarIconosEncabezado();
                    }
                }
            }

        };
        xhr.open("POST", ruta, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.send(
            `busqueda=${busqueda}&orden=${orden}&tipoOrden=${tipoOrden}&filas=${filas}&pagina=${pagina}&id=${id}&anio=${anio}`
        );
    } // fin metodo tabla

    buscador(destinobuscador, segmento) {


        let select = "";
        let valorSeleccionado = 30;

        for (let i = 0; i < segmento.length; i++) {
            const selected = segmento[i] == valorSeleccionado ? "selected" : "";
            select += `<option value="${segmento[i]}" ${selected}>${segmento[i]}</option>`;
        }
        document.getElementById(destinobuscador).innerHTML = `\n        \n        <p class="appearance-none h-full block appearance-none w-full text-gray-700 p-1 leading-tight focus:outline-none focus:bg-white focus:border-gray-500">Registros</p>\n        <select class="appearance-none h-full rounded-l rounded-r border block appearance-none w-full bg-white border-gray-400 text-gray-700 py-1 px-4 pr-8 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" name="registros" id="registros">\n        ${select}\n        </select></p>\n        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">\n        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">\n                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />\n            </svg>\n        </div>\n        `;
        } // fin metodo buscador

    paginador(rutaTotalRegistros, fila, pagina, destinoPaginador, busqueda, id, anio) {
            let paginator = "";
            let total = 0;
            var xhr = new XMLHttpRequest();
            let self = this;
            
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    total = parseInt(this.responseText);

                  
                    // Inicio eb componente

                    paginator += `<div class="text-center custom-number-input w-full justify-center">\n                    <label for="custom-input-number" class="w-35 text-gray-700 text-sm font-semibold ">Página ${parseInt(pagina) + 1} de ${Math.ceil(total / fila)}\n                    </label>\n                    <div class="flex flex-row h-auto w-35 rounded-lg relative bg-transparent mt-1 justify-center">`;

                    if (pagina > 0) {
                        paginator += `\n                    <button data-action="decrement" data-elementopaginador="0" class=" bg-gray-300 text-gray-600 hover:text-gray-700 hover:bg-gray-400 h-full w-10 rounded-l cursor-pointer outline-none">\n                        <span class="m-auto text-sm lg:text-base font-thin" style="pointer-events: none;"><i  class="fas fa-angle-double-left"></i></span>\n                      </button>\n                      <button data-action="decrement" data-elementopaginador="${parseInt(pagina) - 1}" class=" bg-gray-300 text-gray-600 hover:text-gray-700 hover:bg-gray-400 h-full w-10  cursor-pointer outline-none">\n                        <span class="m-auto text-sm lg:text-base font-thin" style="pointer-events: none;"><i  class="fas fa-angle-left"></i></span>\n                      </button>`;
                    }
                    paginator += `<input type="number" class="outline-none focus:outline-none text-center w-30 bg-gray-300 font-semibold text-md hover:text-black focus:text-black  md:text-basecursor-default flex items-center text-gray-700  outline-none inputPaginador" name="custom-input-number" value="${parseInt(pagina) + 1}" readonly></input>`;
                    if (pagina < (Math.ceil(total / fila) - 1)) {
                        paginator += `<button data-action="increment" data-elementopaginador="${parseInt(pagina) + 1}" class="bg-gray-300 text-gray-600 hover:text-gray-700 hover:bg-gray-400 h-full w-10  cursor-pointer">\n                      <span class="m-auto text-sm lg:text-base font-thin" style="pointer-events: none;"><i  class="fas fa-angle-right"></i></span>\n                    </button>\n                    <button data-action="increment" data-elementopaginador="${Math.ceil(total / fila) - 1}" class="bg-gray-300 text-gray-600 hover:text-gray-700 hover:bg-gray-400 h-full w-10 rounded-r cursor-pointer">\n                      <span class="m-auto text-sm lg:text-base font-thin" style="pointer-events: none;"><i class="fas fa-angle-double-right"></i></span>\n                    </button>\n                  `;
                    }
                    paginator += `\n                    </div>\n                    <label for="custom-input-number" class="w-35 text-gray-700 text-sm font-semibold ">Total Registros:&nbsp; ${total}</label>\n                    `;

                    // fin web componente
                    document.getElementById(`${destinoPaginador}`).innerHTML = `${paginator}`;
                }
            };
            xhr.open("POST", rutaTotalRegistros, true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.send(
                `busqueda=${busqueda}&id=${id}&anio=${anio}`
            );

    } // fin metodo paginador

    exportarExcel() {
        let busqueda = {}; 

        // Capturar valores de los inputs con la clase "inputKeyup"
        document.querySelectorAll(".inputKeyup").forEach(input => {
            busqueda[input.name] = input.value;
        });

        let busquedaJSON = JSON.stringify(busqueda);
        let filas = document.getElementById("filas").value || 30;
        let pagina = document.getElementById("pagina").value || 1;
        let orden = document.getElementById("orden").value || "id";
        let tipoOrden = document.getElementById("tipoOrden").value || "ASC";

        let xhr = new XMLHttpRequest();
        let rutaExportacion = this.ruta + "/exportarFacturasExcel"; // Ajusta la ruta

        xhr.open("POST", rutaExportacion, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        xhr.responseType = "blob"; // Importante para manejar el archivo como Excel

        xhr.onload = function () {
            if (xhr.status === 200) {
                let blob = new Blob([xhr.response], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
                let url = window.URL.createObjectURL(blob);
                let a = document.createElement("a");
                a.href = url;
                a.download = "Facturas_Exportadas.xlsx";
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            }
        };

        xhr.send(
            `busqueda=${busquedaJSON}&orden=${orden}&tipoOrden=${tipoOrden}&filas=${filas}&pagina=${pagina}`
        );
    }



} // fin de la clase creartabla



export default function arrancar(objeto, ruta, destino, orden, tipoOrden, pagina, destinobuscador, rutaTotalRegistros, segmento, clasesTabla, destinoPaginador, boton, rutaApp, id, anio) {
    
    var objeto = new creartabla(ruta, destino, orden, tipoOrden, pagina, destinobuscador, rutaTotalRegistros, segmento, clasesTabla, destinoPaginador, boton, rutaApp, id, anio);

    let prueba1 = document.getElementById(destino);
    // Debounce handler para las búsquedas (espera al terminar de teclear)
    let debounceTimer;
    prueba1.addEventListener("keyup", function(e) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            var inputs = document.getElementsByClassName("inputKeyup");
            let algo = {};
            Array.prototype.forEach.call(inputs, function(element, index) {
                if (element.value != '') {
                    let obj = element.value;
                    let obj2 = element.getAttribute('data-nombre');
                    algo[obj2] = obj;
                }
            });
            let output = JSON.stringify(algo);
            objeto.busqueda = output;
            // Mantener el orden actual (no reiniciar a los valores iniciales) y volver a la primera página
            objeto.rendertabla(objeto.busqueda, objeto.filas, 0, objeto.campoOrden, objeto.tipoOrden);
        }, 300);
    })

    let selectFilas = document.getElementById('registros');
    selectFilas.addEventListener("change", function() {
        objeto.filas = selectFilas.value
            //console.log(selectFilas.value);
        // CORRECCIÓN: Reiniciar el ordenamiento a los valores iniciales al cambiar cantidad de filas
        objeto.rendertabla(objeto.busqueda, objeto.filas, 0, objeto.campoOrdenInicial, objeto.tipoOrdenInicial);
    }); // fin del addEventListener para select registros pagina


    let paginadores = document.getElementById(objeto.destinoPaginador);
    paginadores.addEventListener("click", function(e) {
        //console.log(e.target.dataset.elementopaginador);
        objeto.pagina = e.target.dataset.elementopaginador;
        // CORRECCIÓN: Reiniciar el ordenamiento a los valores iniciales al cambiar de página
        objeto.rendertabla(objeto.busqueda, objeto.filas, objeto.pagina, objeto.campoOrdenInicial, objeto.tipoOrdenInicial)

    }); // fin del addEventListener para paginar


    let ordenador = document.getElementById(objeto.destino);
    // Delegación para clicks en botón único de orden (ciclo DEFAULT->ASC->DESC->DEFAULT)
    ordenador.addEventListener("click", function(e) {
        const btn = e.target.closest('.sort-toggle');
        if (btn) {
            e.preventDefault();
            const campo = btn.getAttribute('data-campo');
            let estado = btn.getAttribute('data-state') || 'DEFAULT';
            
            // ciclo: DEFAULT -> ASC -> DESC -> DEFAULT
            let siguiente = 'ASC';
            if (estado === 'DEFAULT') {
                siguiente = 'ASC';
            } else if (estado === 'ASC') {
                siguiente = 'DESC';
            } else if (estado === 'DESC') {
                siguiente = 'DEFAULT';
            }

            // Aplicar el cambio: si es DEFAULT, remover columna; si no, agregarlo/actualizarlo
            objeto.setSortCriterion(campo, siguiente);

            // Ordenar en memoria y redibujar
            objeto.ordenarDatosEnMemoria();
            objeto.redibujarTabla(objeto.datosActuales, objeto.destino, objeto.clasesTabla, objeto.boton, objeto.rutaApp);
            return;
        }

        // Si click fue sobre th titular (compatibilidad previa): alternar orden simple sobre esa columna
        let thElement = e.target.closest(".titulos");
        if (thElement) {
            let campoMostrado = thElement.getAttribute('data-campo');
            objeto.contador += 1;
            objeto.tipoOrden = ((objeto.contador % 2) == 0) ? "ASC" : "DESC";
            objeto.setSortCriterion(campoMostrado, objeto.tipoOrden);
            objeto.ordenarDatosEnMemoria();
            objeto.redibujarTabla(objeto.datosActuales, objeto.destino, objeto.clasesTabla, objeto.boton, objeto.rutaApp);
        }
    }); // fin del addEventListener para ordenar

    //llamada btn exportar excel-------> CONTRUYENDO
    /* document.getElementById("exportExcel").addEventListener("click", function () {
        tabla.exportarExcel();
    }); */

}; // fin de la funcion arrancar
