<h1 class="h3 mb-4 text-gray-800">Empresas</h1>

<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEmpresaModals">
    Abrir Modal de cadastrar
</button>

<button type="button" class="btn btn-warning" data-toggle="modal" data-target="#editEmpresaModals">
    Abrir Modal de editar
</button>

<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteEmpresaModals">
    Abrir Modal de deletar
</button>

<div class="modal fade" id="addEmpresaModals" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Modal de Teste</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Teste do modal
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" name="add_empresa" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
            <!-- ./modal-content -->
        </div>
    </div>
</div>

<div class="modal fade" id="editEmpresaModals" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Modal de Teste</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Teste do modal
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" name="add_empresa" class="btn btn-warning">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
            <!-- ./modal-content -->
        </div>
    </div>
</div>

<div class="modal fade" id="deleteEmpresaModals" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Modal de Teste</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Teste do modal
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" name="add_empresa" class="btn btn-danger">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
            <!-- ./modal-content -->
        </div>
    </div>
</div>