<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Laporan Kas Per Periode
        </x-slot>

        <div class="space-y-4">
            {{ $this->form }}

            @php
                $report = $this->getReportData();
                $months = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                    4 => 'April', 5 => 'Mei', 6 => 'Juni',
                    7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                    10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ];
                $monthName = $months[$report['month']] ?? '';
            @endphp

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="text-sm text-green-600 dark:text-green-400 font-medium">Terkumpul</div>
                    <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                        Rp {{ number_format($report['total_collected'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-green-600 dark:text-green-400">
                        {{ $report['paid_count'] }} pembayaran
                    </div>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                    <div class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">Pending</div>
                    <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">
                        Rp {{ number_format($report['total_pending'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-yellow-600 dark:text-yellow-400">
                        {{ $report['pending_count'] }} anggota
                    </div>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                    <div class="text-sm text-red-600 dark:text-red-400 font-medium">Terlambat</div>
                    <div class="text-2xl font-bold text-red-700 dark:text-red-300">
                        Rp {{ number_format($report['total_overdue'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-red-600 dark:text-red-400">
                        {{ $report['overdue_count'] }} anggota
                    </div>
                </div>
            </div>

            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Periode: {{ $monthName }} {{ $report['year'] }} | 
                Total: {{ $report['total_count'] }} record
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
