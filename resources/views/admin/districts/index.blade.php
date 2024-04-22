<!-- Modal -->
<div class="modal fade" id="editDistrictModal" tabindex="-1" role="dialog" aria-labelledby="editDistrictModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editDistrictModalLabel">Edit District</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Form Fields -->
          <form id="editDistrictForm">
            <div class="form-group">
              <label for="districtName">District Name</label>
              <input type="text" class="form-control" id="districtName" name="name">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" onclick="saveDistrict()">Save changes</button>
        </div>
      </div>
    </div>
  </div>
