<?php
    ob_start();
    session_start();
    include 'config.php'; 

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $current_uid = $_SESSION['user_id'];
    $user_res    = mysqli_query($conn, "SELECT fullname, role FROM users WHERE id = '$current_uid'");
    $user_data   = mysqli_fetch_assoc($user_res);
    
    $_SESSION['fullname'] = $user_data['fullname']; 
    
    $display_name = $user_data['fullname'] ?? "Angelo Vicente";
    $user_role    = $user_data['role'] ?? "OJT";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspiro | Computer Asset Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        :root {
            --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%);
            --accent-purple: #7A1CAC;
            --bg-light: #f4f7fe;
            --sidebar-width: 260px;
        }
        body {
            background-color: var(--bg-light);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #362d36;
            margin: 0;
        }
        .content-wrapper {
            margin-left: var(--sidebar-width);
            padding: 35px;
            min-height: 100vh;
        }
        .data-panel { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .input-custom { border-radius: 12px; padding: 12px 15px; border: 1.5px solid #eee; background: #fafafa; font-weight: 600; font-size: 0.9rem; width: 100%; transition: 0.3s; }
        .btn-purple { background: var(--main-gradient); color: white; border-radius: 12px; padding: 12px 20px; font-weight: 700; border: none; transition: 0.2s; }
        .btn-purple:hover { opacity: 0.9; color: white; }
        
        .table-container { max-height: 500px; overflow-y: auto; border-radius: 12px; border: 1px solid #eef2f5; }
        .status-badge { padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.65rem; text-transform: uppercase; display: inline-block; }
        .st-active { background: #E9D5FF; color: #7A1CAC; }
        .st-disposal { background: #FEE2E2; color: #DC2626; }
        .st-replacement { background: #FEF3C7; color: #D97706; }
        
        .badge-dup { background-color: #ef4444; color: white; font-size: 0.7rem; font-weight: 700; padding: 4px 8px; border-radius: 6px; }
        .badge-ok { background-color: #10b981; color: white; font-size: 0.7rem; font-weight: 700; padding: 4px 8px; border-radius: 6px; }
    </style>
</head>
<body>

<?php include 'aside.php';
    $title     = "IMPORT MANAGEMENT";
    $sub_title = "Asset Import System"; ?>

<div class="content-wrapper">
     <?php include 'header.php'; ?>
     
     <div class="data-panel mb-4">
         <h4 class="fw-bold mb-3" style="color: var(--accent-purple);">Upload Asset Registry File</h4>
         <div class="row g-3 align-items-center">
             <div class="col-md-9">
                 <input type="file" id="excel_file" class="form-control input-custom" accept=".xlsx, .xls, .csv">
             </div>
             <div class="col-md-3">
                 <button type="button" id="btn_preview" class="btn btn-purple w-100">
                     <i class="fa-solid fa-magnifying-glass me-2"></i>Analyze & Preview
                 </button>
             </div>
         </div>
     </div>

     <div class="data-panel d-none" id="preview_panel">
         <div class="d-flex justify-content-between align-items-center mb-3">
             <h5 class="fw-bold m-0 text-secondary">Staging Area Preview</h5>
             <button type="button" id="btn_import" class="btn btn-success px-4 fw-bold" style="border-radius: 10px;">
                 <i class="fa-solid fa-cloud-arrow-up me-2"></i>Import Selected Records
             </button>
         </div>

         <div class="table-container">
             <table class="table table-hover align-middle mb-0">
                 <thead class="table-light sticky-top">
                     <tr>
                         <th width="40" class="text-center">
                             <input type="checkbox" id="check_all" class="form-check-input" checked>
                         </th>
                         <th>Import Status</th>
                         <th>Inventory Date</th>
                         <th>Asset Tag</th>
                         <th>Serial Number</th>
                         <th>Brand/Model</th>
                         <th>Type</th>
                         <th>Year/Model</th>
                         <th>Company/Location ID</th>
                         <th>Asset Status</th>
                     </tr>
                 </thead>
                 <tbody id="preview_tbody"></tbody>
             </table>
         </div>
     </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    let excelRowsData = [];

    function parseExcelDate(excelDate) {
        if (!excelDate) return '';
        if (!isNaN(excelDate)) {
            const date = new Date((excelDate - 25569) * 86400000);
            return date.toISOString().split('T')[0];
        }
        const parsed = new Date(excelDate);
        if (!isNaN(parsed.getTime())) {
            return parsed.toISOString().split('T')[0];
        }
        return excelDate;
    }

    $('#btn_preview').on('click', function() {
        const fileInput = document.getElementById('excel_file');
        if (!fileInput.files.length) {
            Swal.fire('Error', 'Please select an Excel file first.', 'warning');
            return;
        }

        Swal.fire({ title: 'Processing data...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const file = fileInput.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            
            excelRowsData = XLSX.utils.sheet_to_json(worksheet, { defval: "" });

            if (excelRowsData.length === 0) {
                Swal.fire('Empty File', 'No valid data found in this spreadsheet.', 'error');
                return;
            }

            const tagsToCheck = excelRowsData.map(r => String(r['ASSET_TAG'] || r['Asset Tag'] || '').trim()).filter(Boolean);

            // Fetch duplicates using application/json to prevent post parameter overhead limits
            $.ajax({
                url: 'check_duplicates.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ asset_tags: tagsToCheck }),
                dataType: 'json',
                success: function(duplicates) {
                    Swal.close();
                    renderPreviewTable(excelRowsData, duplicates);
                },
                error: function() {
                    Swal.fire('Error', 'Failed to scan database verification registry.', 'error');
                }
            });
        };
        reader.readAsArrayBuffer(file);
    });

    function renderPreviewTable(rows, duplicates) {
        let html = '';
        rows.forEach((row, index) => {
            let dateVal   = parseExcelDate(row['INVENTORY_DATE'] || row['Inventory Date']);
            let tagVal    = String(row['ASSET_TAG'] || row['Asset Tag'] || '').trim();
            let serialVal = row['SERIAL_NUMBER'] || row['Serial Number'] || 'N/A';
            let brandVal  = row['BRAND'] || row['Brand/Model'] || 'N/A';
            let typeVal   = row['TYPE'] || row['Type'] || 'N/A';
            let yearVal   = row['YEAR_MODEL'] || row['Year/Model'] || 'N/A';
            
            let companyText = String(row['COMPANY'] || row['Location'] || '').trim().toLowerCase();
            let mappedLocation = "1"; 
            if(companyText.includes('infocom')) mappedLocation = "1";
            if(companyText.includes('inspiro')) mappedLocation = "2";

            let statusVal = row['STATUS'] || row['Status'] || 'Active';

            if (!tagVal || tagVal === "undefined" || tagVal === "") return;

            let isDuplicate = duplicates.includes(tagVal);
            
            let statusClass = 'st-active';
            if(statusVal.toLowerCase().includes('disposal')) statusClass = 'st-disposal';
            if(statusVal.toLowerCase().includes('replacement')) statusClass = 'st-replacement';

            html += `
                <tr class="${isDuplicate ? 'table-light text-muted' : ''}">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input row-checkbox" data-index="${index}" ${isDuplicate ? 'disabled' : 'checked'}>
                    </td>
                    <td>
                        ${isDuplicate ? '<span class="badge-dup"><i class="fa-solid fa-ban me-1"></i> Duplicate</span>' : '<span class="badge-ok"><i class="fa-solid fa-check me-1"></i> Valid</span>'}
                    </td>
                    <td>${dateVal}</td>
                    <td class="fw-bold text-dark">${tagVal}</td>
                    <td><code>${serialVal}</code></td>
                    <td>${brandVal}</td>
                    <td>${typeVal}</td>
                    <td>${yearVal}</td>
                    <td><strong>${mappedLocation === "1" ? "1 (Infocom)" : "2 (Inspiro)"}</strong></td>
                    <td><span class="status-badge ${statusClass}">${statusVal}</span></td>
                </tr>
            `;
        });

        $('#preview_tbody').html(html);
        $('#preview_panel').removeClass('d-none');
    }

    $('#check_all').on('change', function() {
        $('.row-checkbox:not(:disabled)').prop('checked', this.checked);
    });

    $('#btn_import').on('click', function() {
        let selectedRecords = [];

        $('.row-checkbox:checked').each(function() {
            let idx = $(this).data('index');
            let originalRow = excelRowsData[idx];

            let companyText = String(originalRow['COMPANY'] || originalRow['Location'] || '').trim().toLowerCase();
            let finalLocation = "1";
            if(companyText.includes('infocom')) finalLocation = "1";
            if(companyText.includes('inspiro')) finalLocation = "2";

            selectedRecords.push({
                inventory_date: parseExcelDate(originalRow['INVENTORY_DATE'] || originalRow['Inventory Date']),
                asset_tag: String(originalRow['ASSET_TAG'] || originalRow['Asset Tag'] || '').trim(),
                serial_number: originalRow['SERIAL_NUMBER'] || originalRow['Serial Number'] || '',
                brand_model: originalRow['BRAND'] || originalRow['Brand/Model'] || '',
                asset_type: originalRow['TYPE'] || originalRow['Type'] || '',
                year_model: originalRow['YEAR_MODEL'] || originalRow['Year/Model'] || '',
                location: finalLocation,
                status: originalRow['STATUS'] || originalRow['Status'] || 'Active'
            });
        });

        if (selectedRecords.length === 0) {
            Swal.fire('No selection', 'Please mark at least one valid row to process.', 'info');
            return;
        }

        Swal.fire({ title: 'Writing entries...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        // Stream JSON directly to completely bypass max_input_vars limit warnings
        $.ajax({
            url: 'process_import.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ assets: selectedRecords }),
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire('Success', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Database Error', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Network Error', 'The system encountered an engine execution error.', 'error');
            }
        });
    });
});
</script>
</body>
</html>