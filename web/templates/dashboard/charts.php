<?php
/**
 * Dashboard Chart-Templates
 */

switch ($type) {
    case 'monthly-inspections':
        $chartId = 'monthlyInspectionsChart';
        ?>
        <canvas id="<?= $chartId ?>" width="400" height="200"></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?= $chartId ?>').getContext('2d');
            const data = <?= json_encode($data) ?>;
            
            // Daten für Chart vorbereiten
            const labels = data.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('de-DE', { month: 'short', year: '2-digit' });
            });
            
            const passedData = data.map(item => parseInt(item.passed));
            const failedData = data.map(item => parseInt(item.failed));
            const conditionalData = data.map(item => parseInt(item.conditional));
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Bestanden',
                        data: passedData,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Nicht bestanden',
                        data: failedData,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Bedingt bestanden',
                        data: conditionalData,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Monat'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Anzahl Prüfungen'
                            },
                            beginAtZero: true
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        });
        </script>
        <?php
        break;

    case 'inspection-results':
        $chartId = 'inspectionResultsChart';
        ?>
        <canvas id="<?= $chartId ?>" width="300" height="300"></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?= $chartId ?>').getContext('2d');
            const data = <?= json_encode($data) ?>;
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Bestanden', 'Nicht bestanden', 'Bedingt bestanden'],
                    datasets: [{
                        data: [
                            parseInt(data.passed),
                            parseInt(data.failed),
                            parseInt(data.conditional)
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#dc3545',
                            '#ffc107'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
        break;

    case 'ladder-status':
        $chartId = 'ladderStatusChart';
        ?>
        <canvas id="<?= $chartId ?>" width="300" height="300"></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?= $chartId ?>').getContext('2d');
            const data = <?= json_encode($data) ?>;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Aktiv', 'Inaktiv', 'Defekt', 'Entsorgt'],
                    datasets: [{
                        data: [
                            parseInt(data.active),
                            parseInt(data.inactive),
                            parseInt(data.defective),
                            parseInt(data.disposed)
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#6c757d',
                            '#dc3545',
                            '#343a40'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '50%'
                }
            });
        });
        </script>
        <?php
        break;

    case 'inspection-types':
        $chartId = 'inspectionTypesChart';
        ?>
        <canvas id="<?= $chartId ?>" width="300" height="200"></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?= $chartId ?>').getContext('2d');
            const data = <?= json_encode($data) ?>;
            
            const labels = data.map(item => {
                const typeNames = {
                    'routine': 'Routine',
                    'initial': 'Erstprüfung',
                    'after_incident': 'Nach Vorfall',
                    'special': 'Sonderprüfung'
                };
                return typeNames[item.inspection_type] || item.inspection_type;
            });
            
            const counts = data.map(item => parseInt(item.count));
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Anzahl Prüfungen',
                        data: counts,
                        backgroundColor: [
                            '#007bff',
                            '#28a745',
                            '#ffc107',
                            '#17a2b8'
                        ],
                        borderColor: [
                            '#0056b3',
                            '#1e7e34',
                            '#e0a800',
                            '#117a8b'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
        break;

    case 'ladder-types':
        $chartId = 'ladderTypesChart';
        ?>
        <canvas id="<?= $chartId ?>" width="300" height="200"></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?= $chartId ?>').getContext('2d');
            const data = <?= json_encode($data) ?>;
            
            const labels = data.map(item => item.ladder_type);
            const totals = data.map(item => parseInt(item.total));
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Anzahl Leitern',
                        data: totals,
                        backgroundColor: [
                            '#007bff',
                            '#28a745',
                            '#ffc107',
                            '#dc3545',
                            '#17a2b8'
                        ],
                        borderColor: [
                            '#0056b3',
                            '#1e7e34',
                            '#e0a800',
                            '#c82333',
                            '#117a8b'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
        break;

    case 'defect-statistics':
        $chartId = 'defectStatisticsChart';
        ?>
        <canvas id="<?= $chartId ?>" width="400" height="200"></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?= $chartId ?>').getContext('2d');
            const data = <?= json_encode($data) ?>;
            
            const labels = data.map(item => {
                const categoryNames = {
                    'structure': 'Struktur',
                    'safety': 'Sicherheit',
                    'function': 'Funktion',
                    'marking': 'Kennzeichnung',
                    'accessories': 'Zubehör'
                };
                return categoryNames[item.category] || item.category;
            });
            
            const defects = data.map(item => parseInt(item.defects));
            const criticalDefects = data.map(item => parseInt(item.critical_defects));
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Mängel gesamt',
                        data: defects,
                        backgroundColor: '#ffc107',
                        borderColor: '#e0a800',
                        borderWidth: 1
                    }, {
                        label: 'Kritische Mängel',
                        data: criticalDefects,
                        backgroundColor: '#dc3545',
                        borderColor: '#c82333',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
        break;

    case 'maintenance-trend':
        $chartId = 'maintenanceTrendChart';
        ?>
        <canvas id="<?= $chartId ?>" width="400" height="200"></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?= $chartId ?>').getContext('2d');
            const data = <?= json_encode($data) ?>;
            
            // Daten für die letzten 6 Monate vorbereiten
            const months = [];
            const overdueData = [];
            const upcomingData = [];
            
            for (let i = 5; i >= 0; i--) {
                const date = new Date();
                date.setMonth(date.getMonth() - i);
                months.push(date.toLocaleDateString('de-DE', { month: 'short', year: '2-digit' }));
                
                // Hier würden normalerweise historische Daten verwendet
                // Für Demo-Zwecke verwenden wir Zufallswerte
                overdueData.push(Math.floor(Math.random() * 20));
                upcomingData.push(Math.floor(Math.random() * 50));
            }
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Überfällige Prüfungen',
                        data: overdueData,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Anstehende Prüfungen',
                        data: upcomingData,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 5
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
        break;

    default:
        echo '<div class="alert alert-warning">Unbekannter Chart-Typ: ' . htmlspecialchars($type) . '</div>';
        break;
}
?>
