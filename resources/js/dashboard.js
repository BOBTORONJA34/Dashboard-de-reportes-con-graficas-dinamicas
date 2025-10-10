// resources/js/dashboard.js
// ------------------------------------------------------------
// Lógica del Dashboard: filtros, KPIs, gráficas y exportación CSV
// ------------------------------------------------------------
import { Chart } from "chart.js/auto";

/* ============================
   Helpers y referencias al DOM
   ============================ */
const $ = (sel) => document.querySelector(sel);

// Filtros (los IDs deben coincidir con el Blade)
const $start    = $("#start");
const $end      = $("#end");
const $cat      = $("#category_id");
const $reg      = $("#region_id");
const $min      = $("#amount_min");         // 👈 si no existen en tu vista, no pasa nada
const $max      = $("#amount_max");         // 👈 idem
const $btnApply = $("#btn-aplicar");
const $btnCsv   = $("#btn-csv");

// Canvases para Chart.js
const $barCanvas     = $("#chart");        // barras / línea (ventas por mes)
const $pieCanvas     = $("#pieChart");     // pie (participación por categoría)
const $scatterCanvas = $("#scatterChart"); // 👈 NUEVO: puntos (misma data que pie)

/* ============================
   Utilidades
   ============================ */

// Fechas por defecto: últimos 30 días
(function setDefaultDates() {
  if (!$start || !$end) return;
  const end = new Date();
  const start = new Date();
  start.setDate(end.getDate() - 30);
  const fmt = (d) => d.toISOString().slice(0, 10);
  $start.value = fmt(start);
  $end.value   = fmt(end);
})();

// Formateo de moneda
const fmtMoney = (n) =>
  new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN" }).format(n ?? 0);

// Validar/forzar JSON (si backend retorna HTML 404/500 lanza error comprensible)
async function safeJson(response) {
  const ct = response.headers.get("content-type") || "";
  if (!ct.includes("application/json")) {
    const text = await response.text();
    throw new Error("Respuesta NO JSON (¿404/500?):\n" + text.slice(0, 500));
  }
  return response.json();
}

// Construir query actual
function buildQuery() {
  const p = new URLSearchParams();
  if ($start?.value) p.set("start", $start.value);
  if ($end?.value)   p.set("end",   $end.value);
  if ($cat?.value)   p.set("category_id", $cat.value);
  if ($reg?.value)   p.set("region_id",   $reg.value);
  if ($min?.value)   p.set("amount_min",  $min.value);
  if ($max?.value)   p.set("amount_max",  $max.value);
  return p.toString();
}

/* ============================
   Carga de filtros
   ============================ */
async function loadFilters() {
  if (!$cat || !$reg) return; // si los selects no existen, salta
  const [catRes, regRes] = await Promise.all([
    fetch("/api/filters/categories"),
    fetch("/api/filters/regions"),
  ]);
  const [cats, regs] = await Promise.all([safeJson(catRes), safeJson(regRes)]);

  $cat.innerHTML = `<option value="">Todas</option>`;
  $reg.innerHTML = `<option value="">Todas</option>`;

  // Asumimos objetos {id, name}; ajusta si tu API regresa otro formato
  cats.forEach((c) => {
    const opt = document.createElement("option");
    opt.value = c.id ?? c;
    opt.textContent = c.name ?? String(c);
    $cat.appendChild(opt);
  });

  regs.forEach((r) => {
    const opt = document.createElement("option");
    opt.value = r.id ?? r;
    opt.textContent = r.name ?? String(r);
    $reg.appendChild(opt);
  });
}

/* ============================
   Gráficas
   ============================ */
let barChart = null;
let pieChart = null;
let scatterChart = null;

// Paleta de colores (estable y suficiente para varias categorías)
const COLORS = [
  "#2563eb","#16a34a","#f59e0b","#ef4444","#8b5cf6",
  "#06b6d4","#f97316","#84cc16","#ec4899","#10b981",
  "#4f46e5","#22c55e","#eab308","#dc2626","#a855f7",
];

function colorAt(i) {
  return COLORS[i % COLORS.length];
}

