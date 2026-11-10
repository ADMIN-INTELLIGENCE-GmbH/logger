<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <strong>{{ $client->name }}</strong> wants to access Logger as <strong>{{ $user->email }}</strong>.
    </div>

    @if (count($scopes) > 0)
        <ul class="mb-4 list-disc list-inside text-sm text-gray-600">
            @foreach ($scopes as $scope)
                <li>{{ $scope->description }}</li>
            @endforeach
        </ul>
    @endif

    <div class="flex items-center justify-end gap-3 mt-4">
        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <x-secondary-button type="submit">{{ __('Deny') }}</x-secondary-button>
        </form>

        <form method="POST" action="{{ route('passport.authorizations.approve') }}">
            @csrf
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <x-primary-button>{{ __('Authorize') }}</x-primary-button>
        </form>
    </div>
</x-guest-layout>
