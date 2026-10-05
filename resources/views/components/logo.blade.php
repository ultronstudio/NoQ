<span {{ $attributes->except(['alt', 'width', 'height'])->class(['noq-logo']) }}>
    @foreach ($sources as $mode => $file)
        <img
            src="{{ asset('assets/logo/'.$file) }}"
            alt="{{ $attributes->get('alt', 'NoQ') }}"
            width="{{ $attributes->get('width', 180) }}"
            height="{{ $attributes->get('height', str_starts_with($variant, 'symbol-') ? 180 : 105) }}"
            @class([
                'noq-logo__image',
                'noq-logo__image--'.$mode => $variant === 'auto',
            ])
        >
    @endforeach
</span>
