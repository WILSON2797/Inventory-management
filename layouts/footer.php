<?php
if (!isset($version)) {
    $version = time();
}
?>
                <footer class="footer-admin mt-auto footer-light">
                    <div class="container-xl px-4">
                        <div class="row">
                            <div class="col-md-6 small">Copyright &copy; Fan Indonesia Logistics <?php echo date('Y'); ?></div>
                            <div class="col-md-6 text-md-end small">
                                <a href="#!">Privacy Policy</a>
                                &middot;
                                <a href="#!">Terms &amp; Conditions</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

    <!-- ============================================ -->
    <!-- JS LIBRARIES -->
    <!-- ============================================ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js?v=<?php echo $version; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js?v=<?php echo $version; ?>"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js?v=<?php echo $version; ?>"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js?v=<?php echo $version; ?>"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js?v=<?php echo $version; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11?v=<?php echo $version; ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js?v=<?php echo $version; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js?v=<?php echo $version; ?>"></script>
    <script src="js/scripts.js?v=<?php echo $version; ?>"></script>

    <!-- Custom Scripts -->
    <script src="assets/js/script.js?v=<?php echo $version; ?>"></script>
    <script src="assets/js/spinner.js?v=<?php echo $version; ?>"></script>
    <script src="assets/js/toastr-init.js?v=<?php echo $version; ?>"></script>
    <script src="assets/js/modules/pending-orders.js?v=<?php echo $version; ?>"></script>

    <!-- MODAL GANTI KONTEKS (WAJIB DI SINI!) -->
    <div class="modal fade" id="changeContextModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i data-feather="refresh-cw" class="me-2"></i>
                        Changes Warehouse & Project
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="changeContextForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-primary">Warehouse</label>
                                <select name="wh_name" class="form-select select2" required>
                                    <option value="">-- Pilih Warehouse --</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-success">Project</label>
                                <select name="project_name" class="form-select select2" required>
                                    <option value="">-- Pilih Project --</option>
                                </select>
                            </div>
                        </div>
                        <div class="alert alert-info small mt-3">
                            <i data-feather="info" class="me-1"></i>
                            
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="check" class="me-1"></i> Simpan & Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- LOADING OVERLAY -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="container-spinner">
            <div class="spinner">
                <div class="grok-spinner">
                    <div class="grok-dot"></div>
                    <div class="grok-dot"></div>
                    <div class="grok-dot"></div>
                    <div class="grok-dot"></div>
                </div>
            </div>
            <div class="text" id="loading-text">Loading...</div>
            <p class="sub-text">Please wait while we fetch your data.</p>
        </div>
    </div>

    <!-- JS MODULE: SELECT CONTEXT -->
    <script src="assets/js/modules/select_wh_project.js?v=<?php echo $version; ?>"></script>

</body>
</html>