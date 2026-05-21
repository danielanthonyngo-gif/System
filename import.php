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
        /* Style para sa Save Button */
        #save-btn {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #008CBA;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: none; /* Naka-hide muna hangga't walang file */
        }
        #save-btn:hover { background-color: #007bb5; }
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
    
    <!-- Dito idinagdag ang Save Button -->
    <button id="save-btn">I-save ang JSON File</button>
</div>

<script>
    let currentJsonData = null; // Variable para i-store ang data

    // Abangan kapag may piniling file ang user
    document.getElementById('excel-file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();

        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: "" });

            // I-store sa variable para magamit ng save button
            currentJsonData = jsonData;

            document.getElementById('json-output').textContent = JSON.stringify(jsonData, null, 2);
            document.getElementById('save-btn').style.display = 'block'; // Ipakita ang button
            displayTable(jsonData);
        };

        reader.readAsArrayBuffer(file);
    });

    // Function para sa pag-save/download ng JSON
    document.getElementById('save-btn').addEventListener('click', function() {
        if (!currentJsonData) return;
        
        const blob = new Blob([JSON.stringify(currentJsonData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'data.json';
        a.click();
        URL.revokeObjectURL(url);
    });

    // Function para gumawa ng HTML Table mula sa JSON Data
    function displayTable(data) {
        const thead = document.querySelector('#excel-table thead');
        const tbody = document.querySelector('#excel-table tbody');
        
        thead.innerHTML = "";
        tbody.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = "<tr><td colspan='100%'>Walang laman o walang data ang file.</td></tr>";
            return;
        }

        const headers = Object.keys(data[0]);
        let headerRow = "<tr>";
        headers.forEach(header => {
            headerRow += `<th>${header}</th>`;
        });
        headerRow += "</tr>";
        thead.innerHTML = headerRow;

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