{{--
  Partial: _risk_pills
  Variables: $critical, $high, $medium (integers)
--}}
<div class="flex items-center gap-1.5 flex-wrap">
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold
                 {{ $critical > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-400' }}">
        <span class="w-1.5 h-1.5 rounded-full {{ $critical > 0 ? 'bg-red-500' : 'bg-gray-300' }}"></span>
        C:{{ $critical }}
    </span>
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold
                 {{ $high > 0 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-400' }}">
        <span class="w-1.5 h-1.5 rounded-full {{ $high > 0 ? 'bg-orange-500' : 'bg-gray-300' }}"></span>
        H:{{ $high }}
    </span>
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold
                 {{ $medium > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-400' }}">
        <span class="w-1.5 h-1.5 rounded-full {{ $medium > 0 ? 'bg-yellow-500' : 'bg-gray-300' }}"></span>
        M:{{ $medium }}
    </span>
</div>
