<?php
/**
 * DECIMALES DE E - BeayPepe Tools
 * Herramienta para explorar los decimales del número e (Euler)
 */

$raiz = $_SERVER['DOCUMENT_ROOT'];

// ============================================
// MANEJO DE PETICIONES AJAX
// ============================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $archivo_decimales = './pi_decimals.txt';
    
    // Verificar que existe el archivo
    if (!file_exists($archivo_decimales)) {
        echo json_encode(['error' => 'Archivo de decimales no encontrado']);
        exit;
    }
    
    $action = $_GET['action'];
    
    // Obtener decimales (para mostrar primeros N o rango)
    if ($action === 'getDigits') {
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
        $count = isset($_GET['count']) ? (int)$_GET['count'] : 1000;
        
        // Limitar para evitar sobrecarga
        $count = min($count, 100000);
        
        $handle = fopen($archivo_decimales, 'r');
        fseek($handle, $start);
        $digits = fread($handle, $count);
        fclose($handle);
        
        echo json_encode([
            'digits' => $digits,
            'start' => $start,
            'count' => strlen($digits)
        ]);
        exit;
    }
    
    // Buscar secuencia
    if ($action === 'search') {
        $sequence = isset($_GET['sequence']) ? $_GET['sequence'] : '';
        $sequence = preg_replace('/\D/', '', $sequence); // Solo dígitos
        
        if (empty($sequence)) {
            echo json_encode(['error' => 'Secuencia vacía']);
            exit;
        }
        
        // Leer el archivo completo para búsqueda
        $contenido = file_get_contents($archivo_decimales);
        $posicion = strpos($contenido, $sequence);
        
        if ($posicion !== false) {
            // Obtener contexto (20 caracteres antes y después)
            $contexto_inicio = max(0, $posicion - 20);
            $contexto_longitud = strlen($sequence) + 40;
            $contexto = substr($contenido, $contexto_inicio, $contexto_longitud);
            
            echo json_encode([
                'found' => true,
                'position' => $posicion + 1, // Base 1 para el usuario
                'context' => $contexto,
                'contextStart' => $contexto_inicio,
                'sequenceStartInContext' => $posicion - $contexto_inicio
            ]);
        } else {
            echo json_encode([
                'found' => false,
                'searchedDigits' => strlen($contenido)
            ]);
        }
        exit;
    }
    
    // Análisis de frecuencia
    if ($action === 'frequency') {
        $start = isset($_GET['start']) ? (int)$_GET['start'] - 1 : 0; // Convertir a base 0
        $end = isset($_GET['end']) ? (int)$_GET['end'] : 1000;
        $count = $end - $start;
        
        // Limitar
        $count = min($count, 10000000);
        
        $handle = fopen($archivo_decimales, 'r');
        fseek($handle, $start);
        $digits = fread($handle, $count);
        fclose($handle);
        
        // Contar frecuencias
        $freq = array_fill(0, 10, 0);
        $len = strlen($digits);
        for ($i = 0; $i < $len; $i++) {
            $d = (int)$digits[$i];
            $freq[$d]++;
        }
        
        echo json_encode([
            'frequency' => $freq,
            'totalDigits' => $len,
            'start' => $start + 1,
            'end' => $start + $len
        ]);
        exit;
    }
    
    // Obtener total de decimales disponibles
    if ($action === 'getTotal') {
        $size = filesize($archivo_decimales);
        echo json_encode(['total' => $size]);
        exit;
    }
    
    echo json_encode(['error' => 'Acción no válida']);
    exit;
}

// ============================================
// PÁGINA PRINCIPAL
// ============================================

// 1. CONFIGURACIÓN DE LA PÁGINA
$pageTitle = "Decimales de e"; 
$metaDescription = "Herramienta interactiva para explorar los decimales del número e (Euler). Busca secuencias, analiza frecuencias y descubre los secretos de e ≈ 2.71828...";
$nivel_acceso = 'public';

// 2. CARGA DEL HEADER
include $raiz . '/includes/header.php';
?>

