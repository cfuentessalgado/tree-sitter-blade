<div @class([
    'text-rose-400 bg-rose-400/10' => $table->latestDump?->isError(),
    'text-green-400 bg-green-400/10' => $table->latestDump?->isSuccess(),
    'text-orange-400 bg-orange-400/10' => $table->latestDump?->isProcessing(),
    'flex-none',
    'rounded-full',
    'p-1'
])></div>
