<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Acceso denegado');
}

// Función para limpiar acentos si no hay fuentes TTF instaladas
function limpiarTexto($texto) {
    $originales = ['Á','É','Í','Ó','Ú','á','é','í','ó','ú','Ñ','ñ'];
    $reemplazos = ['A','E','I','O','U','a','e','i','o','u','N','n'];
    return str_replace($originales, $reemplazos, trim($texto));
}

// Función para envolver texto en múltiples líneas cortas
function wrapTextArray($text, $maxChars = 18) {
    $words = explode(' ', $text);
    $lines = [];
    $currentLine = '';

    foreach ($words as $word) {
        if (mb_strlen($currentLine . ' ' . $word) <= $maxChars) {
            $currentLine .= ($currentLine === '' ? '' : ' ') . $word;
        } else {
            if ($currentLine !== '') $lines[] = $currentLine;
            $currentLine = $word;
        }
    }
    if ($currentLine !== '') $lines[] = $currentLine;

    return $lines;
}

$problemaRaw = $_POST['problema'] ?? 'Problema no definido';
$problema = limpiarTexto($problemaRaw);
$categoriasRaw = $_POST['categorias'] ?? [];

$categorias = [];
foreach ($categoriasRaw as $c) {
    $nombre = limpiarTexto($c['nombre'] ?? '');
    $causasRaw = $c['causas'] ?? [];
    
    $causas = [];
    foreach ($causasRaw as $item) {
        $cleanItem = limpiarTexto($item);
        if ($cleanItem !== '') {
            $causas[] = $cleanItem;
        }
    }
    
    if (!empty($nombre)) {
        $categorias[] = [
            'nombre' => $nombre,
            'causas' => array_values($causas)
        ];
    }
}

$numCat = count($categorias);
if ($numCat === 0) {
    exit('Debes agregar al menos una categoría.');
}

$topCat = array_slice($categorias, 0, ceil($numCat / 2));
$bottomCat = array_slice($categorias, ceil($numCat / 2));

// Lienzo HD
$width = 1700;
$height = 900;
$img = imagecreatetruecolor($width, $height);

imagealphablending($img, true);
imageantialias($img, true);

// Colores
$bgColor      = imagecolorallocate($img, 255, 255, 255); 
$spineGold    = imagecolorallocate($img, 255, 204, 51);  
$boxGreen     = imagecolorallocate($img, 74, 124, 50);   
$boxOrange    = imagecolorallocate($img, 204, 85, 0);    
$lineDark     = imagecolorallocate($img, 50, 50, 50);    
$textWhite    = imagecolorallocate($img, 255, 255, 255);
$textDark     = imagecolorallocate($img, 20, 20, 20);

imagefilledrectangle($img, 0, 0, $width, $height, $bgColor);

$centerY = (int)($height / 2);
$headWidth = 280;
$headHeight = 160;
$headX = $width - $headWidth - 40;

function drawArrow($img, $x1, $y1, $x2, $y2, $color, $thickness = 2, $arrowSize = 7) {
    imagesetthickness($img, $thickness);
    imageline($img, $x1, $y1, $x2, $y2, $color);
    
    $angle = atan2($y2 - $y1, $x2 - $x1);
    $px1 = $x2 - $arrowSize * cos($angle - M_PI / 6);
    $py1 = $y2 - $arrowSize * sin($angle - M_PI / 6);
    $px2 = $x2 - $arrowSize * cos($angle + M_PI / 6);
    $py2 = $y2 - $arrowSize * sin($angle + M_PI / 6);
    
    $points = [(int)$x2, (int)$y2, (int)$px1, (int)$py1, (int)$px2, (int)$py2];
    imagefilledpolygon($img, $points, $color);
}

// 1. ESPINA CENTRAL (FLECHA DORADA)
$arrowBodyYTop = $centerY - 14;
$arrowBodyYBot = $centerY + 14;
$arrowHeadStartX = $headX - 45;

$spinePoly = [
    40, $arrowBodyYTop,
    $arrowHeadStartX, $arrowBodyYTop,
    $arrowHeadStartX, $centerY - 28,
    $headX - 5, $centerY, 
    $arrowHeadStartX, $centerY + 28,
    $arrowHeadStartX, $arrowBodyYBot,
    40, $arrowBodyYBot
];
imagefilledpolygon($img, $spinePoly, $spineGold);

// 2. CABEZA DEL PESCADO (PROBLEMA)
imagefilledrectangle($img, $headX, $centerY - ($headHeight/2), $headX + $headWidth, $centerY + ($headHeight/2), $boxOrange);

