<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Section --}}
        <x-filament::section>
            <x-slot name="heading">Filter Periode</x-slot>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Bulan</label>
                    <select wire:model.live="filterMonth" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach($this->getMonths() as $key => $month)
                            <option value="{{ $key }}">{{ $month }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Tahun</label>
                    <select wire:model.live="filterYear" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach($this->getYears() as $key => $year)
                            <option value="{{ $key }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>

        {{-- Summary Cards --}}
        @php $summary = $this->getSummary(); @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-success-500">
                        Rp {{ number_format($summary['total_terkumpul'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Terkumpul</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-warning-500">
                        Rp {{ number_format($summary['total_pending'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Belum Terkumpul</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-danger-500">
                        Rp {{ number_format($summary['total_denda'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Denda</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary-500">
                        {{ $summary['persentase'] }}%
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Lunas</div>
                </div>
            </x-filament::section>
        </div>

        {{-- Status Badges --}}
        <div class="flex gap-4 flex-wrap">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-success-500/10 text-success-500">
                <span class="w-2 h-2 rounded-full bg-success-500"></span>
                {{ $summary['sudah_bayar'] }} Lunas
            </span>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-warning-500/10 text-warning-500">
                <span class="w-2 h-2 rounded-full bg-warning-500"></span>
                {{ $summary['belum_bayar'] }} Belum Bayar
            </span>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-danger-500/10 text-danger-500">
                <span class="w-2 h-2 rounded-full bg-danger-500"></span>
                {{ $summary['terlambat'] }} Terlambat
            </span>
        </div>

        {{-- Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