<div class="tool-container">
    <!-- BLOQUE IZQUIERDO: CONTROLES -->
    <div class="controls-panel">
        <h2>Decimales de <span class="numero-symbol">e</span></h2>
        
        <!-- Herramienta 1: Número de decimales -->
        <div class="tool-section">
            <h3><i class="fas fa-list-ol"></i> Primeros N decimales</h3>
            <p class="tool-description">Muestra los primeros decimales de e</p>
            <div class="input-group">
                <input type="number" id="numDecimals" min="1" max="10000000" placeholder="Ej: 1000">
                <button onclick="getPrimeroNDecimales()" class="btn-primary">Mostrar</button>
            </div>
        </div>

        <!-- Herramienta 2: Decimal en posición específica -->
        <div class="tool-section">
            <h3><i class="fas fa-crosshairs"></i> Decimal en posición</h3>
            <p class="tool-description">Muestra el dígito en una posición específica</p>
            <div class="input-group">
                <input type="number" id="posicionEspecifica" min="1" max="10000000" placeholder="Ej: 15">
                <button onclick="getDecimalEnPosicion()" class="btn-primary">Buscar</button>
            </div>
        </div>

        <!-- Herramienta 3: Rango de posiciones -->
        <div class="tool-section">
            <h3><i class="fas fa-arrows-alt-h"></i> Rango de posiciones</h3>
            <p class="tool-description">Muestra decimales entre dos posiciones</p>
            <div class="input-group-double">
                <input type="number" id="rangoInicio" min="1" placeholder="Desde">
                <input type="number" id="rangoFin" min="1" placeholder="Hasta">
            </div>
            <button onclick="getRango()" class="btn-primary btn-full">Mostrar rango</button>
        </div>

        <!-- Herramienta 4: Buscar secuencia -->
        <div class="tool-section">
            <h3><i class="fas fa-search"></i> Buscar secuencia</h3>
            <p class="tool-description">Encuentra una secuencia de números en e (se extraerán solo los dígitos)</p>
            <div class="input-group">
                <input type="text" id="secuenciaBuscar" placeholder="Ej: 718281 o cualquier texto">
                <button onclick="buscarSecuencia()" class="btn-primary">Buscar</button>
            </div>
        </div>

        <!-- Herramienta 5: Frecuencia de dígitos -->
        <div class="tool-section">
            <h3><i class="fas fa-chart-bar"></i> Frecuencia de dígitos</h3>
            <p class="tool-description">Analiza la distribución de dígitos en un rango</p>
            <div class="input-group-double">
                <input type="number" id="freqInicio" min="1" placeholder="Desde">
                <input type="number" id="freqFin" min="1" placeholder="Hasta">
            </div>
            <button onclick="analizarFrecuencia()" class="btn-primary btn-full">Analizar</button>
        </div>
    </div>

    <!-- BLOQUE DERECHO: RESULTADOS -->
    <div class="results-panel">
        <div class="results-header">
            <h3><i class="fas fa-poll"></i> Resultados</h3>
            <div class="export-buttons">
                <button onclick="copiarResultado()" class="btn-export" title="Copiar al portapapeles">
                    <i class="fas fa-copy"></i> Copiar
                </button>
                <button onclick="descargarResultado()" class="btn-export" title="Descargar como TXT">
                    <i class="fas fa-download"></i> Descargar
                </button>
            </div>
        </div>
        
        <div class="results-container" id="resultsContainer">
            <div class="results-content" id="resultsContent">
                <div class="placeholder-message">
                    <span class="big-numero">e</span>
                    <p>Selecciona una herramienta para comenzar a explorar los decimales del número e</p>
                    <p class="api-info">Número de Euler: e ≈ 2.71828...<br>10 millones de decimales disponibles</p>
                </div>
            </div>
            <!-- Sentinel para lazy loading -->
            <div id="loadingSentinel" class="loading-sentinel"></div>
        </div>
        
        <!-- Indicador de carga -->
        <div id="loadingIndicator" class="loading-indicator hidden">
            <div class="spinner"></div>
            <span>Cargando decimales...</span>
        </div>
        
        <!-- Info del resultado actual -->
        <div id="resultInfo" class="result-info hidden"></div>
    </div>
</div>

