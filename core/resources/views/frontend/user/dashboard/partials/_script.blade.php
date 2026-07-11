<script>
    "use strict";

    // Define Dark Finance colors
    const successColor = ["#00D4AA"]; // Teal for success/deposit
    const failColor    = ["#FF4D6A"]; // Red for fail/withdraw
    
    // For withdraw chart, we want withdraw to be the primary metric
    const withdrawSuccessColor = ["#FF8C42"]; // Orange for withdraws
    const withdrawFailColor    = ["#55556A"]; // Gray for failed withdraws

    // Convert Laravel data to JavaScript arrays
    const depositData = @json($sortedDeposits);
    const withdrawData = @json($sortedWithdrawals);

    // Get max value for uniform Y-axis scaling
    function getMaxValue(data, successKey, failKey) {
        return data.reduce((maxVal, item) =>
            Math.max(maxVal, item[successKey] ?? 0, item[failKey] ?? 0), 0);
    }

    const depositMax = getMaxValue(depositData, 'success_total', 'fail_total');
    const withdrawMax = getMaxValue(withdrawData, 'withdraw_success_total', 'withdraw_fail_total');
    const globalMax = Math.max(depositMax, withdrawMax);

    /**
     * Render an ApexCharts area chart with dark finance theme
     */
    function renderChart(elementId, categories, successData, failData, dataset, labels, primaryColor, secondaryColor) {
        const options = {
            series: [
                {name: labels[0], data: successData},
                {name: labels[1], data: failData}
            ],
            chart: {
                height: 220,
                type: 'area',
                toolbar: {show: false},
                fontFamily: 'Inter, sans-serif',
                foreColor: '#8888A8' // Text muted color
            },
            grid: {
                show: true,
                borderColor: "rgba(255,255,255,0.06)", // ds-border-subtle
                strokeDashArray: 4,
                padding: { top: 0, right: 0, bottom: 0, left: 10 }
            },
            dataLabels: {enabled: false},
            stroke: {curve: 'smooth', width: 2, colors: [primaryColor, secondaryColor]},
            xaxis: {
                tooltip: {enabled: false},
                categories: categories, // Day names
                crosshairs: {show: false},
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    show: true,
                    style: {colors: "#8888A8", fontSize: "11px", fontWeight: 500}
                }
            },
            yaxis: {
                labels: {
                    style: {colors: "#8888A8", fontSize: "11px", fontWeight: 500},
                    formatter: (value) => { return value >= 1000 ? (value/1000).toFixed(1) + 'k' : value }
                }
            },
            tooltip: {
                theme: 'dark',
                x: {
                    formatter: (val) => {
                        // Attempt to format if dayMap is available, otherwise return val
                        try { return "Dia: " + dayMap(val); } catch(e) { return "Dia: " + val; }
                    }
                },
                y: {
                    formatter: (val, {dataPointIndex}) => {
                        const symbol = dataset[dataPointIndex]?.symbol ?? 'R$ ';
                        return symbol + parseFloat(val).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    }
                }
            },
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.0,
                    stops: [0, 100]
                }
            },
            colors: [primaryColor, secondaryColor],
            markers: {size: 0, strokeWidth: 0, hover: {size: 4}},
            legend: {show: false}
        };

        new ApexCharts(document.querySelector(elementId), options).render();
    }

    // Render Deposit Chart
    if(document.querySelector("#deposit-chart") && depositData.length > 0) {
        renderChart(
            "#deposit-chart",
            depositData.map(item => item.day),
            depositData.map(item => item.success_total),
            depositData.map(item => item.fail_total),
            depositData,
            ['Depósitos (Sucesso)', 'Depósitos (Falha)'],
            successColor[0], 
            failColor[0]
        );
    }

    // Render Withdraw Chart
    if(document.querySelector("#withdraw-chart") && withdrawData.length > 0) {
        renderChart(
            "#withdraw-chart",
            withdrawData.map(item => item.day),
            withdrawData.map(item => item.withdraw_success_total),
            withdrawData.map(item => item.withdraw_fail_total),
            withdrawData,
            ['Saques (Sucesso)', 'Saques (Falha)'],
            withdrawSuccessColor[0], 
            withdrawFailColor[0]
        );
    }

    document.addEventListener("DOMContentLoaded", function() {
        let toggleBtn = document.getElementById("toggleLinksBtn");
        let hiddenLinks = document.querySelectorAll(".more-links");
        let isExpanded = false;

        if (toggleBtn) {
            toggleBtn.addEventListener("click", function() {
                hiddenLinks.forEach(el => el.classList.toggle("d-none"));
                isExpanded = !isExpanded;
                toggleBtn.textContent = isExpanded ? "Mostrar menos" : "Mostrar mais";
            });
        }
    });
</script>