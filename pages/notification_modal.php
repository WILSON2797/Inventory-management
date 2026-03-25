<!-- Modal Notification Details -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">
                    <i data-feather="bell" class="me-2"></i>Pending Requests
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="notificationLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div id="notificationContent" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Request Number</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Requested By</th>
                                    <th>Project</th>
                                    <th>Total Qty</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="notificationTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="notificationEmpty" style="display: none;" class="text-center py-4">
                    <i data-feather="inbox" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                    <p class="text-muted">No pending requests</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    border: 2px solid white;
}

.notification-icon-wrapper {
    position: relative;
    display: inline-block;
}
</style>