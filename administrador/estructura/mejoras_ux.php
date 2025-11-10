<!-- Sistema Unificado de Mejoras UX -->
<!-- 1. MODAL DE CONFIRMACIÓN -->
<div class="modal fade" id="confirmacionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmacionMensaje">¿Está seguro de que desea realizar esta acción?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmacionAceptar">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- 2. SPINNER DE CARGA -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="loading-spinner-container">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="loading-text mt-3" id="loadingText">Procesando...</div>
    </div>
</div>

<!-- 3. SISTEMA DE BÚSQUEDA Y FILTROS (se inserta donde se necesite) -->
<template id="templateBusquedaFiltros">
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Buscar</label>
                    <div class="input-group">
                        <input type="text" class="form-control busqueda-input" placeholder="Buscar en la tabla...">
                        <button class="btn btn-outline-secondary limpiar-busqueda" type="button">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filtrar por columna</label>
                    <select class="form-select filtro-columna">
                        <option value="">Todas las columnas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 aplicar-filtros">
                        <i class="fas fa-filter me-1"></i>Filtrar
                    </button>
                </div>
            </div>
            <div class="mt-2 filtros-activos" style="display: none;"></div>
        </div>
    </div>
</template>

<style>
.loading-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
    background: rgba(0, 0, 0, 0.7); z-index: 9999;
    display: flex; justify-content: center; align-items: center;
}
.loading-spinner-container { text-align: center; color: white; }
.loading-text { font-size: 1.1rem; font-weight: 500; }
</style>

<script>
// ==================================
// SISTEMA UNIFICADO DE MEJORAS UX
// ==================================

class UXManager {
    constructor() {
        this.loadingCounter = 0;
        this.init();
    }

    init() {
        this.setupConfirmaciones();
        this.setupLoadingGlobal();
    }

    // 1. SISTEMA DE CONFIRMACIONES
    setupConfirmaciones() {
        // Función global de confirmación
        window.confirmarAccion = (mensaje, callback) => {
            const modal = new bootstrap.Modal(document.getElementById('confirmacionModal'));
            document.getElementById('confirmacionMensaje').textContent = mensaje;
            
            const btnAceptar = document.getElementById('confirmacionAceptar');
            const nuevoBtn = btnAceptar.cloneNode(true);
            btnAceptar.parentNode.replaceChild(nuevoBtn, btnAceptar);
            
            nuevoBtn.onclick = () => {
                modal.hide();
                callback?.();
            };
            
            modal.show();
        };

        // Interceptar eliminaciones automáticamente
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-danger');
            if (btn && (btn.value === 'Borrar' || btn.value === 'Eliminar' || btn.classList.contains('btn-eliminar'))) {
                e.preventDefault();
                const form = btn.closest('form');
                const nombre = btn.dataset.nombre || 'este elemento';
                const id = btn.dataset.id || form?.querySelector('[name="txtID"]')?.value || '';
                
                confirmarAccion(
                    `¿Está seguro de que desea eliminar "${nombre}"${id ? ' (ID: ' + id + ')' : ''}?`,
                    () => {
                        if (btn.type === 'button') {
                            // Crear input oculto para la acción
                            const accionInput = document.createElement('input');
                            accionInput.type = 'hidden';
                            accionInput.name = 'accion';
                            accionInput.value = btn.value || 'Borrar';
                            form.appendChild(accionInput);
                        }
                        form.submit();
                    }
                );
            }
        });
    }

    // 2. SISTEMA DE LOADING
    setupLoadingGlobal() {
        // Manager de loading
        window.loadingManager = {
            show: (mensaje = 'Procesando...') => {
                this.loadingCounter++;
                if (this.loadingCounter === 1) {
                    document.getElementById('loadingText').textContent = mensaje;
                    document.getElementById('loadingOverlay').style.display = 'flex';
                }
            },
            hide: () => {
                this.loadingCounter = Math.max(0, this.loadingCounter - 1);
                if (this.loadingCounter === 0) {
                    document.getElementById('loadingOverlay').style.display = 'none';
                }
            }
        };

        // Interceptar formularios
        document.addEventListener('submit', () => loadingManager.show('Enviando formulario...'));
        
        // Interceptar enlaces con data-loading
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-loading]');
            if (link) loadingManager.show(link.dataset.loading);
        });

        // Interceptar AJAX
        const originalFetch = window.fetch;
        window.fetch = (...args) => {
            loadingManager.show();
            return originalFetch(...args).finally(() => loadingManager.hide());
        };
    }

    // 3. SISTEMA DE BÚSQUEDA Y FILTROS (se llama cuando se necesita)
    inicializarBusquedaFiltros(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        // Insertar template de búsqueda antes de la tabla
        const template = document.getElementById('templateBusquedaFiltros');
        const busquedaHTML = template.content.cloneNode(true);
        table.parentNode.insertBefore(busquedaHTML, table);

        const busquedaContainer = table.previousElementSibling;
        const inputBusqueda = busquedaContainer.querySelector('.busqueda-input');
        const selectFiltro = busquedaContainer.querySelector('.filtro-columna');
        const filtrosActivos = busquedaContainer.querySelector('.filtros-activos');

        // Llenar opciones de filtro
        table.querySelectorAll('thead th').forEach((th, index) => {
            if (th.textContent.trim() && !th.textContent.includes('Acciones')) {
                const option = document.createElement('option');
                option.value = index;
                option.textContent = th.textContent.trim();
                selectFiltro.appendChild(option);
            }
        });

        // Guardar datos originales
        const filas = Array.from(table.querySelectorAll('tbody tr')).map(tr => ({
            elemento: tr,
            texto: tr.textContent.toLowerCase(),
            celdas: Array.from(tr.cells).map(td => td.textContent.toLowerCase().trim())
        }));

        // Búsqueda en tiempo real
        inputBusqueda.addEventListener('input', () => this.filtrarTabla());
        busquedaContainer.querySelector('.limpiar-busqueda').addEventListener('click', () => {
            inputBusqueda.value = '';
            this.filtrarTabla();
        });

        // Aplicar filtros
        busquedaContainer.querySelector('.aplicar-filtros').addEventListener('click', () => this.filtrarTabla());

        // Función de filtrado
        this.filtrarTabla = () => {
            const termino = inputBusqueda.value.toLowerCase();
            const columnaIndex = selectFiltro.value;
            const valorFiltro = termino;

            filas.forEach(fila => {
                let visible = true;
                
                if (termino) {
                    if (columnaIndex) {
                        // Filtro por columna específica
                        visible = fila.celdas[columnaIndex]?.includes(termino) || false;
                    } else {
                        // Búsqueda general
                        visible = fila.texto.includes(termino);
                    }
                }

                fila.elemento.style.display = visible ? '' : 'none';
            });

            // Actualizar contador
            const visibles = filas.filter(f => f.elemento.style.display !== 'none').length;
            const totalBadge = document.querySelector('.badge.bg-light.text-dark');
            if (totalBadge) totalBadge.textContent = `Mostrando: ${visibles} de ${filas.length}`;
        };
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.uxManager = new UXManager();
});
</script>