$fontPath = __DIR__ . '/arial.ttf';
$useTTF = file_exists($fontPath);

if ($useTTF) {
    imagettftext($img, 13, 0, $headX + 20, $centerY - 35, $textWhite, $fontPath, "PROBLEMA:");
    $lines = wrapTextArray($problema, 20);
    $yText = $centerY - 5;
    foreach ($lines as $l) {
        imagettftext($img, 11, 0, $headX + 20, $yText, $textWhite, $fontPath, $l);
        $yText += 22;
    }
} else {
    imagestring($img, 5, $headX + 15, $centerY - 50, "PROBLEMA:", $textWhite);
    $lines = wrapTextArray($problema, 22);
    $yText = $centerY - 20;
    foreach ($lines as $l) {
        imagestring($img, 4, $headX + 15, $yText, $l, $textWhite);
        $yText += 18;
    }
}

// 3. CATEGORÍAS SUPERIORES
$numTop = count($topCat);
$stepXTop = ($headX - 350) / max(1, $numTop);

foreach ($topCat as $i => $cat) {
    $startX = (int)(220 + $i * $stepXTop);
    $endX = $startX + 180;
    $startY = 100;
    
    imagesetthickness($img, 3);
    imageline($img, $startX, $startY, $endX, $centerY - 14, $lineDark);
    
    $boxW = 190;
    $boxH = 45;
    imagefilledrectangle($img, $startX - ($boxW/2), $startY - $boxH, $startX + ($boxW/2), $startY, $boxGreen);
    
    if ($useTTF) {
        imagettftext($img, 11, 0, $startX - ($boxW/2) + 12, $startY - 15, $textWhite, $fontPath, strtoupper($cat['nombre']));
    } else {
        imagestring($img, 5, $startX - 80, $startY - 30, strtoupper($cat['nombre']), $textWhite);
    }
    
    $causas = $cat['causas'];
    $numCausas = count($causas);
    
    if ($numCausas > 0) {
        // Causa 1
        $ratio1 = 0.4;
        $anchorX1 = (int)($startX + $ratio1 * ($endX - $startX));
        $anchorY1 = (int)($startY + $ratio1 * (($centerY - 14) - $startY));
        
        drawArrow($img, $anchorX1 - 140, $anchorY1, $anchorX1, $anchorY1, $lineDark, 2, 6);
        
        $linesC1 = wrapTextArray($causas[0], 16);
        $yPos = $anchorY1 - (count($linesC1) * 14);
        foreach ($linesC1 as $line) {
            if ($useTTF) {
                imagettftext($img, 10, 0, $anchorX1 - 270, $yPos, $textDark, $fontPath, $line);
            } else {
                imagestring($img, 3, $anchorX1 - 260, $yPos - 5, $line, $textDark);
            }
            $yPos += 15;
        }
        
        // Causa 2
        if ($numCausas >= 2) {
            $c2StartX = $anchorX1 - 90;
            $c2StartY = $anchorY1;
            $c2EndX = $anchorX1 - 40;
            $c2EndY = $anchorY1 + 55;
            
            imagesetthickness($img, 2);
            imageline($img, $c2StartX, $c2StartY, $c2EndX, $c2EndY, $lineDark);
            
            $linesC2 = wrapTextArray($causas[1], 16);
            $yPos = $c2EndY + 5;
            foreach ($linesC2 as $line) {
                if ($useTTF) {
                    imagettftext($img, 10, 0, $c2EndX - 30, $yPos + 10, $textDark, $fontPath, $line);
                } else {
                    imagestring($img, 3, $c2EndX - 30, $yPos, $line, $textDark);
                }
                $yPos += 15;
            }
            
            // Causa 3
            if ($numCausas >= 3) {
                $c3AnchorX = $c2StartX + 20;
                $c3AnchorY = $c2StartY + 25;
                drawArrow($img, $c3AnchorX, $c3AnchorY, $c3AnchorX - 90, $c3AnchorY, $lineDark, 2, 6);
                
                $linesC3 = wrapTextArray($causas[2], 16);
                $yPos = $c3AnchorY - (count($linesC3) * 14);
                foreach ($linesC3 as $line) {
                    if ($useTTF) {
                        imagettftext($img, 10, 0, $c3AnchorX - 220, $yPos, $textDark, $fontPath, $line);
                    } else {
                        imagestring($img, 3, $c3AnchorX - 210, $yPos - 5, $line, $textDark);
                    }
                    $yPos += 15;
                }
            }
        }
    }
}

