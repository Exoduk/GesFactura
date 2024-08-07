<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $empresa_rut = $_POST['empresa_rut'];
    $fecha = $_POST['fecha'];
    $factura = $_POST['factura'];
    $subtotal = $_POST['subtotal'];
    $iva = $_POST['iva'];
    $base_neto = $_POST['base-neto'];
    $variable_neto = $_POST['variable-neto'];
    $total = $_POST['total'];

    $combustibles = $_POST['combustible'];
    $litros = $_POST['litros'];
    $precios = $_POST['precio'];
    $impuestos_base = $_POST['impuesto_base'];
    $impuestos_variable = $_POST['impuesto_variable'];

    try {
        // Crear conexión a la base de datos
        $pdo = new PDO('sqlite:gescom.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insertar datos en la tabla facturas
        $stmt = $pdo->prepare("INSERT INTO facturas (factura, empresa_rut, fecha, subtotal, iva, base_neto, variable_neto, total) 
                               VALUES (:factura, :empresa_rut, :fecha, :subtotal, :iva, :base_neto, :variable_neto, :total)");
        $stmt->execute([
            ':factura' => $factura,
            ':empresa_rut' => $empresa_rut,
            ':fecha' => $fecha,
            ':subtotal' => $subtotal,
            ':iva' => $iva,
            ':base_neto' => $base_neto,
            ':variable_neto' => $variable_neto,
            ':total' => $total
        ]);

        // Insertar datos en la tabla detalles_factura
        $stmt_detalle = $pdo->prepare("INSERT INTO detalles_factura (factura, combustible, litros, precio, impuesto_base, impuesto_variable) 
                                       VALUES (:factura, :combustible, :litros, :precio, :impuesto_base, :impuesto_variable)");

        foreach ($combustibles as $index => $combustible) {
            $stmt_detalle->execute([
                ':factura' => $factura,
                ':combustible' => $combustible,
                ':litros' => $litros[$index],
                ':precio' => $precios[$index],
                ':impuesto_base' => $impuestos_base[$index],
                ':impuesto_variable' => $impuestos_variable[$index]
            ]);
        }

        echo "Factura y detalles insertados correctamente.";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