function ensureCharts() {
  // Barras / línea (ventas por mes)
  if (!barChart && $barCanvas) {
    barChart = new Chart($barCanvas, {
      type: "bar",
      data: { labels: [], datasets: [{ label: "Ventas ($)", data: [] }] },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: false } },
      },
    });
  }

  // Pie (participación por categoría)
  if (!pieChart && $pieCanvas) {
    pieChart = new Chart($pieCanvas, {
      type: "pie",
      data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
      options: { responsive: true },
    });
  }

  // 👇 NUEVO: Puntos (misma data del pie). Usamos chart 'line' con showLine:false
  // para tener un scatter categórico (eje X por categorías).
  if (!scatterChart && $scatterCanvas) {
    scatterChart = new Chart($scatterCanvas, {
      type: "line",
      data: { labels: [], datasets: [{
        label: "Participación ($)",
        data: [],
        showLine: false,      // ⬅ sin línea, solo puntos
        pointRadius: 6,       // tamaño del punto
        pointHoverRadius: 8,  // hover más visible
        pointBackgroundColor: [], // colores por punto (match con pie)
        pointBorderWidth: 1,
      }]},
      options: {
        responsive: true,
        plugins: { legend: { display: false }, tooltip: { mode: "nearest" } },
        scales: {
          y: { beginAtZero: true, ticks: { callback: (v) => v } },
          // X es categórico (usa labels = nombres de categoría)
        },
      },
    });
  }
}

/* ============================
   KPIs + Datos + Pintado
   ============================ */
async function loadData() {
  const query = buildQuery();

  const [kpiRes, monthRes, shareRes] = await Promise.all([
    fetch("/api/stats/kpis?" + query),
    fetch("/api/stats/sales-by-month?" + query),
    fetch("/api/stats/sales-share-by-category?" + query),
  ]);

  const kpis   = await safeJson(kpiRes);
  const months = await safeJson(monthRes); // { labels: [...], data/values: [...] }
  const share  = await safeJson(shareRes); // { labels: [...], data: [...] } o array

  // KPIs
  $("#kpiVentasHoy") && ($("#kpiVentasHoy").textContent = fmtMoney(kpis.ventas_hoy ?? kpis.revenue ?? 0));
  $("#kpiOrdenes")   && ($("#kpiOrdenes").textContent   = String(kpis.ordenes ?? kpis.tickets ?? 0));
  $("#kpiAvg")       && ($("#kpiAvg").textContent       = fmtMoney(kpis.avg_ticket ?? 0));

  // Normaliza monthly
  const mLabels = months.labels ?? months.months ?? [];
  const mData   = months.data   ?? months.values ?? [];

  // Normaliza share (puede venir como objetos [{category,total}] o como {labels,data})
  let pieLabels, pieValues;
  if (Array.isArray(share)) {
    pieLabels = share.map(x => x.category ?? x.name ?? "N/D");
    pieValues = share.map(x => Number(x.total ?? 0));
  } else {
    pieLabels = share.labels ?? [];
    pieValues = share.data   ?? share.values ?? [];
  }

  ensureCharts();

  // === Ventas por mes: si hay >=6 puntos, pasa a tipo 'line'; si no, 'bar'
  if (barChart) {
    const manyPoints = (mData?.length ?? 0) >= 6;
    if (barChart.config.type !== (manyPoints ? "line" : "bar")) {
      barChart.destroy();
      barChart = new Chart($barCanvas, {
        type: manyPoints ? "line" : "bar",
        data: { labels: mLabels, datasets: [{ label: "Ventas ($)", data: mData, backgroundColor: "#2563eb" }] },
        options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } },
      });
    } else {
      barChart.data.labels = mLabels;
      barChart.data.datasets[0].data = mData;
      barChart.update();
    }
  }

  // === Pie: colores por porción
  if (pieChart) {
    pieChart.data.labels = pieLabels;
    pieChart.data.datasets[0].data = pieValues;
    pieChart.data.datasets[0].backgroundColor = pieLabels.map((_, i) => colorAt(i));
    pieChart.update();
  }

  // === (NUEVO) Puntos: mismas labels/values del pie
  // Usamos 'line' sin línea para puntos categóricos en X.
  if (scatterChart) {
    scatterChart.data.labels = pieLabels;                // eje X con nombres de categoría
    scatterChart.data.datasets[0].data = pieValues;      // valores en Y
    scatterChart.data.datasets[0].pointBackgroundColor = pieLabels.map((_, i) => colorAt(i)); // mismo color que el pie
    scatterChart.update();
  }
}

/* ============================
   Exportación CSV
   ============================ */
function exportCsv() {
  const url = "/reportes/export-csv?" + buildQuery();
  window.location.href = url;
}

/* ============================
   Eventos
   ============================ */
$btnApply?.addEventListener("click", () => {
  loadData().catch(console.error);
});

$btnCsv?.addEventListener("click", (e) => {
  e.preventDefault();
  exportCsv();
});

/* ============================
   Arranque
   ============================ */
(async function init() {
  try {
    await loadFilters();
    await loadData();
  } catch (err) {
    console.error("Error inicializando dashboard:", err);
  }
})();