<style>
    /* ===== LAYOUT PRINCIPAL ===== */
    .tool-container {
        display: grid;
        grid-template-columns: 420px 1fr;
        gap: 25px;
        width: 100%;
        align-items: start;
    }

    /* ===== PANEL DE CONTROLES ===== */
    .controls-panel {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        box-sizing: border-box;
        overflow: hidden;
    }

    .controls-panel h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #02874d;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .numero-symbol {
        font-size: 2rem;
        font-weight: bold;
        font-style: italic;
        color: #5f452a;
    }

    .tool-section {
        background: #f8fdf9;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #d4edda;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .tool-section:last-child {
        margin-bottom: 0;
    }

    .tool-section:hover {
        border-color: #02874d;
        box-shadow: 0 2px 8px rgba(2, 135, 77, 0.15);
    }

    .tool-section h3 {
        font-size: 1rem;
        margin-bottom: 8px;
        color: #5f452a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .tool-section h3 i {
        color: #02874d;
        width: 18px;
        text-align: center;
    }

    .tool-description {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 12px;
    }

    .input-group {
        display: flex;
        gap: 10px;
        width: 100%;
        box-sizing: border-box;
    }

    .input-group input {
        flex: 1;
        min-width: 0;
        padding: 10px 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
        background: #ffffff;
        color: #333;
        font-size: 0.95rem;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .input-group input:focus {
        outline: none;
        border-color: #02874d;
        box-shadow: 0 0 0 3px rgba(2, 135, 77, 0.1);
    }

    .input-group input::placeholder {
        color: #999;
    }

    .input-group-double {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        width: 100%;
        box-sizing: border-box;
    }

    .input-group-double input {
        flex: 1;
        min-width: 0;
        padding: 10px 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
        background: #ffffff;
        color: #333;
        font-size: 0.95rem;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .input-group-double input:focus {
        outline: none;
        border-color: #02874d;
        box-shadow: 0 0 0 3px rgba(2, 135, 77, 0.1);
    }

    .input-group-double input::placeholder {
        color: #999;
    }

    .btn-primary {
        padding: 10px 18px;
        background: #02874d;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-primary:hover {
        background: #026b3e;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(2, 135, 77, 0.3);
    }

    .btn-full {
        width: 100%;
    }

    /* ===== PANEL DE RESULTADOS ===== */
    .results-panel {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        box-sizing: border-box;
        min-width: 0;
        height: fit-content;
        max-height: calc(100vh - 150px);
        position: sticky;
        top: 100px;
    }

    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
        flex-wrap: wrap;
        gap: 10px;
        flex-shrink: 0;
    }

    .results-header h3 {
        margin: 0;
        color: #02874d;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .results-header h3 i {
        color: #5f452a;
    }

    .export-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-export {
        padding: 8px 14px;
        background: #f8fdf9;
        color: #5f452a;
        border: 1px solid #d4edda;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-export:hover {
        background: #02874d;
        color: white;
        border-color: #02874d;
    }

    .btn-export i {
        font-size: 0.9rem;
    }

    .results-container {
        flex: 1;
        background: #f8fdf9;
        border-radius: 8px;
        padding: 20px;
        overflow-y: auto;
        border: 1px solid #d4edda;
        min-height: 200px;
    }

    .results-content {
        font-family: 'Courier New', Courier, monospace;
        font-size: 1rem;
        line-height: 1.6;
        word-break: break-all;
        color: #333;
    }

    .placeholder-message {
        text-align: center;
        color: #666;
        padding: 40px 20px;
    }

    .big-numero {
        font-size: 5rem;
        font-style: italic;
        color: #02874d;
        opacity: 0.3;
        display: block;
        margin-bottom: 20px;
    }

    .api-info {
        font-size: 0.8rem;
        margin-top: 15px;
        opacity: 0.6;
    }

    /* Estilos para resultados especiales */
    .result-position {
        background: linear-gradient(135deg, #02874d 0%, #03a65e 100%);
        padding: 30px;
        border-radius: 12px;
        text-align: center;
        color: white;
    }

    .result-position .position-label {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 10px;
    }

    .result-position .position-digit {
        font-size: 5rem;
        font-weight: bold;
    }

    .result-search {
        padding: 20px;
    }

    .result-search .search-found {
        background: linear-gradient(135deg, #02874d 0%, #03a65e 100%);
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 15px;
        color: white;
    }

    .result-search .search-not-found {
        background: linear-gradient(135deg, #5f452a 0%, #7a5a38 100%);
        padding: 20px;
        border-radius: 10px;
        color: white;
    }

    .search-sequence {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .search-position {
        font-size: 1.1rem;
        opacity: 0.95;
    }

    .context-display {
        background: #ffffff;
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
        font-family: 'Courier New', monospace;
        font-size: 1rem;
        word-break: break-all;
        border: 1px solid #e0e0e0;
    }

    .context-display .highlight {
        background: #02874d;
        color: #fff;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: bold;
    }

    /* Frecuencia de dígitos */
    .frequency-container {
        padding: 10px 0;
    }

    .frequency-bar {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
    }

    .frequency-digit {
        width: 30px;
        font-size: 1.2rem;
        font-weight: bold;
        color: #5f452a;
    }

    .frequency-bar-wrapper {
        flex: 1;
        height: 28px;
        background: #e9f5ec;
        border-radius: 6px;
        overflow: hidden;
        margin: 0 15px;
    }

    .frequency-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #02874d, #03a65e);
        border-radius: 6px;
        transition: width 0.5s ease;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 10px;
        min-width: 40px;
    }

    .frequency-bar-fill span {
        font-size: 0.8rem;
        font-weight: bold;
        color: white;
    }

    .frequency-stats {
        width: 120px;
        text-align: right;
        font-size: 0.9rem;
        color: #666;
    }

    .frequency-total {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #e0e0e0;
        text-align: center;
        color: #666;
    }

    /* Loading */
    .loading-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 15px;
        color: #666;
        flex-shrink: 0;
    }

    .loading-indicator.hidden {
        display: none;
    }

    .spinner {
        width: 20px;
        height: 20px;
        border: 3px solid #e0e0e0;
        border-top-color: #02874d;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .loading-sentinel {
        height: 20px;
        visibility: hidden;
    }

    .result-info {
        margin-top: 10px;
        padding: 10px 15px;
        background: #f8fdf9;
        border-radius: 6px;
        font-size: 0.85rem;
        color: #666;
        text-align: center;
        border: 1px solid #d4edda;
        flex-shrink: 0;
    }

    .result-info.hidden {
        display: none;
    }

    /* Prefijo del número */
    .numero-prefix {
        color: #02874d;
        font-weight: bold;
        font-size: 1.1rem;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 900px) {
        .tool-container {
            grid-template-columns: 1fr;
        }

        .controls-panel {
            order: 1;
        }

        .results-panel {
            order: 2;
            position: static;
            max-height: 500px;
        }
    }

    @media (max-width: 500px) {
        .input-group {
            flex-direction: column;
        }

        .input-group-double {
            flex-direction: column;
        }

        .export-buttons {
            flex-direction: column;
            gap: 5px;
            width: 100%;
        }

        .btn-export {
            justify-content: center;
        }

        .results-header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        .frequency-stats {
            width: 80px;
            font-size: 0.75rem;
        }
    }
</style>

<script>
    /**
     * DECIMALES DE E - Lógica JavaScript
     * Usa archivo local con 10 millones de decimales
     */

    // Configuración
    const INITIAL_LOAD = 10000;
    const LOAD_MORE = 5000;
    const INTEGER_PART = '2';
    const NUMERO_NOMBRE = 'e';
    const MAX_DECIMALES = 10000000;

    // Estado global
    let currentState = {
        type: null,
        totalDigits: 0,
        loadedDigits: 0,
        startPosition: 0,
        data: '',
        isLoading: false,
        searchSequence: null,
        rawData: ''
    };

    // Elementos del DOM
    const resultsContainer = document.getElementById('resultsContainer');
    const resultsContent = document.getElementById('resultsContent');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const loadingSentinel = document.getElementById('loadingSentinel');
    const resultInfo = document.getElementById('resultInfo');

    // Intersection Observer para lazy loading
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !currentState.isLoading) {
            if (currentState.type === 'decimals' || currentState.type === 'range') {
                if (currentState.loadedDigits < currentState.totalDigits) {
                    loadMoreDigits();
                }
            }
        }
    }, { threshold: 0.1 });

    observer.observe(loadingSentinel);

    /**
     * Obtiene dígitos desde el servidor
     */
    async function fetchDigits(start, count) {
        try {
            const response = await fetch(`?action=getDigits&start=${start}&count=${count}`);
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            return data.digits;
        } catch (error) {
            console.error('Error fetching digits:', error);
            throw error;
        }
    }

    /**
     * Muestra el indicador de carga
     */
    function showLoading(show = true, message = 'Cargando decimales...') {
        loadingIndicator.classList.toggle('hidden', !show);
        loadingIndicator.querySelector('span').textContent = message;
        currentState.isLoading = show;
    }

    /**
     * Actualiza la información del resultado
     */
    function updateResultInfo() {
        if (currentState.type === 'decimals' || currentState.type === 'range') {
            resultInfo.classList.remove('hidden');
            resultInfo.innerHTML = `
                Mostrando <strong>${currentState.loadedDigits.toLocaleString()}</strong> 
                de <strong>${currentState.totalDigits.toLocaleString()}</strong> decimales
                ${currentState.loadedDigits < currentState.totalDigits ? ' (desplázate para cargar más)' : ' <i class="fas fa-check-circle" style="color: #02874d;"></i>'}
            `;
        } else {
            resultInfo.classList.add('hidden');
        }
    }

    /**
     * Formatea los dígitos para mostrar
     */
    function formatDigits(digits, showPrefix = true) {
        let html = showPrefix ? '<span class="numero-prefix">' + INTEGER_PART + '.</span>' : '';
        html += digits;
        return html;
    }

    /**
     * HERRAMIENTA 1: Primeros N decimales
     */
    async function getPrimeroNDecimales() {
        const n = parseInt(document.getElementById('numDecimals').value);
        
        if (!n || n < 1) {
            mostrarError('Por favor, introduce un número válido mayor que 0');
            return;
        }

        if (n > MAX_DECIMALES) {
            mostrarError(`El máximo disponible es ${MAX_DECIMALES.toLocaleString()} decimales`);
            return;
        }

        currentState = {
            type: 'decimals',
            totalDigits: n,
            loadedDigits: 0,
            startPosition: 0,
            data: '',
            isLoading: false,
            rawData: ''
        };

        resultsContent.innerHTML = '<span class="numero-prefix">' + INTEGER_PART + '.</span>';
        showLoading(true);
        resultsContainer.scrollTop = 0;

        try {
            const digitsToLoad = Math.min(n, INITIAL_LOAD);
            const digits = await fetchDigits(0, digitsToLoad);
            
            currentState.data = digits;
            currentState.loadedDigits = digits.length;
            currentState.rawData = INTEGER_PART + '.' + digits;

            resultsContent.innerHTML = formatDigits(digits, true);
            updateResultInfo();
        } catch (error) {
            mostrarError('Error al obtener los decimales. Inténtalo de nuevo.');
        } finally {
            showLoading(false);
        }
    }

    /**
     * HERRAMIENTA 2: Decimal en posición específica
     */
    async function getDecimalEnPosicion() {
        const pos = parseInt(document.getElementById('posicionEspecifica').value);
        
        if (!pos || pos < 1) {
            mostrarError('Por favor, introduce una posición válida (mayor que 0)');
            return;
        }

        if (pos > MAX_DECIMALES) {
            mostrarError(`La posición máxima disponible es ${MAX_DECIMALES.toLocaleString()}`);
            return;
        }

        currentState = { type: 'position', rawData: '' };
        showLoading(true);

        try {
            const internalPos = pos - 1;
            const contextStart = Math.max(0, internalPos - 10);
            const contextLength = 21;
            const digits = await fetchDigits(contextStart, contextLength);
            
            const targetIndex = internalPos - contextStart;
            const targetDigit = digits[targetIndex];
            
            currentState.rawData = targetDigit;

            resultsContent.innerHTML = `
                <div class="result-position">
                    <div class="position-label">Decimal en la posición ${pos.toLocaleString()}</div>
                    <div class="position-digit">${targetDigit}</div>
                </div>
                <div class="context-display" style="margin-top: 20px;">
                    <p style="margin-bottom: 10px; color: #666;"><i class="fas fa-info-circle"></i> Contexto:</p>
                    ${formatContext(digits, targetIndex)}
                </div>
            `;
            resultInfo.classList.add('hidden');
        } catch (error) {
            mostrarError('Error al obtener el decimal. Inténtalo de nuevo.');
        } finally {
            showLoading(false);
        }
    }

    /**
     * Formatea el contexto resaltando el dígito buscado
     */
    function formatContext(digits, highlightIndex) {
        let html = '...';
        for (let i = 0; i < digits.length; i++) {
            if (i === highlightIndex) {
                html += `<span class="highlight">${digits[i]}</span>`;
            } else {
                html += digits[i];
            }
        }
        html += '...';
        return html;
    }

    /**
     * HERRAMIENTA 3: Rango de posiciones
     */
    async function getRango() {
        const inicio = parseInt(document.getElementById('rangoInicio').value);
        const fin = parseInt(document.getElementById('rangoFin').value);
        
        if (!inicio || !fin || inicio < 1 || fin < 1) {
            mostrarError('Por favor, introduce posiciones válidas');
            return;
        }

        if (inicio > fin) {
            mostrarError('La posición inicial debe ser menor o igual a la final');
            return;
        }

        if (fin > MAX_DECIMALES) {
            mostrarError(`La posición máxima disponible es ${MAX_DECIMALES.toLocaleString()}`);
            return;
        }

        const totalDigits = fin - inicio + 1;

        currentState = {
            type: 'range',
            totalDigits: totalDigits,
            loadedDigits: 0,
            startPosition: inicio - 1,
            data: '',
            isLoading: false,
            rawData: ''
        };

        resultsContent.innerHTML = `<p style="color: #666; margin-bottom: 15px;"><i class="fas fa-arrows-alt-h"></i> Decimales del ${inicio.toLocaleString()} al ${fin.toLocaleString()}:</p>`;
        showLoading(true);
        resultsContainer.scrollTop = 0;

        try {
            const digitsToLoad = Math.min(totalDigits, INITIAL_LOAD);
            const digits = await fetchDigits(inicio - 1, digitsToLoad);
            
            currentState.data = digits;
            currentState.loadedDigits = digits.length;
            currentState.rawData = digits;

            resultsContent.innerHTML += formatDigits(digits, false);
            updateResultInfo();
        } catch (error) {
            mostrarError('Error al obtener el rango. Inténtalo de nuevo.');
        } finally {
            showLoading(false);
        }
    }

    /**
     * HERRAMIENTA 4: Buscar secuencia
     */
    async function buscarSecuencia() {
        const input = document.getElementById('secuenciaBuscar').value;
        const sequence = input.replace(/\D/g, '');
        
        if (!sequence || sequence.length === 0) {
            mostrarError('Por favor, introduce una secuencia que contenga al menos un número');
            return;
        }

        currentState = { type: 'search', searchSequence: sequence, rawData: '' };
        showLoading(true, 'Buscando secuencia...');

        try {
            const response = await fetch(`?action=search&sequence=${encodeURIComponent(sequence)}`);
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (data.found) {
                currentState.rawData = `Secuencia "${sequence}" encontrada en posición ${data.position}`;

                const context = data.context;
                const seqStart = data.sequenceStartInContext;

                resultsContent.innerHTML = `
                    <div class="result-search">
                        <div class="search-found">
                            <div class="search-sequence"><i class="fas fa-check-circle"></i> ¡Encontrado!</div>
                            <div class="search-position">
                                La secuencia <strong>${sequence}</strong> aparece en la posición <strong>${data.position.toLocaleString()}</strong>
                            </div>
                        </div>
                        <div style="margin-top: 15px; color: #666;">
                            <p><i class="fas fa-keyboard"></i> Texto original: "${input}"</p>
                            <p><i class="fas fa-search"></i> Secuencia buscada: ${sequence}</p>
                        </div>
                        <div class="context-display">
                            <p style="margin-bottom: 10px; color: #666;"><i class="fas fa-map-marker-alt"></i> Ubicación en ${NUMERO_NOMBRE}:</p>
                            ${formatSearchContext(context, seqStart, sequence.length)}
                        </div>
                    </div>
                `;
            } else {
                currentState.rawData = `Secuencia "${sequence}" no encontrada en los ${data.searchedDigits.toLocaleString()} decimales disponibles`;
                
                resultsContent.innerHTML = `
                    <div class="result-search">
                        <div class="search-not-found">
                            <div class="search-sequence"><i class="fas fa-times-circle"></i> No encontrado</div>
                            <div class="search-position">
                                La secuencia <strong>${sequence}</strong> no se encontró en los ${data.searchedDigits.toLocaleString()} decimales disponibles
                            </div>
                        </div>
                        <div style="margin-top: 15px; color: #666;">
                            <p><i class="fas fa-keyboard"></i> Texto original: "${input}"</p>
                            <p><i class="fas fa-search"></i> Secuencia buscada: ${sequence}</p>
                        </div>
                    </div>
                `;
            }
            resultInfo.classList.add('hidden');
        } catch (error) {
            mostrarError('Error en la búsqueda. Inténtalo de nuevo.');
        } finally {
            showLoading(false);
        }
    }

    /**
     * Formatea contexto de búsqueda con highlight
     */
    function formatSearchContext(context, startIndex, length) {
        const before = context.slice(0, startIndex);
        const match = context.slice(startIndex, startIndex + length);
        const after = context.slice(startIndex + length);
        
        return `...${before}<span class="highlight">${match}</span>${after}...`;
    }

    /**
     * HERRAMIENTA 5: Buscar cumpleaños (formato DD/MM/AA)
     */
    async function buscarCumpleanos() {
        const input = document.getElementById('fechaCumple').value.trim();
        
        const dateMatch = input.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/);
        
        if (!dateMatch) {
            mostrarError('Por favor, introduce una fecha válida en formato DD/MM/AA');
            return;
        }

        const day = dateMatch[1].padStart(2, '0');
        const month = dateMatch[2].padStart(2, '0');
        const yearFull = dateMatch[3];
        const year = yearFull.length === 4 ? yearFull.slice(-2) : yearFull.padStart(2, '0');
        const sequence = day + month + year;

        currentState = { type: 'search', searchSequence: sequence, rawData: '' };
        
        showLoading(true, 'Buscando tu cumpleaños...');

        try {
            const response = await fetch(`?action=search&sequence=${encodeURIComponent(sequence)}`);
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (data.found) {
                currentState.rawData = `Fecha ${day}/${month}/${year} encontrada en posición ${data.position}`;

                const context = data.context;
                const seqStart = data.sequenceStartInContext;

                resultsContent.innerHTML = `
                    <div class="result-search">
                        <div class="search-found">
                            <div class="search-sequence"><i class="fas fa-birthday-cake"></i> ¡Tu cumpleaños está en ${NUMERO_NOMBRE}!</div>
                            <div class="search-position">
                                La fecha <strong>${day}/${month}/${year}</strong> (${sequence}) aparece en la posición <strong>${data.position.toLocaleString()}</strong>
                            </div>
                        </div>
                        <div class="context-display">
                            <p style="margin-bottom: 10px; color: #666;"><i class="fas fa-map-marker-alt"></i> Ubicación en ${NUMERO_NOMBRE}:</p>
                            ${formatSearchContext(context, seqStart, sequence.length)}
                        </div>
                    </div>
                `;
            } else {
                currentState.rawData = `Fecha ${day}/${month}/${year} no encontrada`;
                
                resultsContent.innerHTML = `
                    <div class="result-search">
                        <div class="search-not-found">
                            <div class="search-sequence"><i class="fas fa-frown"></i> No encontrado</div>
                            <div class="search-position">
                                La fecha <strong>${day}/${month}/${year}</strong> (${sequence}) no se encontró en los ${data.searchedDigits.toLocaleString()} decimales disponibles
                            </div>
                        </div>
                    </div>
                `;
            }
            resultInfo.classList.add('hidden');
        } catch (error) {
            mostrarError('Error en la búsqueda. Inténtalo de nuevo.');
        } finally {
            showLoading(false);
        }
    }

    /**
     * HERRAMIENTA 6: Frecuencia de dígitos
     */
    async function analizarFrecuencia() {
        const inicio = parseInt(document.getElementById('freqInicio').value);
        const fin = parseInt(document.getElementById('freqFin').value);
        
        if (!inicio || !fin || inicio < 1 || fin < 1) {
            mostrarError('Por favor, introduce posiciones válidas');
            return;
        }

        if (inicio > fin) {
            mostrarError('La posición inicial debe ser menor o igual a la final');
            return;
        }

        if (fin > MAX_DECIMALES) {
            mostrarError(`La posición máxima disponible es ${MAX_DECIMALES.toLocaleString()}`);
            return;
        }

        const totalDigits = fin - inicio + 1;

        currentState = { type: 'frequency', rawData: '' };
        showLoading(true, 'Analizando distribución...');

        try {
            const response = await fetch(`?action=frequency&start=${inicio}&end=${fin}`);
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }

            const freq = data.frequency;
            const total = data.totalDigits;
            const maxFreq = Math.max(...freq);
            
            let statsText = `Análisis de frecuencia (posiciones ${inicio} a ${fin}):\n\n`;
            let html = `
                <div class="frequency-container">
                    <p style="margin-bottom: 20px; color: #666;">
                        <i class="fas fa-chart-bar"></i> Distribución de dígitos entre las posiciones 
                        <strong>${inicio.toLocaleString()}</strong> y <strong>${fin.toLocaleString()}</strong>
                        (${total.toLocaleString()} decimales analizados)
                    </p>
            `;

            for (let i = 0; i <= 9; i++) {
                const percentage = ((freq[i] / total) * 100).toFixed(2);
                const barWidth = (freq[i] / maxFreq) * 100;
                
                statsText += `Dígito ${i}: ${freq[i].toLocaleString()} (${percentage}%)\n`;
                
                html += `
                    <div class="frequency-bar">
                        <div class="frequency-digit">${i}</div>
                        <div class="frequency-bar-wrapper">
                            <div class="frequency-bar-fill" style="width: ${barWidth}%">
                                <span>${percentage}%</span>
                            </div>
                        </div>
                        <div class="frequency-stats">${freq[i].toLocaleString()}</div>
                    </div>
                `;
            }

            const expectedFreq = total / 10;
            const variance = freq.reduce((acc, f) => acc + Math.pow(f - expectedFreq, 2), 0) / 10;
            const stdDev = Math.sqrt(variance);

            html += `
                <div class="frequency-total">
                    <p><strong>Frecuencia esperada (distribución uniforme):</strong> ${expectedFreq.toFixed(0)} por dígito (10%)</p>
                    <p><strong>Desviación estándar:</strong> ${stdDev.toFixed(2)}</p>
                </div>
                </div>
            `;

            currentState.rawData = statsText;
            resultsContent.innerHTML = html;
            resultInfo.classList.add('hidden');
        } catch (error) {
            mostrarError('Error al analizar frecuencias. Inténtalo de nuevo.');
        } finally {
            showLoading(false);
        }
    }

    /**
     * Cargar más dígitos (lazy loading)
     */
    async function loadMoreDigits() {
        if (currentState.isLoading) return;
        if (currentState.loadedDigits >= currentState.totalDigits) return;

        showLoading(true, 'Cargando más decimales...');

        try {
            const remaining = currentState.totalDigits - currentState.loadedDigits;
            const toLoad = Math.min(remaining, LOAD_MORE);
            const startPos = currentState.startPosition + currentState.loadedDigits;

            const digits = await fetchDigits(startPos, toLoad);
            
            currentState.data += digits;
            currentState.loadedDigits += digits.length;
            currentState.rawData += digits;

            resultsContent.innerHTML += digits;
            updateResultInfo();
        } catch (error) {
            console.error('Error loading more digits:', error);
        } finally {
            showLoading(false);
        }
    }

    /**
     * Muestra un mensaje de error
     */
    function mostrarError(mensaje) {
        resultsContent.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #5f452a;">
                <div style="font-size: 3rem; margin-bottom: 15px;"><i class="fas fa-exclamation-triangle"></i></div>
                <p style="font-size: 1.1rem;">${mensaje}</p>
            </div>
        `;
        resultInfo.classList.add('hidden');
    }

    /**
     * Copiar resultado al portapapeles
     */
    async function copiarResultado() {
        if (!currentState.rawData) {
            alert('No hay datos para copiar');
            return;
        }

        try {
            await navigator.clipboard.writeText(currentState.rawData);
            
            const btn = document.querySelector('.btn-export');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
            btn.style.background = '#02874d';
            btn.style.color = 'white';
            
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
        } catch (error) {
            alert('Error al copiar. Intenta seleccionar el texto manualmente.');
        }
    }

    /**
     * Descargar resultado como archivo TXT
     */
    function descargarResultado() {
        if (!currentState.rawData) {
            alert('No hay datos para descargar');
            return;
        }

        let filename = `${NUMERO_NOMBRE}_decimales.txt`;
        let content = currentState.rawData;

        if (currentState.type === 'decimals') {
            filename = `${NUMERO_NOMBRE}_primeros_${currentState.loadedDigits}_decimales.txt`;
        } else if (currentState.type === 'range') {
            filename = `${NUMERO_NOMBRE}_rango_${currentState.startPosition + 1}_${currentState.startPosition + currentState.loadedDigits}.txt`;
        } else if (currentState.type === 'position') {
            filename = `${NUMERO_NOMBRE}_posicion_${document.getElementById('posicionEspecifica').value}.txt`;
        } else if (currentState.type === 'search') {
            filename = `${NUMERO_NOMBRE}_busqueda_${currentState.searchSequence}.txt`;
        } else if (currentState.type === 'frequency') {
            filename = `${NUMERO_NOMBRE}_analisis_frecuencia.txt`;
        }

        const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Enter para ejecutar
    document.querySelectorAll('input[type="number"], input[type="text"]').forEach(input => {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const btn = input.closest('.tool-section').querySelector('button');
                if (btn) btn.click();
            }
        });
    });
</script>

<?php 
// 3. CARGA DEL FOOTER
include $raiz . '/includes/footer.php';
?>