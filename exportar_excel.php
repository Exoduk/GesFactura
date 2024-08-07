<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

try {
    $pdo = new PDO('sqlite:gescom.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener las facturas y los nombres de las empresas
    $stmt = $pdo->query("SELECT facturas.*, empresas.nombre, empresas.rut FROM facturas JOIN empresas ON facturas.empresa_rut = empresas.rut");
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener los detalles de las facturas
    $stmt_detalle = $pdo->query("SELECT * FROM detalles_factura");
    $detalles_factura = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);

    // Crear un arreglo de detalles indexado por el número de factura
    $detalles_indexados = [];
    foreach ($detalles_factura as $detalle) {
        $detalles_indexados[$detalle['factura']][] = $detalle;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados
    $headers = ['Fecha', 'N° Factura', 'Proveedor', 'Rut', 'Tipo de Combustible', 'Litros', 'Precio Bruto Unit.', 'Impuesto Base', 'Impuesto Variable', 'Neto', 'IVA', 'Base Neto', 'Variable Neto', 'Total'];
    $column = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($column . '1', $header);
        $column++;
    }

    // Estilos para la primera fila
    $headerStyleArray = [
        'font' => [
            'bold' => true,
            'color' => ['argb' => Color::COLOR_BLACK],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFFF00'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => '00000000'],
            ],
        ],
    ];
    $sheet->getStyle('A1:N1')->applyFromArray($headerStyleArray);

    // Estilos para el contenido
    $contentStyleArray = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => '00000000'],
            ],
        ],
    ];

    $row = 2;
    foreach ($facturas as $factura) {
        // Número de filas que ocupará esta factura
        $rowCount = count($detalles_indexados[$factura['factura']]);

        // Combinar celdas para columnas comunes
        $sheet->mergeCells("A{$row}:A" . ($row + $rowCount - 1));
        $sheet->mergeCells("B{$row}:B" . ($row + $rowCount - 1));
        $sheet->mergeCells("C{$row}:C" . ($row + $rowCount - 1));
        $sheet->mergeCells("D{$row}:D" . ($row + $rowCount - 1));
        $sheet->mergeCells("J{$row}:J" . ($row + $rowCount - 1));
        $sheet->mergeCells("K{$row}:K" . ($row + $rowCount - 1));
        $sheet->mergeCells("L{$row}:L" . ($row + $rowCount - 1));
        $sheet->mergeCells("M{$row}:M" . ($row + $rowCount - 1));
        $sheet->mergeCells("N{$row}:N" . ($row + $rowCount - 1));

        // Datos comunes de la factura
        $sheet->setCellValue("A{$row}", $factura['fecha']);
        $sheet->setCellValue("B{$row}", $factura['factura']);
        $sheet->setCellValue("C{$row}", $factura['nombre']);
        $sheet->setCellValue("D{$row}", $factura['rut']);
        $sheet->setCellValue("J{$row}", $factura['subtotal']);
        $sheet->setCellValue("K{$row}", $factura['iva']);
        $sheet->setCellValue("L{$row}", $factura['base_neto']);
        $sheet->setCellValue("M{$row}", $factura['variable_neto']);
        $sheet->setCellValue("N{$row}", $factura['total']);

        // Datos de los detalles de la factura
        foreach ($detalles_indexados[$factura['factura']] as $detalle) {
            $sheet->setCellValue("E{$row}", $detalle['combustible']);
            $sheet->setCellValue("F{$row}", $detalle['litros']);
            $sheet->setCellValue("G{$row}", $detalle['precio']);
            $sheet->setCellValue("H{$row}", $detalle['impuesto_base']);
            $sheet->setCellValue("I{$row}", $detalle['impuesto_variable']);
            $row++;
        }
    }

    // Aplicar el estilo del contenido a todas las celdas
    $sheet->getStyle("A2:N{$row}")->applyFromArray($contentStyleArray);

    // Ajustar automáticamente el ancho de las columnas
    foreach (range('A', 'N') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);
    $fileName = 'export_facturas.xlsx';
    $writer->save($fileName);

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.basename($fileName).'"');
    header('Content-Length: ' . filesize($fileName));
    readfile($fileName);

    // Eliminar el archivo después de la descarga
    unlink($fileName);
    exit;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
