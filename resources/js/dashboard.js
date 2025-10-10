// resources/js/dashboard.js
// ------------------------------------------------------------
// Dashboard: filtros, KPIs, gráficas y exportación CSV.
// - Paletas de color por punto
// - "Motor" auto-chart: cambia bar <-> line según # de puntos
// - Filtros adicionales: amount_min / amount_max
// - Tolerante a nombres de KPIs (revenue|ventas_hoy, tickets|ordenes)
// ------------------------------------------------------------

import { Chart } from "chart.js/auto";

/* ============================
   🎨 Paletas de color
   ============================ */
const BAR_COLORS = [
  "#2563eb", "#16a34a", "#f59e0b", "#ef4444", "#8b5cf6",
  "#06b6d4", "#f97316", "#22c55e", "#e11d48", "#0ea5e9",
];
const PIE_COLORS = [
  "#60a5fa", "#34d399", "#fbbf24", "#f87171", "#a78bfa",
  "#2dd4bf", "#fb923c", "#86efac", "#f472b6", "#93c5fd",
];

/* ============================
   DOM
   ============================ */
const $ = (s) => document.querySelector(s);

const $start    = $("#start");
const $end      = $("#end");
const $cat      = $("#category_id");
const $reg      = $("#region_id");
const $min      = $("#amount_min");   // 👈 nuevo
const $max      = $("#amount_max");   // 👈 nuevo
const $btnApply = $("#btn-aplicar");
const $btnCsv   = $("#btn-csv");

const $barCanvas = $("#chart");
const $pieCanvas = $("#pieChart");

/* ============================
   Utils
   ============================ */
(function setDefaultDates(){
  const end = new Date();
  const start = new Date();
  start.setDate(end.getDate() - 30);
  const fmt = (d) => d.toISOString().slice(0,10);
  $start.value = fmt(start);
  $end.value   = fmt(end);
})();
const fmtMoney = (n) =>
  new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN" }).format(n ?? 0);

async function safeJson(response) {
  const ct = response.headers.get("content-type") || "";
  if (!ct.includes("application/json")) {
    const text = await response.text();
    throw new Error("Respuesta NO JSON (¿404/500?):\n" + text.slice(0, 600));
  }
  return response.json();
}
function buildQuery() {
  const p = new URLSearchParams();
  if ($start.value) p.set("start", $start.value);
  if ($end.value)   p.set("end",   $end.value);
  if ($cat.value)   p.set("category_id", $cat.value);
  if ($reg.value)   p.set("region_id",   $reg.value);
  if ($min?.value)  p.set("amount_min",  $min.value);
  if ($max?.value)  p.set("amount_max",  $max.value);
  return p.toString();
}

/* ============================
   Filtros dinámicos
   ============================ */
async function loadFilters() {
  const [catRes, regRes] = await Promise.all([
    fetch("/api/filters/categories"),
    fetch("/api/filters/regions"),
  ]);
  const [cats, regs] = await Promise.all([safeJson(catRes), safeJson(regRes)]);

  $cat.innerHTML = `<option value="">Todas</option>`;
  $reg.innerHTML = `<option value="">Todas</option>`;

  cats.forEach((c) => {
    const o = document.createElement("option");
    // Espera {id, name}. Si tu API fuera sólo strings, usa: o.value = o.textContent = c;
    o.value = c.id ?? c.value ?? c;
    o.textContent = c.name ?? c.label ?? c;
    $cat.appendChild(o);
  });
  regs.forEach((r) => {
    const o = document.createElement("option");
    o.value = r.id ?? r.value ?? r;
    o.textContent = r.name ?? r.label ?? r;
    $reg.appendChild(o);
  });
}

/* ============================
   Charts
   ============================ */
let barChart = null;
let pieChart = null;

function createBarOrLineChart(type, labels, values) {
  return new Chart($barCanvas, {
    type,
    data: {
      labels,
      datasets: [{
        label: "Ventas ($)",
        data: values,
        backgroundColor: values.map((_, i) => BAR_COLORS[i % BAR_COLORS.length]),
        borderColor: "#2563eb",
        tension: 0.3,
        fill: type === "line" ? false : true,
      }],
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } },
  });
}
function ensureCharts() {
  if (!barChart) barChart = createBarOrLineChart("bar", [], []);
  if (!pieChart) {
    pieChart = new Chart($pieCanvas, {
      type: "pie",
      data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
      options: { responsive: true },
    });
  }
}

/* ============================
   Carga KPIs + series
   ============================ */
async function loadData() {
  const query = buildQuery();
  const [kpiRes, monthRes, shareRes] = await Promise.all([
    fetch("/api/stats/kpis?" + query),
    fetch("/api/stats/sales-by-month?" + query),
    fetch("/api/stats/sales-share-by-category?" + query),
  ]);
  const kpis   = await safeJson(kpiRes);
  const months = await safeJson(monthRes); // {labels, data} o {labels, values}
  const share  = await safeJson(shareRes); // {labels, data} o array [{category,total}]

  // KPIs (acepta nombres alternos por compatibilidad)
  const revenue = kpis.revenue ?? kpis.ventas_hoy ?? 0;
  const tickets = kpis.tickets ?? kpis.ordenes ?? 0;
  const avg     = kpis.avg_ticket ?? 0;

  $("#kpiVentasHoy").textContent = fmtMoney(revenue);
  $("#kpiOrdenes").textContent   = String(tickets);
  $("#kpiAvg").textContent       = fmtMoney(avg);

  // === Ventas por mes (motor auto-chart) ===
  ensureCharts();

  const mLabels = months.labels ?? months.months ?? [];
  const mValues = months.data   ?? months.values ?? [];

  const desired = (mValues.length >= 6) ? "line" : "bar";
  if (barChart.config.type !== desired) {
    barChart.destroy();
    barChart = createBarOrLineChart(desired, mLabels, mValues);
  } else {
    barChart.data.labels = mLabels;
    barChart.data.datasets[0].data = mValues;
    barChart.data.datasets[0].backgroundColor =
      mValues.map((_, i) => BAR_COLORS[i % BAR_COLORS.length]);
    barChart.update();
  }

  // === Participación por categoría (pie) ===
  const pieLabels = Array.isArray(share)
    ? share.map(x => x.category ?? x.name ?? "N/D")
    : (share.labels ?? []);
  const pieValues = Array.isArray(share)
    ? share.map(x => Number(x.total ?? 0))
    : (share.data ?? share.values ?? []);

  pieChart.data.labels = pieLabels;
  pieChart.data.datasets[0].data = pieValues;
  pieChart.data.datasets[0].backgroundColor =
    pieLabels.map((_, i) => PIE_COLORS[i % PIE_COLORS.length]);
  pieChart.update();
}

/* ============================
   Exportación CSV
   ============================ */
function exportCsv() {
  window.location.href = "/reportes/export-csv?" + buildQuery();
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
