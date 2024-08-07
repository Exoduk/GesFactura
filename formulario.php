<?php
// Obtener los datos de las empresas desde la base de datos
$empresas = [];
try {
    $pdo = new PDO('sqlite:gescom.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT rut, nombre FROM empresas");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Facturas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 1000px;
            box-sizing: border-box;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
        }

        .form-group label {
            width: 20%;
            margin-right: 10px;
            color: #333;
        }

        .form-group input, .form-group select {
            width: 75%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: #007bff;
            outline: none;
        }

        .product-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .product-table th, .product-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        .product-table th {
            background-color: #f4f4f4;
        }

        .totals {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
        }

        .totals .form-group {
            display: flex;
            justify-content: space-between;
        }

        .totals .form-group label, .totals .form-group input {
            width: auto;
        }

        .submit-btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #21ffed;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        .add-row-btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #f0d910;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        .add-row-btn:hover {
            background-color: #218838;
        }
    </style>
    <script>
        function calculateTotals() {
            const rows = document.querySelectorAll(".product-table tbody tr");
            let subtotalNeto = 0;
            let baseNeto = 0;
            let variableNeto = 0;
            rows.forEach(row => {
                const litros = parseFloat(row.querySelector("input[name^='litros']").value) || 0;
                const precioBruto = parseFloat(row.querySelector("input[name^='precio']").value) || 0;
                const impuestoBase = parseFloat(row.querySelector("input[name^='impuesto_base']").value) || 0;
                const impuestoVariable = parseFloat(row.querySelector("input[name^='impuesto_variable']").value) || 0;

                subtotalNeto += litros * precioBruto;
                baseNeto += litros * impuestoBase;
                variableNeto += litros * impuestoVariable;
            });

            const iva = subtotalNeto * 0.19;
            const total = subtotalNeto + iva + baseNeto + variableNeto;

            document.getElementById("subtotal").value = subtotalNeto.toFixed(2);
            document.getElementById("iva").value = iva.toFixed(2);
            document.getElementById("base-neto").value = baseNeto.toFixed(2);
            document.getElementById("variable-neto").value = variableNeto.toFixed(2);
            document.getElementById("total").value = total.toFixed(2);
        }

        function addRow() {
            const tableBody = document.querySelector(".product-table tbody");
            const newRow = document.createElement("tr");

            newRow.innerHTML = `
                <td>
                    <select name="combustible[]">
                        <option value="Gasolina 93">Gasolina 93</option>
                        <option value="Gasolina 95">Gasolina 95</option>
                        <option value="Gasolina 97">Gasolina 97</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Kerosene">Kerosene</option>
                    </select>
                </td>
                <td><input type="number" name="litros[]" oninput="calculateTotals()"></td>
                <td><input type="number" name="precio[]" step="0.0001" oninput="calculateTotals()"></td>
                <td><input type="number" name="impuesto_base[]" step="0.0001" oninput="calculateTotals()"></td>
                <td><input type="number" name="impuesto_variable[]" step="0.0001" oninput="calculateTotals()"></td>
            `;

            tableBody.appendChild(newRow);
        }

        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".product-table input").forEach(input => {
                input.addEventListener("input", calculateTotals);
            });

            document.getElementById("add-row-btn").addEventListener("click", addRow);
        });
    </script>
</head>
<body>
    <div class="form-container">
        <h2>INGRESO DE FACTURAS</h2>
        <form action="procesar_factura.php" method="post" id="factura-form">
            <div class="form-group">
                <label for="empresa">Empresa</label>
                <select id="empresa" name="empresa_rut" required>
                    <?php if (empty($empresas)): ?>
                        <option value="">No hay empresas disponibles</option>
                    <?php else: ?>
                        <?php foreach ($empresas as $empresa): ?>
                            <option value="<?= htmlspecialchars($empresa['rut']) ?>"><?= htmlspecialchars($empresa['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="fecha">Fecha de Factura</label>
                <input type="date" id="fecha" name="fecha" required>
            </div>
            <div class="form-group">
                <label for="factura">N° Factura</label>
                <input type="text" id="factura" name="factura" required>
            </div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Combustible</th>
                        <th>Litros</th>
                        <th>Precio Bruto Unitario</th>
                        <th>Impuesto Base</th>
                        <th>Impuesto Variable</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="combustible[]">
                                <option value="Gasolina 93">Gasolina 93</option>
                                <option value="Gasolina 95">Gasolina 95</option>
                                <option value="Gasolina 97">Gasolina 97</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Kerosene">Kerosene</option>
                            </select>
                        </td>
                        <td><input type="number" name="litros[]" oninput="calculateTotals()"></td>
                        <td><input type="number" name="precio[]" step="0.0001" oninput="calculateTotals()"></td>
                        <td><input type="number" name="impuesto_base[]" step="0.0001" oninput="calculateTotals()"></td>
                        <td><input type="number" name="impuesto_variable[]" step="0.0001" oninput="calculateTotals()"></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" id="add-row-btn" class="add-row-btn">Agregar Fila</button>
            <div class="totals">
                <div class="form-group">
                    <label for="subtotal">Subtotal Neto</label>
                    <input type="number" id="subtotal" name="subtotal" readonly>
                </div>
                <div class="form-group">
                    <label for="iva">IVA-19%</label>
                    <input type="number" id="iva" name="iva" readonly>
                </div>
                <div class="form-group">
                    <label for="base-neto">Base Neto</label>
                    <input type="number" id="base-neto" name="base-neto" step="0.0001" readonly>
                </div>
                <div class="form-group">
                    <label for="variable-neto">Variable Neto</label>
                    <input type="number" id="variable-neto" name="variable-neto" step="0.0001" readonly>
                </div>
                <div class="form-group">
                    <label for="total">Total</label>
                    <input type="number" id="total" name="total" readonly>
                </div>
            </div>
            <button type="submit" class="submit-btn">Subir</button>
        </form>
        <form action="exportar_excel.php" method="post">
            <button type="submit" class="submit-btn">Exportar a Excel</button>
        </form>
    </div>
    <script>
        function resetForm() {
            document.getElementById("factura-form").reset();
            document.querySelectorAll(".product-table tbody tr").forEach((row, index) => {
                if (index > 0) row.remove();
            });
        }

        document.getElementById("factura-form").addEventListener("submit", function(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);

            fetch(form.action, {
                method: form.method,
                body: formData
            }).then(response => response.text())
              .then(result => {
                  alert(result);
                  resetForm();
              }).catch(error => {
                  console.error('Error:', error);
              });
        });
    </script>
</body>
</html>
