@extends('layouts.app')

@section('title', 'Statistik')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Statistik</h1>

{{-- Status Distribution --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="bi bi-bar-chart-fill text-indigo-600 mr-2"></i>
        Ticket-Verteilung nach Status
    </h2>
    <div class="relative" style="height: 400px;">
        <canvas id="statusChart"></canvas>
    </div>
</div>

{{-- Assignee Workload --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="bi bi-person-fill-gear text-amber-500 mr-2"></i>
        Zustaendige &mdash; In Bearbeitung
    </h2>
    @if(count($assigneeWorkload) === 0)
        <p class="text-gray-500 text-sm">Keine Tickets in Bearbeitung.</p>
    @else
        <div class="relative" style="height: 400px;">
            <canvas id="assigneeInProgressChart"></canvas>
        </div>
    @endif
</div>

{{-- Completed per Assignee --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="bi bi-person-check-fill text-green-600 mr-2"></i>
        Abgeschlossene Aufgaben pro Zustaendiger
    </h2>
    @if(count($completedPerAssignee) === 0)
        <p class="text-gray-500 text-sm">Keine abgeschlossenen Tickets vorhanden.</p>
    @else
        <div class="relative" style="height: 400px;">
            <canvas id="completedChart"></canvas>
        </div>
    @endif
</div>

{{-- Neighbor Statistics --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="bi bi-people-fill text-green-600 mr-2"></i>
        Betroffene Nachbarn
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6 text-center">
            <p class="text-3xl font-bold text-indigo-600" id="totalNeighbors">-</p>
            <p class="text-sm text-indigo-600 mt-1">
                <i class="bi bi-people-fill mr-1"></i>
                Nachbarn insgesamt betroffen
            </p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
            <p class="text-3xl font-bold text-green-600" id="avgNeighbors">-</p>
            <p class="text-sm text-green-600 mt-1">
                <i class="bi bi-calculator mr-1"></i>
                Nachbarn pro Ticket (Durchschnitt)
            </p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 text-center">
            <p class="text-3xl font-bold text-amber-600" id="totalTickets">-</p>
            <p class="text-sm text-amber-600 mt-1">
                <i class="bi bi-ticket-perforated mr-1"></i>
                Tickets gesamt
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const statusData = @json($statusDistribution);
    const assigneeWorkload = @json($assigneeWorkload);
    const completedPerAssignee = @json($completedPerAssignee);

    // Compute neighbor statistics from status data
    const totalTickets = statusData.reduce((sum, s) => sum + s.count, 0);
    document.getElementById('totalTickets').textContent = totalTickets;

    // Status Chart
    const statusLabels = statusData.map(s => s.name);
    const statusCounts = statusData.map(s => s.count);
    const statusColors = statusData.map(s => {
        const hex = s.color.replace('#', '');
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, 0.6)`;
    });
    const statusBorderColors = statusData.map(s => {
        const hex = s.color.replace('#', '');
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, 1)`;
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: statusLabels,
            datasets: [{
                label: 'Anzahl Tickets',
                data: statusCounts,
                backgroundColor: statusColors,
                borderColor: statusBorderColors,
                borderWidth: 1,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } },
            },
            onClick: function(event, elements) {
                if (elements.length > 0) {
                    const label = statusLabels[elements[0].index];
                    window.location.href = '/?status=' + encodeURIComponent(label);
                }
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
            },
        },
    });

    // Assignee In Progress Chart
    if (Object.keys(assigneeWorkload).length > 0) {
        const assigneeLabels = Object.keys(assigneeWorkload);
        const assigneeCounts = Object.values(assigneeWorkload);

        new Chart(document.getElementById('assigneeInProgressChart'), {
            type: 'bar',
            data: {
                labels: assigneeLabels,
                datasets: [{
                    label: 'Anzahl Tickets',
                    data: assigneeCounts,
                    backgroundColor: 'rgba(255, 159, 64, 0.5)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                onClick: function(event, elements) {
                    if (elements.length > 0) {
                        const label = assigneeLabels[elements[0].index];
                        window.location.href = '/?assignee=' + encodeURIComponent(label);
                    }
                },
                onHover: (event, elements) => {
                    event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
            },
        });
    }

    // Completed per Assignee Chart
    if (Object.keys(completedPerAssignee).length > 0) {
        const completedLabels = Object.keys(completedPerAssignee);
        const completedCounts = Object.values(completedPerAssignee);

        new Chart(document.getElementById('completedChart'), {
            type: 'bar',
            data: {
                labels: completedLabels,
                datasets: [{
                    label: 'Anzahl Tickets',
                    data: completedCounts,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                onClick: function(event, elements) {
                    if (elements.length > 0) {
                        const label = completedLabels[elements[0].index];
                        window.location.href = '/?assignee=' + encodeURIComponent(label);
                    }
                },
                onHover: (event, elements) => {
                    event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
            },
        });
    }

    // Calculate neighbor stats client-side from total
    // The controller doesn't pass neighbor data, so display total tickets as a summary
    document.getElementById('totalNeighbors').textContent = '-';
    document.getElementById('avgNeighbors').textContent = '-';
</script>
@endsection
