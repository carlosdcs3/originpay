@php
    use App\Constants\Status;
    $pusherConfig = pluginCredentials('pusher');
@endphp

@if($pusherConfig['status'] == Status::TRUE)
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>

    <script>
        (function () {
            'use strict';

            const pusherKey = '{{ $pusherConfig['pusher_app_key'] }}';
            const pusherCluster = '{{ $pusherConfig['pusher_app_cluster'] }}';
            const authEndpoint = '/broadcasting/auth';
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            if (!csrfToken) {
                console.error('CSRF token not found!');
                return;
            }

            const pusher = new Pusher(pusherKey, {
                cluster: pusherCluster,
                encrypted: true,
                authEndpoint: authEndpoint,
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                }
            });

            let userId, isAdmin, channelName;

            @if (auth('admin')->check())
                isAdmin = true;
                userId = {{ auth('admin')->user()->id }};
                channelName = `private-App.Models.Admin.${userId}`;
            @elseif (auth()->check())
                isAdmin = false;
                userId = {{ auth()->user()->id }};
                channelName = `private-App.Models.User.${userId}`;
            @else
                return;
            @endif

            const subscribeToChannel = (channelName, callback) => {
                const channel = pusher.subscribe(channelName);

                channel.bind('notification.received', function(data) {
                    if (typeof callback === 'function') callback(data);
                });
            };

            subscribeToChannel(channelName, function () {
                const $notificationDropdown = isAdmin
                    ? $('#append-new-admin-notification')
                    : $('.append-new-notification');

                const notificationUrl = isAdmin
                    ? '{{ route('admin.notifications.recent') }}'
                    : '{{ route('user.notifications.recent') }}';

                $.get(notificationUrl, function (response) {
                    $notificationDropdown.html(response);
                });
            });
        })();
    </script>
@endif
