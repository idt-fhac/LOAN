@extends('layouts.app')

@section('content')
<div class="lg:container mx-auto mb-8 p-4 bg-gray-600 rounded text-white">
    <h1 class="text-2xl font-bold mb-4">{{ __('Ausleihprotokoll') }}</h1>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-500 text-left">
                    <th class="p-3">{{ __('Gerät') }}</th>
                    <th class="p-3">{{ __('Person') }}</th>
                    <th class="p-3">{{ __('Ausgegeben') }}</th>
                    <th class="p-3">{{ __('Fällig') }}</th>
                    <th class="p-3">{{ __('Zurück') }}</th>
                    <th class="p-3">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr class="border-b border-gray-600 hover:bg-gray-600/50">
                        <td class="p-3">
                            {{ $loan->device?->deviceModel?->name ?? '—' }}
                        </td>
                        <td class="p-3">
                            {{ $loan->borrower_name }}
                            @unless ($loan->user)
                                <span class="block text-xs text-gray-400">{{ __('ohne Konto') }}</span>
                            @endunless
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            {{ $loan->checked_out_at?->format('d.m.Y') }}
                            <span class="block text-xs text-gray-300">{{ $loan->issuedBy?->name }}</span>
                        </td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->due_at?->format('d.m.Y') }}</td>
                        <td class="p-3 whitespace-nowrap">
                            {{ $loan->returned_at?->format('d.m.Y') ?? '—' }}
                            <span class="block text-xs text-gray-300">{{ $loan->returnedTo?->name }}</span>
                        </td>
                        <td class="p-3">
                            @if ($loan->returned_at)
                                <span class="rounded bg-gray-800 px-2 py-1 text-xs">{{ __('Zurückgegeben') }}</span>
                            @elseif ($loan->isOverdue())
                                <span class="rounded bg-red-700 px-2 py-1 text-xs">{{ __('Überfällig') }}</span>
                            @else
                                <span class="rounded bg-yellow-600 px-2 py-1 text-xs">{{ __('Ausgeliehen') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-gray-300">
                            {{ __('Noch keine Ausleihvorgänge erfasst.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $loans->links() }}</div>
</div>
@endsection
