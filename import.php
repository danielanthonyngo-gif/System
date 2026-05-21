<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel File Importer</title>
    <!-- Isinama natin ang SheetJS Library mula sa CDN -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #f4f7f6;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        input[type="file"] {
            margin: 20px 0;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        #json-output {
            background-color: #333;
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 200px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Excel File Importer</h2>
    <p>Select an Excel file (.xlsx or .xls) to view its contents.</p>
    
    <!-- Input Button para sa File Selection -->
    <input type="file" id="excel-file" accept=".xlsx, .xls" />

    <h3>Preview ng Data (Table):</h3>
    <div style="overflow-x: auto;">
        <table id="excel-table">
            <thead><!-- Dito papasok ang Headers --></thead>
            <tbody><!-- Dito papasok ang Data Rows --></tbody>
        </table>
    </div>

    <h3>Raw JSON Data (Puwede mong i-save sa Database):</h3>
    <pre id="json-output">Naghihintay ng file...</pre>
</div>

<script>
    // Abangan kapag may piniling file ang user
    document.getElementById('excel-file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();

        // Kapag nabasa na ang file bilang ArrayBuffer
        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            
            // Basahin ang workbook gamit ang SheetJS
            const workbook = XLSX.read(data, { type: 'array' });

            // Kunin ang unang sheet (puno ng data)
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];

            // I-convert ang Sheet papuntang JSON format
            // defval: "" para lagyan ng empty string ang mga blankong cell
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: "" });

            // 1. I-display ang Raw JSON data sa screen
            document.getElementById('json-output').textContent = JSON.stringify(jsonData, null, 2);

            // 2. I-display ang data sa HTML Table
            displayTable(jsonData);
        };

        // Simulan ang pagbasa sa file
        reader.readAsArrayBuffer(file);
    });

    // Function para gumawa ng HTML Table mula sa JSON Data
    function displayTable(data) {
        const thead = document.querySelector('#excel-table thead');
        const tbody = document.querySelector('#excel-table tbody');
        
        // Linisin muna ang lumang table data kung mayroon man
        thead.innerHTML = "";
        tbody.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = "<tr><td colspan='100%'>Walang laman o walang data ang file.</td></tr>";
            return;
        }

        // Kunin ang mga Column Headers (Keys ng unang object)
        const headers = Object.keys(data[0]);
        
        // Gawa ng Header Row
        let headerRow = "<tr>";
        headers.forEach(header => {
            headerRow += `<th>${header}</th>`;
        });
        headerRow += "</tr>";
        thead.innerHTML = headerRow;

        // Gawa ng mga Data Rows
        data.forEach(row => {
            let bodyRow = "<tr>";
            headers.forEach(header => {
                bodyRow += `<td>${row[header]}</td>`;
            });
            bodyRow += "</tr>";
            tbody.insertAdjacentHTML('beforeend', bodyRow);
        });
    }
</script>

</body>
</html>