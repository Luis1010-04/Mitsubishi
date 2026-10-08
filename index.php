<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador Profesional de Diagrama de Ishikawa</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f6; padding: 30px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h1 { color: #1e3a8a; margin-bottom: 10px; font-size: 24px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        label { font-weight: 600; color: #334155; display: block; margin-bottom: 8px; }
        input[type="text"] { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .category-card { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #2563eb; padding: 15px; margin-bottom: 15px; border-radius: 6px; position: relative; }
        .cat-header { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .btn-remove { background: #ef4444; color: white; border: none; border-radius: 4px; padding: 6px 10px; cursor: pointer; font-size: 12px; }
        .btn-add-cause { background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer; margin-top: 8px; }
        .causes-container input { margin-bottom: 6px; }
        .btn-primary { background: #1d4ed8; color: white; border: none; padding: 12px 20px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; margin-top: 20px; }
        .btn-secondary { background: #3b82f6; color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Diagrama de Ishikawa Dinámico</h1>
    <p>Agrega solo las categorías que necesites y sus causas. Se generará un diagrama gráfico con espinas inclinadas auténticas.</p>

    <form action="generar_excel.php" method="POST">
        <div class="form-group">
            <label>Efecto / Problema Principal (Cabeza del Pescado):</label>
            <input type="text" name="problema" placeholder="Ej. Defecto de ensamble en línea de producción" required>
        </div>

        <button type="button" class="btn-secondary" onclick="agregarCategoria()">+ Agregar Categoría Personalizada</button>

        <div id="categories-wrapper">
            <!-- Categorías iniciales de ejemplo -->
        </div>

        <button type="submit" class="btn-primary">Generar y Descargar en Excel (.xlsx)</button>
    </form>
</div>

<script>
let catCount = 0;

function agregarCategoria(nombre = "") {
    catCount++;
    const wrapper = document.getElementById('categories-wrapper');
    const catDiv = document.createElement('div');
    catDiv.className = 'category-card';
    catDiv.id = `cat-card-${catCount}`;
    
    catDiv.innerHTML = `
        <div class="cat-header">
            <input type="text" name="categorias[${catCount}][nombre]" value="${nombre}" placeholder="Nombre de la Categoría (Ej. Mano de Obra, Software, etc.)" required style="font-weight: bold; color: #1e40af;">
            <button type="button" class="btn-remove" onclick="eliminarCategoria(${catCount})">Eliminar</button>
        </div>
        <div class="causes-container" id="causes-wrap-${catCount}">
            <input type="text" name="categorias[${catCount}][causas][]" placeholder="Causa 1..." required>
        </div>
        <button type="button" class="btn-add-cause" onclick="agregarCausa(${catCount})">+ Agregar Causa</button>
    `;
    wrapper.appendChild(catDiv);
}

function agregarCausa(catId) {
    const wrap = document.getElementById(`causes-wrap-${catId}`);
    const input = document.createElement('input');
    input.type = 'text';
    input.name = `categorias[${catId}][causas][]`;
    input.placeholder = 'Otra causa...';
    wrap.appendChild(input);
}

function eliminarCategoria(catId) {
    const el = document.getElementById(`cat-card-${catId}`);
    if (el) el.remove();
}

// Cargar 4 categorías iniciales por defecto
window.onload = function() {
    agregarCategoria("Mano de Obra");
    agregarCategoria("Maquinaria");
    agregarCategoria("Métodos");
    agregarCategoria("Materiales");
};
</script>

</body>
</html>