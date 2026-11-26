@extends('layouts.app')

@section('title', 'Uživatelé')

@section('content')
    <div class="container py-4">
        <div class="mb-4">
            <h1 class="h2 mb-1">
                Uživatelé
            </h1>

            <p class="text-body-secondary mb-0">
                Přehled všech účtů a jejich rolí ve frontách. Super admin se nastavuje příkazem
                <code>noq:super-admin</code>, role ve frontách se přiřazují na stránce dané fronty.
            </p>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Jméno</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Super admin</th>
                            <th scope="col">Vlastní fronty</th>
                            <th scope="col">Role ve frontách</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <th scope="row">
                                    {{ $user->name }}
                                </th>

                                <td>
                                    {{ $user->email }}
                                </td>

                                <td>
                                    @if ($user->is_super_admin)
                                        <span class="queue-status queue-status--open">
                                            Ano
                                        </span>
                                    @else
                                        <span class="text-body-secondary">
                                            Ne
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $user->virtual_queues_count }}
                                </td>

                                <td>
                                    @forelse ($user->roleAssignments as $assignment)
                                        <div class="small">
                                            <a href="{{ route('queue.show', $assignment->virtualQueue) }}" class="text-decoration-none">
                                                {{ $assignment->virtualQueue->name }}
                                            </a>
                                            · {{ $assignment->role->label() }}
                                            @if ($assignment->servicePoint)
                                                · {{ $assignment->servicePoint->name }}
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-body-secondary small">
                                            Žádné
                                        </span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