// 4. CATEGORÍAS INFERIORES
$numBottom = count($bottomCat);
$stepXBottom = ($headX - 350) / max(1, $numBottom);

foreach ($bottomCat as $i => $cat) {
    $startX = (int)(220 + $i * $stepXBottom);
    $endX = $startX + 180;
    $startY = $height - 100;
    
    imagesetthickness($img, 3);
    imageline($img, $startX, $startY, $endX, $centerY + 14, $lineDark);
    
    $boxW = 190;
    $boxH = 45;
    imagefilledrectangle($img, $startX - ($boxW/2), $startY, $startX + ($boxW/2), $startY + $boxH, $boxGreen);
    
    if ($useTTF) {
        imagettftext($img, 11, 0, $startX - ($boxW/2) + 12, $startY + 28, $textWhite, $fontPath, strtoupper($cat['nombre']));
    } else {
        imagestring($img, 5, $startX - 80, $startY + 15, strtoupper($cat['nombre']), $textWhite);
    }
    
    $causas = $cat['causas'];
    $numCausas = count($causas);
    
    if ($numCausas > 0) {
        // Causa 1
        $ratio1 = 0.4;
        $anchorX1 = (int)($startX + $ratio1 * ($endX - $startX));
        $anchorY1 = (int)($startY - $ratio1 * ($startY - ($centerY + 14)));
        
        drawArrow($img, $anchorX1 - 140, $anchorY1, $anchorX1, $anchorY1, $lineDark, 2, 6);
        
        $linesC1 = wrapTextArray($causas[0], 16);
        $yPos = $anchorY1 - (count($linesC1) * 14);
        foreach ($linesC1 as $line) {
            if ($useTTF) {
                imagettftext($img, 10, 0, $anchorX1 - 270, $yPos, $textDark, $fontPath, $line);
            } else {
                imagestring($img, 3, $anchorX1 - 260, $yPos - 5, $line, $textDark);
            }
            $yPos += 15;
        }
        
        // Causa 2
        if ($numCausas >= 2) {
            $c2StartX = $anchorX1 - 90;
            $c2StartY = $anchorY1;
            $c2EndX = $anchorX1 - 40;
            $c2EndY = $anchorY1 - 55;
            
            imagesetthickness($img, 2);
            imageline($img, $c2StartX, $c2StartY, $c2EndX, $c2EndY, $lineDark);
            
            $linesC2 = wrapTextArray($causas[1], 16);
            $yPos = $c2EndY - 20;
            foreach ($linesC2 as $line) {
                if ($useTTF) {
                    imagettftext($img, 10, 0, $c2EndX - 30, $yPos, $textDark, $fontPath, $line);
                } else {
                    imagestring($img, 3, $c2EndX - 30, $yPos - 5, $line, $textDark);
                }
                $yPos += 15;
            }
            
            // Causa 3
            if ($numCausas >= 3) {
                $c3AnchorX = $c2StartX + 20;
                $c3AnchorY = $c2StartY - 25;
                drawArrow($img, $c3AnchorX, $c3AnchorY, $c3AnchorX - 90, $c3AnchorY, $lineDark, 2, 6);
                
                $linesC3 = wrapTextArray($causas[2], 16);
                $yPos = $c3AnchorY - (count($linesC3) * 14);
                foreach ($linesC3 as $line) {
                    if ($useTTF) {
                        imagettftext($img, 10, 0, $c3AnchorX - 220, $yPos, $textDark, $fontPath, $line);
                    } else {
                        imagestring($img, 3, $c3AnchorX - 210, $yPos - 5, $line, $textDark);
                    }
                    $yPos += 15;
                }
            }
        }
    }
}

// Guardar e Incrustar en Excel
$tmpImgPath = sys_get_temp_dir() . '/ishikawa_' . time() . '.png';
imagepng($img, $tmpImgPath);
imagedestroy($img);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Diagrama de Ishikawa");
$sheet->setShowGridLines(false);

$drawing = new Drawing();
$drawing->setName('Diagrama de Ishikawa');
$drawing->setDescription('Diagrama Causa y Efecto');
$drawing->setPath($tmpImgPath);
$drawing->setCoordinates('B2');
$drawing->setHeight(650);
$drawing->setWorksheet($sheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Diagrama_Ishikawa_Grafico.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

@unlink($tmpImgPath);
exit;