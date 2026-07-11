{{--validation error notification--}}
@if ( !$errors->isEmpty() && $errors->any())
    @php
           notifyEvs('error', $errors->first());
           session()->flash('errors', $errors);
           session()->save();
    @endphp
@endif

@session('notifyevs')
    <script>
        (function() {
            'use strict';
            const notifyData = @json(session('notifyevs'));
            if (typeof notifyEvs === 'function') {
                notifyEvs(notifyData.type, notifyData.message);
            }
        })();
    </script>
@endsession
